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
	public function testEnginePart($table, array $replacements, $regularExpressions, $memLimit, $memUsage, $expectedColumns,
	                               $expectedPKColumns, $expectedAutoIncrementColumn, $maxRuns, $expectedMaxBatch,
	                               $expectedOutSQL, $expectedBackupSQL)
	{
		$outFile      = $this->root->url() . '/out.sql';
		$backupFile   = $this->root->url() . '/backup.sql';
		$timer        = $this->makeTimer();
		$db           = $this->makeDriver();
		$logger       = new NullLogger();
		$outWriter    = new FileWriter($outFile);
		$backupWriter = new FileWriter($backupFile);
		$config       = $this->makeConfiguration($replacements, $regularExpressions);
		$memoryInfo   = $this->makeMemoryInfo($memLimit, $memUsage);
		$tableMeta    = $db->getTableMeta($table);

		$part = new Table($timer, $db, $logger, $config, $outWriter, $backupWriter, $tableMeta, $memoryInfo);
		$run  = 0;

		while (true)
		{
			$status = $part->tick();

			self::assertNull($status->getError(), "We should not get any errors!");

			if ($part->getState() == PartInterface::STATE_PREPARED)
			{
				self::assertEquals($expectedColumns, $this->getObjectAttribute($part, 'replaceableColumns'), 'Unexpected columns with replaceable data');
				self::assertEquals($expectedPKColumns, $this->getObjectAttribute($part, 'pkColumns'), 'Unexpected primary key column(s)');
				self::assertEquals($expectedAutoIncrementColumn, $this->getObjectAttribute($part, 'autoIncrementColumn'), 'Unexpected primary key column');
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
		$outSQL    = array_map('trim', file($outFile));
		$backupSQL = array_map('trim', file($backupFile));

		// echo var_export($outSQL, true) . ",\n" . var_export($backupSQL, true) . ",\n\n";

		self::assertEquals($expectedOutSQL, $outSQL);
		self::assertEquals($expectedBackupSQL, $backupSQL);
	}

	public static function providerEnginePart()
	{
		$memoryInfo = new MemoryInfo();

		return [
			'Plain text replacement, numeric key' => [
				// Table, Replacements, RegularExpressions
				'#__table1', ['BORG' => 'test'], false,
				// memLimit, memUsage
				10485760, 2621440,
				// $expectedColumns, $expectedPKColumns, $expectedAutoIncrementColumn,
				[ 'title' ], ['id'], 'id',
				// $maxRuns, $expectedMaxBatch
				5, 1000,
				// $expectedOutSQL, $expectedBackupSQL
				[
					'UPDATE `tst_table1` SET `title` = \'My test\' WHERE (`id` = \'3\');'
				],
				[
					'UPDATE `tst_table1` SET `title` = \'My BORG\' WHERE (`id` = \'3\');'
				]
			],
			'Plain text replacement, composite key' => [
				// Table, Replacements, RegularExpressions
				'#__table2', ['BORG' => 'test'], false,
				// memLimit, memUsage
				10485760, 2621440,
				// $expectedColumns, $expectedPKColumns, $expectedAutoIncrementColumn,
				[ 'foo', 'title' ], [ 'foo', 'title' ], null,
				// $maxRuns, $expectedMaxBatch
				5, 1000,
				// $expectedOutSQL, $expectedBackupSQL
				array(
					'UPDATE `tst_table2` SET `foo` = \'test bar\' WHERE (`foo` = \'BORG bar\') AND (`title` = \'Baz\');',
					'UPDATE `tst_table2` SET `title` = \'test\' WHERE (`foo` = \'Forg\') AND (`title` = \'BORG\');',
				),
				array(
					'UPDATE `tst_table2` SET `foo` = \'BORG bar\' WHERE (`foo` = \'test bar\') AND (`title` = \'Baz\');',
					'UPDATE `tst_table2` SET `title` = \'BORG\' WHERE (`foo` = \'Forg\') AND (`title` = \'test\');',
				),
			],
			'Plain text replacement, string key, serialized data' => [
				// Table, Replacements, RegularExpressions
				'#__table3', ['BORG' => 'test'], false,
				// memLimit, memUsage
				10485760, 2621440,
				// $expectedColumns, $expectedPKColumns, $expectedAutoIncrementColumn,
				[ 'key', 'serialized' ], [ 'key' ], null,
				// $maxRuns, $expectedMaxBatch
				5, 1000,
				// $expectedOutSQL, $expectedBackupSQL
				array (
					'UPDATE `tst_table3` SET `key` = \'stdClass with “Just testing”\', `serialized` = \'O:8:\\"stdClass\\":4:{s:3:\\"foo\\";s:0:\\"\\";s:3:\\"bar\\";s:12:\\"Just testing\\";s:3:\\"bat\\";s:4:\\"dorg\\";s:12:\\"Just testing\\";s:4:\\"morg\\";}\' WHERE (`key` = \'stdClass with “Just BORGing”\');',
					'UPDATE `tst_table3` SET `key` = \'SomeRandomClass with “Just testing”\', `serialized` = \'O:15:\\"SomeRandomClass\\":1:{s:20:\\" SomeRandomClass foo\\";s:12:\\"Just testing\\";}\' WHERE (`key` = \'SomeRandomClass with “Just BORGing”\');',
					'UPDATE `tst_table3` SET `key` = \'array with “Just testing”\', `serialized` = \'a:4:{s:3:\\"foo\\";s:0:\\"\\";s:3:\\"bar\\";s:12:\\"Just testing\\";s:3:\\"bat\\";s:4:\\"dorg\\";s:12:\\"Just testing\\";s:4:\\"morg\\";}\' WHERE (`key` = \'array with “Just BORGing”\');',
				),
				array (
					'UPDATE `tst_table3` SET `key` = \'stdClass with “Just BORGing”\', `serialized` = \'O:8:\\"stdClass\\":4:{s:3:\\"foo\\";s:0:\\"\\";s:3:\\"bar\\";s:12:\\"Just BORGing\\";s:3:\\"bat\\";s:4:\\"dorg\\";s:12:\\"Just BORGing\\";s:4:\\"morg\\";}\' WHERE (`key` = \'stdClass with “Just testing”\');',
					'UPDATE `tst_table3` SET `key` = \'SomeRandomClass with “Just BORGing”\', `serialized` = \'O:15:\\"SomeRandomClass\\":1:{s:20:\\" SomeRandomClass foo\\";s:12:\\"Just BORGing\\";}\' WHERE (`key` = \'SomeRandomClass with “Just testing”\');',
					'UPDATE `tst_table3` SET `key` = \'array with “Just BORGing”\', `serialized` = \'a:4:{s:3:\\"foo\\";s:0:\\"\\";s:3:\\"bar\\";s:12:\\"Just BORGing\\";s:3:\\"bat\\";s:4:\\"dorg\\";s:12:\\"Just BORGing\\";s:4:\\"morg\\";}\' WHERE (`key` = \'array with “Just testing”\');',
				),
			],

			// TODO Perform tests with Regular Expressions

			'SPECIAL: Table with no replaceable columns' => [
				// Table, Replacements, RegularExpressions
				'#__nontext', ['BORG' => 'test'], false,
				// memLimit, memUsage
				10485760, 2621440,
				// $expectedColumns, $expectedPKColumns, $expectedAutoIncrementColumn,
				[], [ 'id' ], 'id',
				// $maxRuns, $expectedMaxBatch
				5, 1000,
				// $expectedOutSQL, $expectedBackupSQL
				[], []
			],
			'SPECIAL: Table with excluded columns' => [
				// Table, Replacements, RegularExpressions
				'#__partial', ['BORG' => 'test'], false,
				// memLimit, memUsage
				10485760, 2621440,
				// $expectedColumns, $expectedPKColumns, $expectedAutoIncrementColumn,
				[ 'something' ], ['id'], 'id',
				// $maxRuns, $expectedMaxBatch
				5, 1000,
				// $expectedOutSQL, $expectedBackupSQL
				array (
					'UPDATE `tst_partial` SET `something` = \'Just testing\' WHERE (`id` = \'7\');',
					'UPDATE `tst_partial` SET `something` = \'Just testing\' WHERE (`id` = \'13\');',
				),
				array (
					'UPDATE `tst_partial` SET `something` = \'Just BORGing\' WHERE (`id` = \'7\');',
					'UPDATE `tst_partial` SET `something` = \'Just BORGing\' WHERE (`id` = \'13\');',
				),
			],
			'SPECIAL: Large table, tight memory conditions' => [
				// Table, Replacements, RegularExpressions
				'#__large', ['BORG' => 'test'], false,
				// memLimit, memUsage
				10485760, 4194304,
				// $expectedColumns, $expectedPKColumns, $expectedAutoIncrementColumn,
				['name', 'something'], ['id'], 'id',
				// $maxRuns, $expectedMaxBatch
				16, 100,
				// $expectedOutSQL, $expectedBackupSQL
				array(
					'UPDATE `tst_large` SET `something` = \'Recusandae dolor test magnam aut. Mollitia quaerat vitae temporibus. Omnis qui quae rem molestiae aut\\n                similique id.\\n            \' WHERE (`id` = \'4\');',
					'UPDATE `tst_large` SET `something` = \'Adipisci id odio corrupti sit sint test ipsum. Cupiditate quaerat temporibus sed quia et. Deserunt labore\\n                nesciunt nostrum autem rerum. Est test eum quisquam magnam ratione doloribus.\\n            \' WHERE (`id` = \'160\');',
					'UPDATE `tst_large` SET `something` = \'Hic natus illum magnam nulla ullam unde voluptas. Labore odit id magni sint qui. Et ut sunt nemo\\n                voluptas occaecati. A test consequatur est sed eum. Expedita quidem atque natus non.\\n            \' WHERE (`id` = \'250\');',
				),
				array(
					'UPDATE `tst_large` SET `something` = \'Recusandae dolor BORG magnam aut. Mollitia quaerat vitae temporibus. Omnis qui quae rem molestiae aut\\n                similique id.\\n            \' WHERE (`id` = \'4\');',
					'UPDATE `tst_large` SET `something` = \'Adipisci id odio corrupti sit sint BORG ipsum. Cupiditate quaerat temporibus sed quia et. Deserunt labore\\n                nesciunt nostrum autem rerum. Est BORG eum quisquam magnam ratione doloribus.\\n            \' WHERE (`id` = \'160\');',
					'UPDATE `tst_large` SET `something` = \'Hic natus illum magnam nulla ullam unde voluptas. Labore odit id magni sint qui. Et ut sunt nemo\\n                voluptas occaecati. A BORG consequatur est sed eum. Expedita quidem atque natus non.\\n            \' WHERE (`id` = \'250\');',
				),
			],
			'SPECIAL: Large table, medium memory' => [
				// Table, Replacements, RegularExpressions
				'#__large', ['BORG' => 'test'], false,
				// memLimit, memUsage
				33554432, 4194304,
				// $expectedColumns, $expectedPKColumns, $expectedAutoIncrementColumn,
				[ 'name', 'something' ], ['id'], 'id',
				// $maxRuns, $expectedMaxBatch
				7, 350,
				// $expectedOutSQL, $expectedBackupSQL
				array(
					'UPDATE `tst_large` SET `something` = \'Recusandae dolor test magnam aut. Mollitia quaerat vitae temporibus. Omnis qui quae rem molestiae aut\\n                similique id.\\n            \' WHERE (`id` = \'4\');',
					'UPDATE `tst_large` SET `something` = \'Adipisci id odio corrupti sit sint test ipsum. Cupiditate quaerat temporibus sed quia et. Deserunt labore\\n                nesciunt nostrum autem rerum. Est test eum quisquam magnam ratione doloribus.\\n            \' WHERE (`id` = \'160\');',
					'UPDATE `tst_large` SET `something` = \'Hic natus illum magnam nulla ullam unde voluptas. Labore odit id magni sint qui. Et ut sunt nemo\\n                voluptas occaecati. A test consequatur est sed eum. Expedita quidem atque natus non.\\n            \' WHERE (`id` = \'250\');',
				),
				array(
					'UPDATE `tst_large` SET `something` = \'Recusandae dolor BORG magnam aut. Mollitia quaerat vitae temporibus. Omnis qui quae rem molestiae aut\\n                similique id.\\n            \' WHERE (`id` = \'4\');',
					'UPDATE `tst_large` SET `something` = \'Adipisci id odio corrupti sit sint BORG ipsum. Cupiditate quaerat temporibus sed quia et. Deserunt labore\\n                nesciunt nostrum autem rerum. Est BORG eum quisquam magnam ratione doloribus.\\n            \' WHERE (`id` = \'160\');',
					'UPDATE `tst_large` SET `something` = \'Hic natus illum magnam nulla ullam unde voluptas. Labore odit id magni sint qui. Et ut sunt nemo\\n                voluptas occaecati. A BORG consequatur est sed eum. Expedita quidem atque natus non.\\n            \' WHERE (`id` = \'250\');',
				),
			],
			'SPECIAL: Large table, ample memory' => [
				// Table, Replacements, RegularExpressions
				'#__large', ['BORG' => 'test'], false,
				// memLimit, memUsage
				134217728, 4194304,
				// $expectedColumns, $expectedPKColumns, $expectedAutoIncrementColumn,
				[ 'name', 'something' ], ['id'], 'id',
				// $maxRuns, $expectedMaxBatch
				5, 1000,
				// $expectedOutSQL, $expectedBackupSQL
				array(
					'UPDATE `tst_large` SET `something` = \'Recusandae dolor test magnam aut. Mollitia quaerat vitae temporibus. Omnis qui quae rem molestiae aut\\n                similique id.\\n            \' WHERE (`id` = \'4\');',
					'UPDATE `tst_large` SET `something` = \'Adipisci id odio corrupti sit sint test ipsum. Cupiditate quaerat temporibus sed quia et. Deserunt labore\\n                nesciunt nostrum autem rerum. Est test eum quisquam magnam ratione doloribus.\\n            \' WHERE (`id` = \'160\');',
					'UPDATE `tst_large` SET `something` = \'Hic natus illum magnam nulla ullam unde voluptas. Labore odit id magni sint qui. Et ut sunt nemo\\n                voluptas occaecati. A test consequatur est sed eum. Expedita quidem atque natus non.\\n            \' WHERE (`id` = \'250\');',
				),
				array(
					'UPDATE `tst_large` SET `something` = \'Recusandae dolor BORG magnam aut. Mollitia quaerat vitae temporibus. Omnis qui quae rem molestiae aut\\n                similique id.\\n            \' WHERE (`id` = \'4\');',
					'UPDATE `tst_large` SET `something` = \'Adipisci id odio corrupti sit sint BORG ipsum. Cupiditate quaerat temporibus sed quia et. Deserunt labore\\n                nesciunt nostrum autem rerum. Est BORG eum quisquam magnam ratione doloribus.\\n            \' WHERE (`id` = \'160\');',
					'UPDATE `tst_large` SET `something` = \'Hic natus illum magnam nulla ullam unde voluptas. Labore odit id magni sint qui. Et ut sunt nemo\\n                voluptas occaecati. A BORG consequatur est sed eum. Expedita quidem atque natus non.\\n            \' WHERE (`id` = \'250\');',
				),
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
