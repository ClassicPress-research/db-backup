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
use ClassicPress\SimpleDBBackup\Database\Metadata\Column;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table as TableMeta;
use ClassicPress\SimpleDBBackup\Database\Query;
use ClassicPress\SimpleDBBackup\Engine\AbstractPart;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\ActionAware;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\ActionAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\Table\ActionAware as TableActionAware;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\Table\GetCreate;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAware;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Data\FilterInterface as DataFilterInterace;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Data\NoViewData;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Data\SpecialEngines;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Row\FilterInterface as RowFilterInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Helper\MemoryInfo;
use ClassicPress\SimpleDBBackup\Engine\Core\OutputWriterAware;
use ClassicPress\SimpleDBBackup\Engine\Core\OutputWriterAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\PartInterface;
use ClassicPress\SimpleDBBackup\Engine\StepAware;
use ClassicPress\SimpleDBBackup\Logger\LoggerAware;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;
use ClassicPress\SimpleDBBackup\Timer\TimerInterface;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;

/**
 * An Engine Part to process the contents of database tables
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Part
 */
class Table extends AbstractPart implements
	PartInterface,
	ConfigurationAwareInterface,
	DatabaseAwareInterface,
	OutputWriterAwareInterface,
	ActionAwareInterface
{
	use LoggerAware;
	use DatabaseAware;
	use ConfigurationAware;
	use OutputWriterAware;
	use TableActionAware;
	use ActionAware;
	use StepAware;

	/**
	 * Hard-coded list of table data dump filter classes. This is for convenience during Unit Testing.
	 *
	 * @var  array
	 */
	protected $dataFilters = [
		NoViewData::class,
		SpecialEngines::class,
	];

	/**
	 * Hard-coded list of table row filter classes. This is for convenience during Unit Testing.
	 *
	 * @var  RowFilterInterface[]
	 */
	protected $rowFilters = [
	];

	/**
	 * Cache for the row filter object instances. Saves a ton of time since PHP doesn't have to create and destroy
	 * myriads of objects on each page load.
	 *
	 * @var  array
	 */
	protected $rowFilterInstances = [];

	/**
	 * Cache for the data dump filter object instances. Saves a ton of time since PHP doesn't have to create and destroy
	 * myriads of objects on each page load.
	 *
	 * @var  array
	 */
	protected $dataFilterInstances = [];

	/**
	 * Hard-coded list of per-table action classes. This is for my convenience.
	 *
	 * @var  array
	 */
	protected $perTableActions = [
		GetCreate::class,
	];

	/**
	 * The memory information helper, used to take decisions based on the available PHP memory
	 *
	 * @var  MemoryInfo
	 */
	protected $memoryInfo = null;

	/**
	 * The next table row we have to process
	 *
	 * @var  int
	 */
	protected $offset = 0;

	/**
	 * The determined batch size of the table
	 *
	 * @var  int
	 */
	protected $batch = 1;

	/**
	 * The metadata of the table we are processing
	 *
	 * @var  TableMeta
	 */
	protected $tableMeta = null;

	/**
	 * The metadata for the columns of the table
	 *
	 * @var  Column[]
	 */
	protected $columnsMeta = [];

	/**
	 * The names of the columns which are my primary key
	 *
	 * @var  string[]
	 */
	protected $pkColumns = [];

	/**
	 * Which table column is the auto-increment one? If there is one we'll use it in the SELECT query to ensure
	 * consistency of results.
	 *
	 * @var  string
	 */
	protected $autoIncrementColumn = '';

	/**
	 * Autoincrement column value of the last row processed.
	 *
	 * If a table as an auto-increment column we store its value for the last row we successfully processed. This allows
	 * us to optimize the SQL SELECT query for the next backup step on this table.
	 *
	 * @var  int
	 */
	protected $lastAutoIncrement = 0;

	/**
	 * The prototype INSERT INTO SQL query
	 *
	 * @var  string
	 */
	private $protoSQL;

	/**
	 * The tuples of data we need to write out to disk
	 *
	 * @var  array
	 */
	private $data;

	/**
	 * The length of the SQL INSERT query which will be written out to disk.
	 *
	 * This is the length of the prototype SQL query, plus the length of the tuples, plus two bytes for each tuple to
	 * take into account the comma and space separating tuples. If you are careful you might have observed this is one
	 * byte too many for the final query (since it ends in a semicolon). However, it's followed by a newline, therefore
	 * the total size is still correct. That's a non-obvious optimization so I thought I should explain it.
	 *
	 * @var  int
	 */
	private $dataLength;

	/**
	 * Table constructor.
	 *
	 * @param   TimerInterface  $timer        The timer object that controls us
	 * @param   Driver          $db           The database we are operating against
	 * @param   LoggerInterface $logger       The logger for our actions
	 * @param   Configuration   $config       The engine configuration
	 * @param   WriterInterface $outputWriter The writer for the output SQL file (can be null)
	 * @param   TableMeta       $tableMeta    The metadata of the table we will be processing
	 * @param   MemoryInfo      $memInfo      Memory info object, used for determining optimum batch size
	 */
	public function __construct(TimerInterface $timer, Driver $db, LoggerInterface $logger, Configuration $config, WriterInterface $outputWriter, TableMeta $tableMeta, MemoryInfo $memInfo)
	{
		$this->setLogger($logger);
		$this->setDriver($db);
		$this->setConfig($config);
		$this->setOutputWriter($outputWriter);

		$this->tableMeta  = $tableMeta;
		$this->memoryInfo = $memInfo;

		parent::__construct($timer, $config);
	}

	protected function prepare()
	{
		$this->getLogger()->info(sprintf("Backing up table “%s”", $this->tableMeta->getName()));

		// Get meta for columns
		$this->getLogger()->debug('Retrieving table column metadata');
		$this->columnsMeta = $this->getDbo()->getColumnsMeta($this->tableMeta->getName());

		// Run once-per-table callbacks.
		$this->runPerTableActions($this->perTableActions, $this->tableMeta, $this->columnsMeta, $this->getLogger(),
			$this->getOutputWriter(), $this->getDbo(), $this->getConfig());

		// Determine optimal batch size
		$memoryLimit      = $this->memoryInfo->getMemoryLimit();
		$usedMemory       = $this->memoryInfo->getMemoryUsage();
		$defaultBatchSize = $this->getConfig()->getMaxBatchSize();
		$this->batch      = $this->getOptimumBatchSize($this->tableMeta, $memoryLimit, $usedMemory, $defaultBatchSize);
		$this->offset     = 0;

		// Determine the columns which constitute a primary key
		$this->pkColumns = $this->findPrimaryKey($this->columnsMeta);

		// Determine the auto-increment column
		$this->autoIncrementColumn = $this->findAutoIncrementColumn($this->columnsMeta);

		// Create a prototype SQL INSERT statement without the actual value tuples
		$this->protoSQL = sprintf("INSERT INTO %s %s VALUES ",
			$this->db->qn($this->tableMeta->getName()),
			$this->getColumnListForInsert(array_keys($this->columnsMeta), $this->db)
		);

		// Initialize the data to write out
		$this->data       = [];
		$this->dataLength = 0;
	}

	/**
	 * Main processing. Here we dump the rows of the table. When we are done we return boolean false to indicate we are
	 * done processing. If more steps are required we return true instead.
	 *
	 * @return  bool
	 */
	protected function process()
	{
		// Get the maxquery size in a local variable instead of going continuously through the getter in the tight loop.
		$maxQuerySize = $this->config->getMaxQuerySize();

		// Log the next step
		$tableName = $this->tableMeta->getName();

		// Check data dump filters. If the data is filtered out return false, indicating we're done
		if (!$this->applyDataFilters($this->tableMeta, $this->dataFilters))
		{
			$this->logger->info(sprintf("Data of table “%s” will not be backed up.", $this->tableMeta->getName()));

			return false;
		}

		$this->logger->info(sprintf("Processing up to %d rows of table %s starting with row %d",
			$this->batch, $tableName, $this->offset + 1));

		/**
		 * Get the next batch of rows
		 *
		 * Why clone the database driver? Every time we run execute() the cursor inside the driver is overwritten. If
		 * we use the same driver for the SELECT query and for running any other query while the result is open we will
		 * end up overwriting our cursor the first time we run a different query. This will kill our loop prematurely
		 * and cause us to use too many iterations to process the data.
		 *
		 * By using a cloned driver we have multiple cursors open at the same time on the same connection. This lets us
		 * step through the records using one cursor (in $queryDb) and perform data modification queries, overwriting
		 * another cursor (in $db).
		 */
		$db      = $this->getDbo();
		$queryDb = clone $db;
		$timer   = $this->getTimer();
		$sql     = $this->getSelectQuery();

		$this->enforceSQLCompatibility();
		$queryDb->setQuery($sql, $this->offset, $this->batch);
		$this->setSubstep($tableName . ', record ' . $this->offset);

		// An error here *is* fatal, so we must NOT use a try/catch
		$cursor = $queryDb->execute();

		// Check how many rows we got. If zero, we are done processing the table.
		if ($queryDb->getNumRows($cursor) == 0)
		{
			$this->logger->info("No more data found in this table.");
			$db->freeResult($cursor);

			// If I'm done backing the table and I still have data to write out then please do so now.
			if (!empty($this->data))
			{
				$this->outputWriter->writeLine($this->protoSQL . implode(', ', $this->data) . ';');
				$this->data       = [];
				$this->dataLength = 0;
			}

			return false;
		}

		// Iterate every row as long as we have enough time.
		while ($timer->getTimeLeft() && ($row = $queryDb->fetchAssoc($cursor)))
		{
			$this->offset++;

			// Filter row
			if (!$this->applyRowFilters($tableName, $row, $this->rowFilters))
			{
				// Log the primary key identification of the filtered row
				$pkSig = '';

				foreach ($this->pkColumns as $c)
				{
					$v     = addcslashes($row[$c], "\\'");
					$pkSig = "$c = '$v' ,";
				}

				// Log the filtered row
				$this->getLogger()->debug(sprintf('Skipping row [%s] of table “%s”', substr($pkSig, 0, -2), $tableName));

				continue;
			}

			if (!empty($this->autoIncrementColumn))
			{
				$this->lastAutoIncrement = $row[$this->autoIncrementColumn];
			}

			// Convert the row data to a tuple
			$tuple = $this->toTuple($row, $db);

			/**
			 * If the current SQL INSERT INTO query plus the new tuple exceed my query size limit I will write it out to
			 * the disk.
			 */
			if ($this->dataLength + $this->rawByteLength($tableName) + 2 > $maxQuerySize)
			{
				$this->outputWriter->writeLine($this->protoSQL . implode(', ', $this->data) . ';');

				// Be kind to the memory
				unset($sql);

				// Reinitialize the data to write out.
				$this->data       = [];
				$this->dataLength = 0;
			}

			// The tuple has not been written to disk. Add it to the data to write out.
			$this->data[]     = $tuple;
			$this->dataLength += strlen($tuple) + 2;
		}

		unset($queryDb);

		// Indicate we have more work to do
		return true;
	}

	protected function finalize()
	{
		$this->getLogger()->info(sprintf("Finished backing up table “%s”", $this->tableMeta->getName()));
	}

	/**
	 * Apply the data dump filters on the table
	 *
	 * @param   TableMeta $table   The metadata of the table we are backing up
	 * @param   array     $filters The list of filter classes
	 *
	 * @return  bool  True if we can back up the table data
	 */
	protected function applyDataFilters(TableMeta $table, array $filters)
	{
		if (empty($this->dataFilterInstances))
		{
			foreach ($filters as $class)
			{
				if (!class_exists($class))
				{
					$this->addWarningMessage(sprintf("Data filter class “%s” not found. Is your installation broken?", $class));

					continue;
				}

				if (!in_array('ClassicPress\\SimpleDBBackup\\Engine\\Core\\Filter\\Data\\FilterInterface', class_implements($class)))
				{
					$this->addWarningMessage(sprintf("Filter class “%s” is not a valid data filter. Is your installation broken?", $class));

					continue;
				}

				/** @var DataFilterInterace $o */
				$this->dataFilterInstances[$class] = new $class($this->getLogger(), $this->getDbo(), $this->getConfig());
			}
		}

		/** @var DataFilterInterace $filter */
		foreach ($this->dataFilterInstances as $filter)
		{
			if (!$filter->filter($table))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Apply the per-row filters on the table
	 *
	 * @param   string $tableName The name of the table we're backing up
	 * @param   array  $row       The row we're currently backing up
	 * @param   array  $filters   The list of filter classes
	 *
	 * @return  bool
	 */
	protected function applyRowFilters($tableName, array $row, array $filters)
	{
		if (empty($this->rowFilterInstances))
		{
			foreach ($filters as $class)
			{
				if (!class_exists($class))
				{
					$this->addWarningMessage(sprintf("Row filter class “%s” not found. Is your installation broken?", $class));

					continue;
				}

				if (!in_array('ClassicPress\\SimpleDBBackup\\Engine\\Core\\Filter\\Row\\FilterInterface', class_implements($class)))
				{
					$this->addWarningMessage(sprintf("Filter class “%s” is not a valid row filter. Is your installation broken?", $class));

					continue;
				}

				/** @var DataFilterInterace $o */
				$this->rowFilterInstances[$class] = new $class($this->getLogger(), $this->getDbo(), $this->getConfig());
			}
		}

		/** @var RowFilterInterface $filter */
		foreach ($this->rowFilterInstances as $filter)
		{
			if (!$filter->filter($tableName, $row))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the optimum batch size for a table. This depends on the average row size of the table and the available
	 * PHP memory. If we have plenty of memory (or no limit) we are going to use the default batch size. The returned
	 * batch size can never be larger than the default batch size.
	 *
	 * @param   TableMeta $tableMeta        The metadata of the table. We are going to use the average row size.
	 * @param   int       $memoryLimit      How much PHP memory is available, 0 for no limit
	 * @param   int       $usedMemory       How much PHP memory is used, in bytes
	 * @param   int       $defaultBatchSize The default (and maximum) batch size
	 *
	 * @return  int
	 */
	protected function getOptimumBatchSize(TableMeta $tableMeta, $memoryLimit, $usedMemory, $defaultBatchSize = 1000)
	{
		// No memory limit? Return the default batch size
		if ($memoryLimit <= 0)
		{
			return $defaultBatchSize;
		}

		// Get the average row length. If it's unknown use the default batch size.
		$averageRowLength = $tableMeta->getAverageRowLength();

		if (empty($averageRowLength))
		{
			return $defaultBatchSize;
		}

		// Make sure the average row size is an integer
		$avgRow = str_replace([',', '.'], ['', ''], $averageRowLength);
		$avgRow = (int) $avgRow;

		// If the average row size is not a positive integer use the default batch size.
		if ($avgRow <= 0)
		{
			return $defaultBatchSize;
		}

		// The memory available for manipulating data is less than the free memory. The 0.75 factor is empirical.
		$memoryLeft = 0.75 * ($memoryLimit - $usedMemory);

		// This should never happen. I will return the default batch size and brace for impact: crash imminent!
		if ($memoryLeft <= 0)
		{
			$this->getLogger()->debug('Cannot determine optimal row size: PHP reports that its used memory is larger than the configured memory limit. This is NOT normal! I expect PHP to crash soon with an out of memory Fatal Error.');

			return $defaultBatchSize;
		}

		// The 3.25 factor is empirical and leans on the safe side.
		$maxRows = (int) ($memoryLeft / (3.25 * $avgRow));

		return max(1, min($maxRows, $defaultBatchSize));
	}

	/**
	 * Find the set of columns which constitute a primary key.
	 *
	 * We are returning whatever we find first: a primary key, a unique key, all columns listed
	 *
	 * @param   Column[] $columns
	 *
	 * @return  string[]
	 */
	protected function findPrimaryKey($columns)
	{
		// First try to find a Primary Key
		$ret = $this->findColumnsByIndex('PRI', $columns);

		if (!empty($ret))
		{
			return $ret;
		}

		// Next, try to find a Unique Key
		$ret = $this->findColumnsByIndex('UNI', $columns);

		if (!empty($ret))
		{
			return $ret;
		}

		// If all else fails use all of the columns
		$ret = [];

		foreach ($columns as $column)
		{
			$ret[] = $column->getColumnName();
		}

		return $ret;
	}

	/**
	 * Return a list of column names which belong to the named key
	 *
	 * @param   string   $keyName The key name to search for
	 * @param   Column[] $columns The list of columns to search in
	 *
	 * @return  string[]
	 */
	protected function findColumnsByIndex($keyName, $columns)
	{
		$ret = [];

		foreach ($columns as $column)
		{
			if ($column->getKeyName() == $keyName)
			{
				$ret[] = $column->getColumnName();
			}
		}

		return $ret;
	}

	/**
	 * Find the auto-increment column of the table
	 *
	 * @param   Column[] $columns
	 *
	 * @return  string
	 */
	protected function findAutoIncrementColumn(array $columns)
	{
		foreach ($columns as $column)
		{
			if ($column->isAutoIncrement())
			{
				return $column->getColumnName();
			}
		}

		return '';
	}

	/**
	 * Apply MySQL compatibility options to the database connection. We need them to prevent query failure unrelated to
	 * our code.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	protected function enforceSQLCompatibility()
	{
		$db = $this->getDbo();

		/**
		 * Enable the Big Selects option. Sometimes the MySQL optimizer believes that the number of rows which will be
		 * examined is too big and rejects the query. We know that our queries are big since we are inspecting large
		 * chunks of rows at one time and yes, we really do need to runt hat query, thank you very much. I am using two
		 * distinct syntax options to set this option since we try to support a large number of MySQL server versions.
		 */
		try
		{
			$db->setQuery('SET SQL_BIG_SELECTS=1')->execute();
			$db->setQuery('SET SESSION SQL_BIG_SELECTS=1')->execute();
			$db->execute();
		}
		catch (\Exception $e)
		{
		}
	}

	/**
	 * Get the SELECT query for this table
	 *
	 * @return  Query
	 */
	protected function getSelectQuery()
	{
		// Get the base query
		$db    = $this->getDbo();
		$query = $db->getQuery(true)
			->select('*')
			->from($db->qn($this->tableMeta->getName()));

		/**
		 * If we have an auto-increment column sort by it ascending (maintains consistency) and optimize the query
		 * performance by using it in a WHERE clause. The WHERE clause operates on the indexed auto-increment column
		 * which drastically reduces the time MySQL needs to seek to later columns on massive tables (millions of rows
		 * of data)
		 */
		if (!empty($this->autoIncrementColumn))
		{
			$query->where($db->qn($this->autoIncrementColumn) . '>' . $this->lastAutoIncrement);
			$query->order($db->qn($this->autoIncrementColumn) . ' ASC');
		}

		return $query;
	}

	/**
	 * Convert a single row in a tuple for use in a SQL INSERT statement
	 *
	 * @param   array  $row The row data to dumb
	 * @param   Driver $db  The database driver, used to escape the data
	 *
	 * @return  string
	 */
	protected function toTuple(array $row, Driver $db)
	{
		// Escape the raw data
		$row = array_map(function ($value) use ($db) {
			// Special consideration for NULL values
			return is_null($value) ? 'NULL' : $db->quote($value);
		}, $row);

		return '(' . implode(', ', $row) . ')';
	}

	/**
	 * Get the raw length of a string, in bytes.
	 *
	 * This requires the mbstring to be enabled. In this case we return the string length as if it were raw 8-bit ASCII
	 * characters, i.e. the raw byte length. If mbstring is not enabled we fall back to strlen() and hope your PHP
	 * version does not know how to handle multibyte Unicode characters, the default behaviour for PHP 5.6 and 7.x.
	 *
	 * @param   string $string The string to get the length of
	 *
	 * @return  int  The string length in bytes
	 */
	private function rawByteLength($string)
	{
		return function_exists('mb_strlen') ? mb_strlen($string, '8bit') : strlen($string);
	}

	/**
	 * Converts the table's column list into the columns argument for an INSERT INTO query.
	 *
	 * @param   array  $columns A list of column names
	 * @param   Driver $db      The database driver used to quote the field names
	 *
	 * @return  string
	 */
	private function getColumnListForInsert(array $columns, Driver $db)
	{
		return '(' . implode(',', array_map([$db, 'quoteName'], $columns)) . ')';
	}
}