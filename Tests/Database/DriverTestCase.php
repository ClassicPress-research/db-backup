<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database;

use ClassicPress\SimpleDBBackup\Database\Driver;

/**
 * A test case for database drivers.
 *
 * Class DatabaseTestCase
 * @package ClassicPress\SimpleDBBackup\Tests\Database
 */
class DriverTestCase extends \PHPUnit_Extensions_Database_TestCase
{
	/**
	 * @var   string  The name of the database driver to instantiate
	 */
	protected static $driverName = 'throw_an_error';

	/**
	 * Get a fresh instance of the driver object being tested.
	 *
	 * @return Driver
	 */
	protected static function getDriver()
	{
		return Driver::getInstance([
			'driver'   => static::$driverName,
			'database' => $_ENV['DB_NAME'],
			'host'     => $_ENV['DB_HOST'],
			'user'     => $_ENV['DB_USER'],
			'password' => $_ENV['DB_PASS'],
			'prefix'   => 'akr_',
			'select'   => true,
		]);
	}

	/**
	 * Runs before any tests from this class execute.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		// Get the class name being tested
		$class = '\\ClassicPress\\SimpleDBBackup\\Database\\Driver\\' . ucfirst(static::$driverName);

		// If the driver is not supported mark the test as skipped
		if (!$class::isSupported())
		{
			$driverName = static::$driverName;
			static::markTestSkipped("Your current configuration does not support the $driverName database driver.");
		}

		// Get the schema filename based on the driver's database technology
		$schemaFilename = DBBACKUP_TEST_ROOT . '/_data/schema/' . strtolower($class::$dbtech) . '.sql';

		// Make sure the database tables exist
		$pdo = new \PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8", $_ENV['DB_USER'], $_ENV['DB_PASS']);
		$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, 0);
		$queries = file_get_contents($schemaFilename);
		$pdo->exec($queries);
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
		return new \PHPUnit_Extensions_Database_DataSet_XmlDataSet(DBBACKUP_TEST_ROOT . '/_data/schema/database.xml');
	}

}