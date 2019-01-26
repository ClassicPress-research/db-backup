<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Action\Table;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Database\Metadata\Column;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table as TableMeta;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\ActionAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;

trait ActionAware
{
	/**
	 * @param   array            $perTableActionClasses  The list of per table action classes to use
	 * @param   TableMeta        $tableMeta              The metadata of the table to process
	 * @param   Column[]         $columns                The column metadata of the table to process
	 * @param   LoggerInterface  $logger                 The logger to use
	 * @param   WriterInterface  $outputWriter           The output writer to use
	 * @param   Driver           $db                     The database to execute SQL against
	 * @param   Configuration    $config                 The Configuration object to use
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	protected function runPerTableActions(array $perTableActionClasses,
	                                      TableMeta $tableMeta, array $columns,
	                                      LoggerInterface $logger, WriterInterface $outputWriter,
	                                      Driver $db, Configuration $config)
	{
		if (empty($perTableActionClasses))
		{
			$logger->info("No actions to be performed on the table itself.");

			return;
		}

		$logger->info("Processing actions to be performed on the table itself.");

		foreach ($perTableActionClasses as $class)
		{
			$this->runPerTableAction($class, $tableMeta, $columns, $logger, $outputWriter,
				$db, $config);
		}
	}

	/**
	 * Runs a table action given an action class name and returns the number of action queries generated
	 *
	 * @param   string           $class         The action class to create an object from
	 * @param   TableMeta        $tableMeta     The metadata of the table to process
	 * @param   Column[]         $columns       The column metadata of the table to process
	 * @param   LoggerInterface  $logger        The logger to use
	 * @param   WriterInterface  $outputWriter  The output writer to use
	 * @param   Driver           $db            The database to execute SQL against
	 * @param   Configuration    $config        The Configuration object to use
	 *
	 * @return  int
	 */
	protected function runPerTableAction($class, TableMeta $tableMeta, array $columns, LoggerInterface $logger,
	                                     WriterInterface $outputWriter, Driver $db,
	                                     Configuration $config)
	{
		if (!class_exists($class))
		{
			if ($this instanceof WarningsAwareInterface)
			{
				$this->addWarningMessage(sprintf("Action class “%s” does not exist", $class));
			}

			return 0;
		}

		if (!in_array('ClassicPress\SimpleDBBackup\Engine\Core\Action\Table\ActionInterface', class_implements($class)))
		{
			if ($this instanceof WarningsAwareInterface)
			{
				$this->addWarningMessage(sprintf("Action class “%s” is not a valid per-table action", $class));
			}

			return 0;
		}

		$classParts = explode('\\', $class);
		$baseClass  = array_pop($classParts);

		$logger->debug(sprintf("Running “%s” action class against table “%s”.", $baseClass, $tableMeta->getName()));


		/** @var ActionInterface $o */
		$o        = new $class($db, $logger, $config);
		$response = $o->processTable($tableMeta, $columns);

		if ($this instanceof ActionAwareInterface)
		{
			return $this->applyActionQueries($response, $outputWriter);
		}

		return 0;
	}
}