<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Part;

use ClassicPress\SimpleDBBackup\Database\DatabaseAware;
use ClassicPress\SimpleDBBackup\Database\DatabaseAwareInterface;
use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Engine\AbstractPart;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\ActionAware;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\Database\ActionAware as DatabaseActionAware;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAware;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table\FilterInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table\NonCore;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table\Sorter;
use ClassicPress\SimpleDBBackup\Engine\Core\Helper\MemoryInfo;
use ClassicPress\SimpleDBBackup\Engine\Core\OutputWriterAware;
use ClassicPress\SimpleDBBackup\Engine\Core\OutputWriterAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\LoggerAware;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;
use ClassicPress\SimpleDBBackup\Timer\TimerInterface;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;

/**
 * An Engine Part which iterates a database for tables
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Part
 */
class Database extends AbstractPart implements
	ConfigurationAwareInterface,
	DatabaseAwareInterface,
	OutputWriterAwareInterface
{

	use LoggerAware;
	use DatabaseAware;
	use ConfigurationAware;
	use ActionAware;
	use OutputWriterAware;
	use DatabaseActionAware;

	/**
	 * Hard-coded list of table filter classes. This is for convenience in testing.
	 *
	 * @var  array
	 */
	private $filters = [
		NonCore::class,
		// IMPORTANT! Always put the sorting class **LAST**.
		Sorter::class
	];

	/**
	 * Hard-coded list of per-database action classes. This is for convenience in testing.
	 *
	 * @var  array
	 */
	private $perDatabaseActionClasses = [
	];

	/**
	 * Hard-coded name of the Table engine part class. This is for convenience in testing.
	 *
	 * @var  string
	 */
	private $tablePartClass = 'ClassicPress\\SimpleDBBackup\\Engine\\Core\\Part\\Table';

	/**
	 * The memory information helper, used to take decisions based on the available PHP memory
	 *
	 * @var  MemoryInfo
	 */
	protected $memoryInfo = null;

	/**
	 * The list of tables to process. Initialized in prepare().
	 *
	 * @var  array
	 */
	private $tableList = [];

	/**
	 * The Engine Part we tick to process a table
	 *
	 * @var  AbstractPart
	 */
	private $tablePart = null;

	/**
	 * Overloaded constructor.
	 *
	 * @param   TimerInterface   $timer         Timer object
	 * @param   Driver           $db            Database driver object
	 * @param   LoggerInterface  $logger        Logger object
	 * @param   WriterInterface  $outputWriter  Output SQL file writer (null to disable the feature)
	 * @param   Configuration    $config        Engine configuration
	 * @param   MemoryInfo       $memoryInfo    Memory information helper object
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct(TimerInterface $timer, Driver $db, LoggerInterface $logger, WriterInterface $outputWriter, Configuration $config, MemoryInfo $memoryInfo)
	{
		$this->setDriver($db);
		$this->setLogger($logger);
		$this->setConfig($config);
		$this->setOutputWriter($outputWriter);

		$this->memoryInfo = $memoryInfo;

		$this->setDomain($this->getDbo()->getDatabase());

		parent::__construct($timer, $config);
	}

	/**
	 * Executes when the state is STATE_INIT. You are supposed to set up internal objects and do any other kind of
	 * preparatory work which does not take too much time.
	 *
	 * @return  void
	 */
	protected function prepare()
	{
		$this->setStep('Initialization...');
		$this->setSubstep('');

		// Log things the user should know
		$this->getLogger()->info(sprintf("Starting to process backup of database “%s”", $this->getDbo()->getDatabase()));

		$this->logOutputWriter();

		// Run once-per-database callbacks.
		$this->getLogger()->debug("Retrieving database metadata");

		try
		{
			$databaseMeta = $this->getDbo()->getDatabaseMeta();
			$this->runPerDatabaseActions($this->perDatabaseActionClasses, $databaseMeta, $this->getLogger(),
				$this->getOutputWriter(), $this->getDbo(), $this->getConfig());
		}
		catch (\RuntimeException $e)
		{
			if (strpos($e->getMessage(), 'does not have access to INFORMATION_SCHEMA'))
			{
				throw $e;
			}

			$this->getLogger()->warning($e->getMessage());
		}

		// Get and filter the list of tables.
		$this->getLogger()->debug('Getting the list of database tables');
		$this->tableList = $this->getDbo()->getTableList();

		$this->getLogger()->debug('Filtering the list of database tables');
		$this->tableList = $this->applyFilters($this->tableList, $this->filters);
	}

	/**
	 * Main processing. Here you do the bulk of the work. When you no longer have any more work to do return boolean
	 * false.
	 *
	 * @return  bool  false to indicate you are done, true to indicate more work is to be done.
	 */
	protected function process()
	{
		// If no current table is set we need to iterate the next table
		if (empty($this->tablePart))
		{
			try
			{
				$this->takeNextTable();
			}
			catch (\UnderflowException $e)
			{
				// Oh, no more tables on the list. We are done here.
				return false;
			}

			// The table was filtered out. Get the next table on the next tick.
			if (empty($this->tablePart))
			{
				return true;
			}
		}

		// I'm running out of time. Let processing take place in the next step.
		if ($this->timer->getTimeLeft() < 0.001)
		{
			return true;
		}

		// Run a single step of the table processing Engine Part
		$status = $this->tablePart->tick();

		// Inherit warnings and errors
		$this->inheritWarningsFrom($this->tablePart);
		$this->inheritErrorFrom($this->tablePart);

		$this->setSubstep($this->tablePart->getSubstep());

		// If we have an error we must stop processing right away
		if (is_object($status->getError()))
		{
			return false;
		}

		// If the table processing Engine Part is done we indicate we need a new table
		if ($status->isDone())
		{
			$this->tablePart = null;
		}

		// We have more work to do
		return true;
	}

	/**
	 * Finalization. Here you are supposed to perform any kind of tear down after your work is done.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	protected function finalize()
	{
		$this->setStep('Finalization...');
		$this->setSubstep('');

		$this->getLogger()->info(sprintf("Finished processing replacements in database “%s”", $this->getDbo()->getDatabase()));
	}

	/**
	 * Apply the hard-coded list of table filters against the provided table list
	 *
	 * @param   array  $tables   The tables to filters
	 * @param   array  $filters  List of filter classes to instantiate
	 *
	 * @return  array  The filtered tables after applying all filters
	 */
	private function applyFilters(array $tables, array $filters)
	{
		foreach ($filters as $class)
		{
			if (!class_exists($class))
			{
				$this->addWarningMessage(sprintf("Filter class “%s” not found. Is your installation broken?", $class));

				continue;
			}

			if (!in_array('ClassicPress\\SimpleDBBackup\\Engine\\Core\\Filter\\Table\\FilterInterface', class_implements($class)))
			{
				$this->addWarningMessage(sprintf("Filter class “%s” is not a valid table filter. Is your installation broken?", $class));

				continue;
			}

			/** @var FilterInterface $o */
			$o = new $class($this->getLogger(), $this->getDbo(), $this->getConfig());
			$tables = $o->filter($tables);
		}

		return $tables;
	}

	/**
	 * Log the path (if any) of the output SQL file
	 *
	 * @return  void
	 */
	protected function logOutputWriter()
	{
		$outputWriter = $this->getOutputWriter();
		$path   = $outputWriter->getFilePath();

		if (empty($path))
		{
			$path = '(none)';
		}

		$this->getLogger()->info("Output SQL file: $path");
	}

	/**
	 * Prepare to operate on the next table on the list.
	 */
	protected function takeNextTable()
	{
		// Make sure there are more tables to process
		if (empty($this->tableList))
		{
			throw new \UnderflowException("The list of tables is empty");
		}

		// Get the table meta of the next table to process
		$tableName       = array_shift($this->tableList);
		$tableMeta       = $this->getDbo()->getTableMeta($tableName);
		$this->tablePart = null;

		$this->setStep($tableMeta->getName());

		// Create a new table Engine Part
		$class           = $this->tablePartClass;
		$this->tablePart = new $class($this->timer, $this->getDbo(), $this->getLogger(), $this->getConfig(), $this->getOutputWriter(), $tableMeta, $this->memoryInfo);
	}
}