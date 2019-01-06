<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core\Part;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\Helper\MemoryInfo;
use ClassicPress\SimpleDBBackup\Engine\Core\Part\Database;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;
use ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\Core\Part\TableSpy;
use ClassicPress\SimpleDBBackup\Tests\vfsAware;
use ClassicPress\SimpleDBBackup\Timer\Timer;
use ClassicPress\SimpleDBBackup\Timer\TimerInterface;
use ClassicPress\SimpleDBBackup\Writer\FileWriter;

/**
 * Since we are testing an engine part this is really an integration test, not a unit test. We have already tested the
 * "meat" of the engine through the unit tests. This is just making sure that when everything is thrown together it
 * _still_ works as intended.
 *
 * @package ClassicPress\SimpleDBBackup\Tests\Engine\Core\Part
 */
class DatabaseTest extends \PHPUnit_Extensions_Database_TestCase
{
	use vfsAware;

	protected function setUp()
	{
		parent::setUp();

		$this->setUp_vfsAware();
	}

	/**
	 * Runs before any tests from this class execute.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		// Get the schema filename based on the driver's database technology
		$schemaFilename = DBBACKUP_TEST_ROOT . '/_data/schema/engine_parts_test.sql';

		// Make sure the database tables exist
		$driver = Driver::getInstance([
			'driver'   => 'pdomysql',
			'database' => $_ENV['DB_NAME'],
			'host'     => $_ENV['DB_HOST'],
			'user'     => $_ENV['DB_USER'],
			'password' => $_ENV['DB_PASS'],
			'prefix'   => 'tst_',
			'select'   => true,
		]);
		$allQueries = file_get_contents($schemaFilename);
		$queries = Driver::splitSql($allQueries);

		foreach ($queries as $sql)
		{
			$sql = trim($sql);

			if (empty($sql))
			{
				continue;
			}

			try
			{
				$driver->setQuery($sql)->execute();
			}
			catch (\Exception $e)
			{
				echo "THE QUERY DIED\n\n$sql\n\n";
				echo $e->getMessage();

				throw $e;
			}
		}
	}

	/**
	 * Returns the default database connection for running the tests. This is the internal connection used by PHPUnit
	 * to do thing, like apply our data set. It is not used by the driver object being tested.
	 *
	 * @return  \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 */
	protected function getConnection()
	{
		$pdo = new \PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);

		return $this->createDefaultDBConnection($pdo, $_ENV['DB_NAME']);
	}

	/**
	 * Gets the data set to be loaded into the database during setup. This is applied to the database by PHPUnit.
	 *
	 * @return  \PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	protected function getDataSet()
	{
		return new \PHPUnit_Extensions_Database_DataSet_XmlDataSet(DBBACKUP_TEST_ROOT . '/_data/schema/engine_parts_test.xml');
	}

	/**
	 * @dataProvider providerEnginePart
	 */
	public function testEnginePart(array $replacements, $regularExpressions, $memLimit, $memUsage, $maxRuns,
								   $expectedTables)
	{
		TableSpy::$instanceParams = [];

		$timer        = $this->makeTimer();
		$db           = $this->makeDriver();
		$logger       = new NullLogger();
		$outWriter    = new FileWriter($this->root->url() . '/out.sql');
		$backupWriter = new FileWriter($this->root->url() . '/backup.sql');
		$config       = $this->makeConfiguration($replacements, $regularExpressions);
		$memoryInfo   = $this->makeMemoryInfo($memLimit, $memUsage);

		$part = new Database($timer, $db, $logger, $outWriter, $backupWriter, $config, $memoryInfo);
		$run  = 0;

		// Inject a stub/spy Table class name into the Database class
		$refObj = new \ReflectionObject($part);
		$refProp = $refObj->getProperty('tablePartClass');
		$refProp->setAccessible(true);
		$refProp->setValue($part, TableSpy::class);

		while (true)
		{
			$status = $part->tick();

			self::assertNull($status->getError(), "We should not get any errors!");

			$run++;
			self::assertLessThanOrEqual($maxRuns, $run, "Running the Engine Part should not exceed $maxRuns ticks.");

			if ($status->isDone())
			{
				break;
			}

			$timer->resetTime();
		}

		// Make sure we have the right number of tables
		self::assertCount(count($expectedTables), TableSpy::$instanceParams);

		// Get the names of the tables processed by our part
		$actualNames = [];

		/** @var Table $tableMeta */
		foreach (TableSpy::$instanceParams as $tableMeta)
		{
			$actualNames[] = $tableMeta->getName();
		}

		// Make sure the expected and actual table names match
		asort($actualNames);
		asort($expectedTables);

		self::assertEquals($expectedTables, $actualNames);
	}

	public static function providerEnginePart()
	{
		$memoryInfo = new MemoryInfo();

		return [
			'Plain text' => [
				// Replacements, RegularExpressions
				['BORG' => 'test'], false,
				// memLimit, memUsage
				10485760, 2621440,
				// maxRuns, expectedTables
				50, [
					'tst_large','tst_nontext', 'tst_partial', 'tst_table1', 'tst_table2', 'tst_table3',
				],
			],
			/**
			 * Well, this is gonna succeed anyway since I don't really check replacements, just whether the Table part
			 * is executed in a predictable manner.
			 */
			'RegEx' => [
				// Replacements, RegularExpressions
				['/(\w+)(bar)(\w+)/' => '${1}test${3}'], true,
				// memLimit, memUsage
				10485760, 2621440,
				// maxRuns, expectedTables
				50, [
					'tst_large','tst_nontext', 'tst_partial', 'tst_table1', 'tst_table2', 'tst_table3',
				],
			],
		];
	}


	/**
	 * @return  TimerInterface
	 */
	private function makeTimer()
	{
		$prophecy = $this->prophesize(Timer::class);
		$prophecy->willImplement(TimerInterface::class);
		$prophecy->getTimeLeft()->willReturn(5);
		$prophecy->getRunningTime()->willReturn(1);
		$prophecy->resetTime()->willReturn(null);

		return $prophecy->reveal();
	}

	/**
	 * @return Driver
	 */
	private function makeDriver()
	{
		return Driver::getInstance([
			'driver'   => 'pdomysql',
			'database' => $_ENV['DB_NAME'],
			'host'     => $_ENV['DB_HOST'],
			'user'     => $_ENV['DB_USER'],
			'password' => $_ENV['DB_PASS'],
			'prefix'   => 'tst_',
			'select'   => true,
		]);
	}

	/**
	 * @param   array  $replacements
	 * @param   bool   $regularExpressions
	 *
	 * @return Configuration
	 */
	private function makeConfiguration(array $replacements, $regularExpressions)
	{
		return new Configuration([
			'liveMode'           => false,
			'allTables'          => false,
			'maxBatchSize'       => 1000,
			'excludeTables'      => [
				'#__userfiltered',
			],
			'excludeRows'        => [
				'#__partial' => ['title'],
			],
			'regularExpressions' => $regularExpressions,
			'replacements'       => $replacements,
			'databaseCollation'  => '',
			'tableCollation'     => '',
		]);
	}

	/**
	 * @param $memLimit
	 * @param $memUsage
	 *
	 * @return MemoryInfo
	 */
	private function makeMemoryInfo($memLimit, $memUsage)
	{
		$prophecy = $this->prophesize(MemoryInfo::class);
		$prophecy->getMemoryLimit()->willReturn($memLimit);
		$prophecy->getMemoryUsage()->willReturn($memUsage);
		/** @var MemoryInfo $memoryInfo */
		$memoryInfo = $prophecy->reveal();

		return $memoryInfo;
	}

}
