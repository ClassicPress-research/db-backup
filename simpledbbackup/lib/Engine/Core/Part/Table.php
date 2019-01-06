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
use ClassicPress\SimpleDBBackup\Engine\Core\Action\Table\ActionAware as TableActionAware;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAware;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Row\FilterInterface as RowFilterInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Helper\MemoryInfo;
use ClassicPress\SimpleDBBackup\Engine\Core\OutputWriterAware;
use ClassicPress\SimpleDBBackup\Engine\Core\OutputWriterAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;
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
	ConfigurationAwareInterface,
	DatabaseAwareInterface,
	OutputWriterAwareInterface
{
	use LoggerAware;
	use DatabaseAware;
	use ConfigurationAware;
	use OutputWriterAware;
	use TableActionAware;
	use ActionAware;
	use StepAware;

	/**
	 * Hard-coded list of table column filter classes. This is for my convenience.
	 *
	 * @var  array
	 */
	protected $filters = [
	];

	/**
	 * Hard-coded list of table row filter classes. This is for my convenience.
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
	 * Hard-coded list of per-table action classes. This is for my convenience.
	 *
	 * @var  array
	 */
	protected $perTableActions = [
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
	 * The names of the columns which constitute the table's primary key
	 *
	 * @var  string[]
	 */
	protected $primaryKeyColumns = [];

	/**
	 * The names of the columns to which we will be applying replacements
	 *
	 * @var  string[]
	 */
	protected $replaceableColumns = [];

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
	 * Table constructor.
	 *
	 * @param   TimerInterface   $timer         The timer object that controls us
	 * @param   Driver           $db            The database we are operating against
	 * @param   LoggerInterface  $logger        The logger for our actions
	 * @param   Configuration    $config        The engine configuration
	 * @param   WriterInterface  $outputWriter  The writer for the output SQL file (can be null)
	 * @param   TableMeta        $tableMeta     The metadata of the table we will be processing
	 * @param   MemoryInfo       $memInfo       Memory info object, used for determining optimum batch size
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
		$this->getLogger()->info(sprintf("Starting to process replacements in table “%s”", $this->tableMeta->getName()));

		// Get meta for columns
		$this->getLogger()->debug('Retrieving table column metadata');
		$this->columnsMeta = $this->getDbo()->getColumnsMeta($this->tableMeta->getName());

		// Run once-per-table callbacks.
		$this->runPerTableActions($this->perTableActions, $this->tableMeta, $this->columnsMeta, $this->getLogger(),
			$this->getOutputWriter(), $this->getDbo(), $this->getConfig());

		$this->getLogger()->debug('Filtering the columns list');
		$this->replaceableColumns = $this->applyFilters($this->tableMeta, $this->columnsMeta, $this->filters);

		/**
		 * Are there no text columns left? This can happen in two ways:
		 *
		 * 1. Only non-text columns on the table, e.g. a glue table in a many-to-many table relationship
		 * 2. All text columns were filtered out by text filters
		 *
		 * In this case we mark ourselves as post-run and terminate early. Note that we use STATE_POSTRUN, not
		 * STATE_FINALIZED. That's because the call the nextState() in the abstract superclass will do the transition
		 * for us.
		 */
		if (empty($this->replaceableColumns))
		{
			$this->getLogger()->info(sprintf('Skipping table %s -- It does not have any text columns I can replace data into.', $this->tableMeta->getName()));
			$this->state = PartInterface::STATE_POSTRUN;
		}

		// Log columns to replace
		$this->getLogger()->debug(sprintf('Table %s replaceable columns: %s', $this->tableMeta->getName(), implode(', ', $this->replaceableColumns)));

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
	}

	protected function process()
	{
		// Log the next step
		$tableName = $this->tableMeta->getName();

		$this->logger->info(sprintf("Processing up to %d rows of table %s starting with row %d",
			$this->batch, $tableName, $this->offset + 1));

		/**
		 * Get the next batch of rows
		 *
		 * Why clone the database driver? Every time we run execute() the cursor inside the driver is overwritten. If
		 * we use the same driver for the SELECT query and for running data modification queries we will end up
		 * overwriting our cursor the first time we run a data modification query. This will kill our loop prematurely
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

			return false;
		}

		// Set up replacement
		$replacements         = $this->getConfig()->getReplacements();
		$isRegularExpressions = $this->getConfig()->isRegularExpressions();
		$liveMode             = $this->getConfig()->isLiveMode();

		// Iterate every row as long as we have enough time.
		while ($timer->getTimeLeft() && ($row = $queryDb->fetchAssoc($cursor)))
		{
			$this->offset++;

			$response = $this->processRow($tableName, $row, $this->replaceableColumns, $this->pkColumns, $replacements, $isRegularExpressions, $db);

			// Apply the action result
			$this->applyActionQueries($response, $this->getOutputWriter(), $this->getDbo(), $liveMode, true);

			// Be kind to the memory
			unset($response);
		}

		unset($queryDb);

		// Indicate we have more work to do
		return true;
	}

	protected function finalize()
	{
		$this->getLogger()->info(sprintf("Finished processing replacements in table “%s”", $this->tableMeta->getName()));
	}

	/**
	 * Apply the hard-coded list of column filters against the provided columns list and return a filtered list of
	 * strings, consisting of the column names which will we be replacing into.
	 *
	 * @param   TableMeta  $tableMeta    The metadata of the table we are filtering columns for
	 * @param   Column[]   $columnsMeta  The columns metadata we will be filtering
	 * @param   string[]   $filters      The filters to apply
	 *
	 * @return  string[]
	 */
	protected function applyFilters(TableMeta $tableMeta, array $columnsMeta, array $filters)
	{
		$allColumns = array_merge($columnsMeta);

		foreach ($filters as $class)
		{
			if (!class_exists($class))
			{
				$this->addWarningMessage(sprintf("Filter class “%s” not found. Is your installation broken?", $class));

				continue;
			}

			if (!in_array('ClassicPress\\SimpleDBBackup\\Engine\\Core\\Filter\\Column\\FilterInterface', class_implements($class)))
			{
				$this->addWarningMessage(sprintf("Filter class “%s” is not a valid column filter. Is your installation broken?", $class));

				continue;
			}

			/** @var FilterInterface $o */
			$o = new $class($this->getLogger(), $this->getDbo(), $this->getConfig());
			$allColumns = $o->filter($tableMeta, $allColumns);
		}

		$ret = [];

		if (empty($allColumns))
		{
			return $ret;
		}

		/** @var Column $column */
		foreach ($allColumns as $column)
		{
			$ret[] = $column->getColumnName();
		}

		return $ret;

	}

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

				/** @var FilterInterface $o */
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
	 * @param   TableMeta  $tableMeta         The metadata of the table. We are going to use the average row size.
	 * @param   int        $memoryLimit       How much PHP memory is available, 0 for no limit
	 * @param   int        $usedMemory        How much PHP memory is used, in bytes
	 * @param   int        $defaultBatchSize  The default (and maximum) batch size
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
		$memoryLeft  = 0.75 * ($memoryLimit - $usedMemory);

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
	 * @param   string    $keyName  The key name to search for
	 * @param   Column[]  $columns  The list of columns to search in
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
	 * @param   Column[]  $columns
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
		$db = $this->getDbo();

		/**
		 * We do not need to get all columns, just the PK and the columns to replace.
		 *
		 * Note that there might be an overlap between PK and replaceable columns, hence the array_unique.
		 */
		$columns = array_merge($this->pkColumns, $this->replaceableColumns);
		$columns = array_unique($columns);
		$columns = array_map([$db, 'quoteName'], $columns);

		// If we are selecting all columns it's best to use '*' (makes the query faster)
		if (count($columns) == count($this->columnsMeta))
		{
			$columns = '*';
		}

		// Get the base query
		$query = $db->getQuery(true)
			->select($columns)
			->from($db->qn($this->tableMeta->getName()));

		// If we have an auto-increment column sort by it ascending (maintains consistency)
		if (!empty($this->autoIncrementColumn))
		{
			$query->order($db->qn($this->autoIncrementColumn) . ' ASC');
		}

		return $query;
	}

	/**
	 * Process the replacements for a single row.
	 *
	 * @param   string  $tableName             The name of the table the row belongs to
	 * @param   array   $row                   The row data (PK and replaceable columns)
	 * @param   array   $replaceableColumns    Names of columns where data will be replaced to
	 * @param   array   $pkColumns             Names of columns which make up the table's primary key
	 * @param   array   $replacements          Replacements to do as [$from => $to, ...]
	 * @param   bool    $isRegularExpressions  Is the FROM side of replacements a regular expression?
	 * @param   Driver  $db                    The DB driver used to prepare the SQL queries
	 *
	 * @return  SQL
	 */
	protected function processRow($tableName, array $row, array $replaceableColumns, array $pkColumns,
	                              array $replacements, $isRegularExpressions, Driver $db)
	{
		// Apply row filtering
		if (!$this->applyRowFilters($tableName, $row, $this->rowFilters))
		{
			// Log the primary key identification of the filtered row
			$pkSig = '';

			foreach ($pkColumns as $c)
			{
				$v = addcslashes($row[$c], "\\'");
				$pkSig = "$c = '$v' ,";
			}

			// Log the filtered row
			$this->getLogger()->debug(sprintf('Skipping row [%s] of table `%s`', substr($pkSig, 0, -2), $tableName));

			// Return an empty response, skipping everything else
			$response = new SQL([], []);

			return $response;
		}

		$newRow  = array_merge($row);
		$changed = false;

		// Iterate columns, run the replacement against them
		foreach ($replaceableColumns as $column)
		{
			foreach ($replacements as $from => $to)
			{
				$newRow[$column] = Replacement::replace($newRow[$column], $from, $to, $isRegularExpressions);

				$changed = $changed || ($newRow[$column] != $row[$column]);
			}
		}

		// If the row has not been modified continue
		if (!$changed)
		{
			return new SQL([], []);
		}

		// TODO Add an option to convert UPDATE to UPDATE IGNORE for backup (recommended) and/or output SQL commands.
		$tableNameQuoted = $db->qn($tableName);
		$backupSQLProto  = "UPDATE {$tableNameQuoted} SET %s WHERE %s";
		$outputSQLProto  = "UPDATE {$tableNameQuoted} SET %s WHERE %s";
		$backupSet       = [];
		$outputSet       = [];
		$backupWhere     = [];
		$outputWhere     = [];

		// Get the SET part of the SQL commands based on the replaceable columns with different data
		foreach ($replaceableColumns as $column)
		{
			// Skip over unchanged columns
			if ($row[$column] == $newRow[$column])
			{
				continue;
			}

			// Backup sets the column to the CURRENT value since it needs to undo the replacement (new to old)
			$backupSet[] = $db->qn($column) . ' = ' . $db->q($row[$column]);
			// Output sets the column to the NEW value since it needs to perform the replacement (old to new)
			$outputSet[] = $db->qn($column) . ' = ' . $db->q($newRow[$column]);
		}

		// Get the WHERE clause based on the already determined PK columns
		foreach ($pkColumns as $column)
		{
			// Backup finds the column using its NEW value since it runs AFTER replacement
			$backupWhere[] = $db->qn($column) . ' = ' . $db->q($newRow[$column]);
			// Output finds the column using its CURRENT value since it runs BEFORE replacement
			$outputWhere[] = $db->qn($column) . ' = ' . $db->q($row[$column]);
		}

		// Be kind to the memory
		unset($row);
		unset($newRow);

		// Generate backup SQL
		$backupSQL = sprintf($backupSQLProto,
			implode(', ', $backupSet),
			'(' . implode(') AND (', $backupWhere) . ')'
		);

		// Be kind to the memory
		unset($backupSQLProto);
		unset($backupSet);
		unset($backupWhere);

		// Generate output SQL
		$outputSQL = sprintf($outputSQLProto,
			implode(', ', $outputSet),
			'(' . implode(') AND (', $outputWhere) . ')'
		);

		// Be kind to the memory
		unset($backupSQLProto);
		unset($backupSet);
		unset($backupWhere);

		// Create a response
		$response = new SQL([$outputSQL], [$backupSQL]);

		// Be kind to the memory
		unset($outputSQL);
		unset($backupSQL);

		return $response;
	}
}