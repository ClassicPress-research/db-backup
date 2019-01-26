<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core\Part;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\Helper\MemoryInfo;
use ClassicPress\SimpleDBBackup\Engine\Core\Part\Table;
use ClassicPress\SimpleDBBackup\Engine\PartInterface;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;
use ClassicPress\SimpleDBBackup\Tests\vfsAware;
use ClassicPress\SimpleDBBackup\Timer\Timer;
use ClassicPress\SimpleDBBackup\Timer\TimerInterface;
use ClassicPress\SimpleDBBackup\Writer\FileWriter;

class TableTest extends \PHPUnit_Extensions_Database_TestCase
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
		$driver     = Driver::getInstance([
			'driver'   => 'pdomysql',
			'database' => $_ENV['DB_NAME'],
			'host'     => $_ENV['DB_HOST'],
			'user'     => $_ENV['DB_USER'],
			'password' => $_ENV['DB_PASS'],
			'prefix'   => 'tst_',
			'select'   => true,
		]);
		$allQueries = file_get_contents($schemaFilename);
		$queries    = Driver::splitSql($allQueries);

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
	public function testEnginePart($table, $memLimit, $memUsage,
	                               $maxRuns, $expectedMaxBatch,
	                               $expectedOutSQL)
	{
		$outFile    = $this->root->url() . '/out.sql';
		$timer      = $this->makeTimer();
		$db         = $this->makeDriver();
		$logger     = new NullLogger();
		$outWriter  = new FileWriter($outFile);
		$config     = $this->makeConfiguration();
		$memoryInfo = $this->makeMemoryInfo($memLimit, $memUsage);
		$tableMeta  = $db->getTableMeta($table);

		$part = new Table($timer, $db, $logger, $config, $outWriter, $tableMeta, $memoryInfo);
		$run  = 0;

		while (true)
		{
			$status = $part->tick();

			self::assertNull($status->getError(), "We should not get any errors!");

			if ($part->getState() == PartInterface::STATE_PREPARED)
			{
				self::assertLessThanOrEqual($expectedMaxBatch, $this->getObjectAttribute($part, 'batch'), 'Unexpected batch size');
				self::assertEquals(0, $this->getObjectAttribute($part, 'offset'), 'After running prepare() the next query offset MUST be zero');
			}

			$run++;
			self::assertLessThanOrEqual($maxRuns, $run, "Running the Engine Part should not exceed $maxRuns ticks.");

			if ($status->isDone())
			{
				break;
			}

			$timer->resetTime();
		}

		// Check the resulting SQL
		$outSQL = array_map('trim', file($outFile));

		//echo var_export($outSQL, true) . "\n";

		self::assertEquals($expectedOutSQL, $outSQL);
	}

	public static function providerEnginePart()
	{
		$memoryInfo = new MemoryInfo();

		return [
			'Simple table' => [
				// Table
				'#__table1',
				// memLimit, memUsage
				10485760, 2621440,
				// $maxRuns, $expectedMaxBatch
				5, 1000,
				// $expectedOutSQL
				[
					0 => 'CREATE TABLE `tst_table1` (   `id` int(10) unsigned NOT NULL AUTO_INCREMENT,   `title` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,   PRIMARY KEY (`id`) ) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;',
					1 => 'INSERT INTO `tst_table1` (`id`,`title`) VALUES (\'1\', \'Foobar\'), (\'2\', \'More borging\'), (\'3\', \'My BORG\'), (\'4\', \'Foo bar baz\');',
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
	 * @return Configuration
	 */
	private function makeConfiguration()
	{
		return new Configuration([
			'liveMode'          => false,
			'allTables'         => false,
			'maxBatchSize'      => 1000,
			'excludeTables'     => [
				'#__userfiltered',
			],
			'excludeRows'       => [
				'#__partial' => ['title'],
			],
			'databaseCollation' => '',
			'tableCollation'    => '',
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
