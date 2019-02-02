<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core\Action\Table;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\Table\GetCreate;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;

class GetCreateTest extends \PHPUnit_Extensions_Database_TestCase
{
	/**
	 * Runs before any tests from this class execute.
	 *
	 * @return void
	 *
	 * @throws \Exception
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
	 * @dataProvider  providerProcessTable
	 *
	 * @param   string $tableName Name of table / view to test dump for
	 * @param   string $expected  The expected value
	 */
	public function testProcessTable($tableName, $expected)
	{
		// Get the DB driver
		$db = Driver::getInstance([
			'driver'   => 'pdomysql',
			'database' => $_ENV['DB_NAME'],
			'host'     => $_ENV['DB_HOST'],
			'user'     => $_ENV['DB_USER'],
			'password' => $_ENV['DB_PASS'],
			'prefix'   => 'tst_',
			'select'   => true,
		]);

		$tableMeta = $db->getTableMeta($tableName);
		$columns   = $db->getColumnsMeta($tableName);
		$logger    = new NullLogger();
		$config    = new Configuration([]);

		$getCreate = new GetCreate($db, $logger, $config);
		$sql       = $getCreate->processTable($tableMeta, $columns);
		$actual    = trim(implode("\n", $sql->getActionQueries()));

		self::assertEquals(self::squashSQL($expected), self::squashSQL($actual));
	}

	protected static function squashSQL($sql)
	{
		$replacements = [
			"\r" => '',
			"\n" => ' ',
			"\t" => ' ',
		];
		$sql          = str_replace(array_keys($replacements), array_values($replacements), $sql);

		while (strpos($sql, '  ') !== false)
		{
			$sql = str_replace("  ", " ", $sql);
		}

		return trim($sql);
	}

	public static function providerProcessTable()
	{
		return [
			'Simple MyISAM table tst_table1' => [
				'tst_table1', <<< SQL
CREATE TABLE `tst_table1` (
  `id`    int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50)      COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE=MyISAM
  AUTO_INCREMENT=5
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

SQL
			],

			'Simple InnoDB table tst_table2' => [
				'tst_table2', <<< SQL
CREATE TABLE `tst_table2` (
  `foo`   varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `title` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  PRIMARY KEY (`foo`,`title`)
)
  ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

SQL
			],

			'View tst_view' => [
				'tst_view', <<< SQL
CREATE OR REPLACE ALGORITHM=UNDEFINED VIEW `tst_view` AS
  select `tst_table1`.`id` AS `id`,`tst_table1`.`title` AS `title` from `tst_table1`;

SQL
			],

		];
	}
}