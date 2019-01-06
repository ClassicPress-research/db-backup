<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Database\Metadata\Database;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table;

class DriverTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var    Driver
	 */
	protected $instance;

	/**
	 * A store to track if logging is working.
	 *
	 * @var    array
	 */
	protected $logs;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		require_once DBBACKUP_TEST_ROOT . '/Stubs/Database/Driver/Fake.php';
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp($resetContainer = true)
	{
		$this->instance = new Driver\Fake([
			'database'  => 'mightymouse',
			'prefix'    => 'kot_',
			'nameQuote' => '[]',
		]);
	}

	/**
	 * Tears down the fixture.
	 *
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */
	protected function tearDown()
	{
		// We need this to be empty.
	}

	public function test__call()
	{
		self::assertEquals($this->instance->q('foo'), $this->instance->quote('foo'), 'q() is an alias of quote()');

		self::assertEquals($this->instance->qn('foo'), $this->instance->quoteName('foo'), 'qn() is an alias of quoteName()');

		self::assertNull($this->instance->foobar('foo'), 'Unknown aliases return null');
	}

	public function test__construct()
	{
		$options = [
			'database' => 'mightymouse',
			'prefix'   => 'kot_',
		];

		$dummy = new Driver\Fake($options);

		$actualOptions = $this->getObjectAttribute($dummy, 'options');

		self::assertArrayHasKey('database', $actualOptions);
		self::assertEquals('mightymouse', $actualOptions['database']);

		self::assertArrayHasKey('prefix', $actualOptions);
		self::assertEquals('kot_', $actualOptions['prefix']);
	}

	public function testGetInstance()
	{
		$optionsOne = [
			'driver'   => 'fake',
			'database' => 'mightymouse',
			'prefix'   => 'kot_',
		];

		$optionsTwo = [
			'driver'   => 'fake',
			'database' => 'dangermouse',
			'prefix'   => 'dng_',
		];

		$driverOne = Driver::getInstance($optionsOne);
		$driverTwo = Driver::getInstance($optionsTwo);

		self::assertInstanceOf('\\ClassicPress\\SimpleDBBackup\\Database\\Driver\\Fake', $driverOne, 'Driver::getInstance() must return correct subclass (test #1)');
		self::assertInstanceOf('\\ClassicPress\\SimpleDBBackup\\Database\\Driver\\Fake', $driverTwo, 'Driver::getInstance() must return correct subclass (test #2)');

		self::assertNotSame($driverOne, $driverTwo, 'Using different options must return different driver objects');

		$driverThree = Driver::getInstance($optionsTwo);
		self::assertSame($driverTwo, $driverThree, 'Using the same options must always return the same object');

		$actualOptions = $this->getObjectAttribute($driverOne, 'options');
		self::assertEquals('mightymouse', $actualOptions['database'], 'Driver options must be passed correctly to each constructor (test #1)');

		$actualOptions = $this->getObjectAttribute($driverTwo, 'options');
		self::assertEquals('dangermouse', $actualOptions['database'], 'Driver options must be passed correctly to each constructor (test #2)');
	}

	public function testGetConnection()
	{
		self::assertNull($this->instance->getConnection());
	}

	public function testGetConnectors()
	{
		$connectors = $this->instance->getConnectors();
		self::assertContains(
			'Mysqli',
			$connectors,
			'The getConnectors method should return an array with Mysqli as an available option.'
		);
	}

	public function testGetCount()
	{
		self::assertEquals(0, $this->instance->getCount());
	}

	public function testGetDatabase()
	{
		self::assertEquals('mightymouse', $this->instance->getDatabase());
	}

	public function testGetDatabaseFromOptions()
	{
		$refObj = new \ReflectionObject($this->instance);
		$refProp = $refObj->getProperty('_database');
		$refProp->setAccessible(true);
		$refProp->setValue($this->instance, '');

		self::assertEquals('mightymouse', $this->instance->getDatabase());
		self::assertEquals('mightymouse', $this->getObjectAttribute($this->instance, '_database'));
	}

	public function testGetDatabaseFromConnection()
	{
		$db = $this->getRealDatabaseConnection();

		// I need to do this manually since I'm about to kill all the connection information from the object!
		$db->connect();

		$refObj = new \ReflectionObject($db);

		$refDatabase = $refObj->getProperty('_database');
		$refDatabase->setAccessible(true);
		$refDatabase->setValue($db, '');

		$refOptions = $refObj->getProperty('options');
		$refOptions->setAccessible(true);
		$options = $refOptions->getValue($db);
		unset($options['database']);
		$refOptions->setValue($db, $options);

		self::assertEquals($_ENV['DB_NAME'], $db->getDatabase());
		self::assertEquals($_ENV['DB_NAME'], $this->getObjectAttribute($db, '_database'));
		$actualOptions = $this->getObjectAttribute($db, 'options');
		self::assertEquals($_ENV['DB_NAME'], $actualOptions['database']);
	}


	public function testGetDateFormat()
	{
		self::assertThat(
			$this->instance->getDateFormat(),
			$this->equalTo('Y-m-d H:i:s')
		);
	}

	public function testSplitSql()
	{
		self::assertThat(
			$this->instance->splitSql('SELECT * FROM #__foo;SELECT * FROM #__bar;'),
			$this->equalTo(
				[
					'SELECT * FROM #__foo;',
					'SELECT * FROM #__bar;',
				]
			),
			'splitSql method should split a string of multiple queries into an array.'
		);
	}

	public function testGetPrefix()
	{
		self::assertThat(
			$this->instance->getPrefix(),
			$this->equalTo('kot_')
		);
	}

	public function testGetNullDate()
	{
		self::assertThat(
			$this->instance->getNullDate(),
			$this->equalTo('1BC')
		);
	}

	public function testGetMinimum()
	{
		self::assertThat(
			$this->instance->getMinimum(),
			$this->equalTo('12.1'),
			'getMinimum should return a string with the minimum supported database version number'
		);
	}

	public function testIsMinimumVersion()
	{
		self::assertThat(
			$this->instance->isMinimumVersion(),
			$this->isTrue(),
			'isMinimumVersion should return a boolean true if the database version is supported by the driver'
		);
	}

	public function testSetDebug()
	{
		self::assertThat(
			$this->instance->setDebug(true),
			$this->isType('boolean'),
			'setDebug should return a boolean value containing the previous debug state.'
		);
	}

	public function testSetQuery()
	{
		self::assertThat(
			$this->instance->setQuery('SELECT * FROM #__dbtest'),
			$this->isInstanceOf('ClassicPress\SimpleDBBackup\Database\Driver'),
			'setQuery method should return an instance of ClassicPress\SimpleDBBackup\Database\Driver.'
		);
	}

	public function testReplacePrefix()
	{
		self::assertThat(
			$this->instance->replacePrefix('SELECT * FROM #__dbtest'),
			$this->equalTo('SELECT * FROM kot_dbtest'),
			'replacePrefix method should return the query string with the #__ prefix replaced by the actual table prefix.'
		);
	}

	public function testQuote()
	{
		self::assertThat(
			$this->instance->quote('test', false),
			$this->equalTo("'test'"),
			'Tests the without escaping.'
		);

		self::assertThat(
			$this->instance->quote('test'),
			$this->equalTo("'_test_'"),
			'Tests the with escaping (default).'
		);

		self::assertEquals(
			["'_test1_'", "'_test2_'"],
			$this->instance->quote(['test1', 'test2']),
			'Check that the array is quoted.'
		);
	}

	public function testQuoteBoolean()
	{
		self::assertThat(
			$this->instance->quote(true),
			$this->equalTo("'_1_'"),
			'Tests handling of boolean true with escaping (default).'
		);

		self::assertThat(
			$this->instance->quote(false),
			$this->equalTo("'__'"),
			'Tests handling of boolean false with escaping (default).'
		);

		self::assertThat(
			$this->instance->quote(null),
			$this->equalTo("'__'"),
			'Tests handling of null with escaping (default).'
		);

		self::assertThat(
			$this->instance->quote(42),
			$this->equalTo("'_42_'"),
			'Tests handling of integer with escaping (default).'
		);

		self::assertThat(
			$this->instance->quote(3.14),
			$this->equalTo("'_3.14_'"),
			'Tests handling of float with escaping (default).'
		);
	}

	public function testQuoteName()
	{
		self::assertThat(
			$this->instance->quoteName('test'),
			$this->equalTo('[test]'),
			'Tests the left-right quotes on a string.'
		);

		self::assertThat(
			$this->instance->quoteName('a.test'),
			$this->equalTo('[a].[test]'),
			'Tests the left-right quotes on a dotted string.'
		);

		self::assertThat(
			$this->instance->quoteName(['a', 'test']),
			$this->equalTo(['[a]', '[test]']),
			'Tests the left-right quotes on an array.'
		);

		self::assertThat(
			$this->instance->quoteName(['a.b', 'test.quote']),
			$this->equalTo(['[a].[b]', '[test].[quote]']),
			'Tests the left-right quotes on an array.'
		);

		self::assertThat(
			$this->instance->quoteName(['a.b', 'test.quote'], [null, 'alias']),
			$this->equalTo(['[a].[b]', '[test].[quote] AS [alias]']),
			'Tests the left-right quotes on an array.'
		);

		self::assertThat(
			$this->instance->quoteName(['a.b', 'test.quote'], ['alias1', 'alias2']),
			$this->equalTo(['[a].[b] AS [alias1]', '[test].[quote] AS [alias2]']),
			'Tests the left-right quotes on an array.'
		);

		self::assertThat(
			$this->instance->quoteName((object) ['a', 'test']),
			$this->equalTo(['[a]', '[test]']),
			'Tests the left-right quotes on an object.'
		);

		$refl     = new \ReflectionObject($this->instance);
		$property = $refl->getProperty('nameQuote');
		$property->setAccessible(true);
		$property->setValue($this->instance, '/');

		self::assertThat(
			$this->instance->quoteName('test'),
			$this->equalTo('/test/'),
			'Tests the uni-quotes on a string.'
		);
	}

	public function testTruncateTable()
	{
		self::assertNull(
			$this->instance->truncateTable('#__dbtest'),
			'truncateTable should not return anything if successful.'
		);
	}

	public function testGetDatabaseNameFromConnection()
	{
		$db = $this->getRealDatabaseConnection();

		$actual   = $db->getDatabaseNameFromConnection();
		$expected = $_ENV['DB_NAME'];
		self::assertEquals($expected, $actual);
	}

	public function testGetDatabaseMeta()
	{
		$db     = $this->getRealDatabaseConnection();
		$actual = $db->getDatabaseMeta();

		self::assertInstanceOf(Database::class, $actual);
		self::assertEquals($_ENV['DB_NAME'], $actual->getName());
		self::assertContains('utf8', $actual->getCollation());
		self::assertContains('utf8', $actual->getCharacterSet());
	}

	public function testGetDatabaseMetaNotExists()
	{
		$db     = $this->getRealDatabaseConnection();

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('The current database user does not have access to INFORMATION_SCHEMA or cannot query the metadata for database FooBarBazBat');

		$actual = $db->getDatabaseMeta('FooBarBazBat');
	}

	public function testGetTableMeta()
	{
		$db     = $this->getRealDatabaseConnection();

		$sql = <<< MYSQL
CREATE TABLE IF NOT EXISTS `akr_dbtest_formeta` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title`       varchar(50)      NOT NULL,
  `start_date`  datetime         NOT NULL,
  `description` varchar(255)     NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8_general_ci

MYSQL;
		$db->setQuery($sql)->execute();

		$actual = $db->getTableMeta('#__dbtest_formeta');

		self::assertInstanceOf(Table::class, $actual);
		self::assertEquals('akr_dbtest_formeta', $actual->getName());
		self::assertEquals('utf8_general_ci', $actual->getCollation());
		self::assertEquals('MEMORY', $actual->getEngine());
	}

	public function testGetTableMetaNotExists()
	{
		$db     = $this->getRealDatabaseConnection();

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Table ThisTableDoesNotExist does not exist in database ' . $this->getRealDatabaseConnection()->getDatabase() . ' or the current database user does not have permissions to retrieve its metadata');

		$actual = $db->getTableMeta('ThisTableDoesNotExist');
	}

	public function testGetColumnsMeta()
	{
		$db     = $this->getRealDatabaseConnection();

		$sql = <<< MYSQL
CREATE TABLE IF NOT EXISTS `akr_dbtest_formeta` (
  `id`          int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title`       varchar(50)      NOT NULL,
  `start_date`  datetime         NOT NULL,
  `description` varchar(255)     NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = MEMORY
  DEFAULT COLLATE = utf8_general_ci

MYSQL;
		$db->setQuery($sql)->execute();

		$actual = $db->getColumnsMeta('#__dbtest_formeta');

		self::assertCount(4, $actual);
		self::assertArrayHasKey('id', $actual);
		self::assertArrayHasKey('title', $actual);
		self::assertArrayHasKey('start_date', $actual);
		self::assertArrayHasKey('description', $actual);
		self::assertFalse($actual['id']->isText());
		self::assertTrue($actual['id']->isPK());
		self::assertTrue($actual['title']->isText());
		self::assertFalse($actual['title']->isPK());
	}

	public function testGetColumnsMetaNotExists()
	{
		$db     = $this->getRealDatabaseConnection();

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('Table ThisTableDoesNotExist does not exist in database ' . $this->getRealDatabaseConnection()->getDatabase() . ' or the current database user does not have permissions to retrieve its column metadata');

		$actual = $db->getColumnsMeta('ThisTableDoesNotExist');
	}

	/**
	 * Create a real database driver connected to our test database. This is required for some tests which definitely
	 * need to run against a real database.
	 *
	 * @return  Driver
	 */
	private function getRealDatabaseConnection()
	{
		// I need a real connection for this test.
		if (Driver\Mysql::isSupported())
		{
			$driverType = 'mysql';
		}
		elseif (Driver\Mysqli::isSupported())
		{
			$driverType = 'mysqli';
		}
		elseif (Driver\Pdomysql::isSupported())
		{
			$driverType = 'pdomysql';
		}
		else
		{
			$this->markTestIncomplete('No supported database driver found');
		}

		$db = Driver::getInstance([
			'driver'   => $driverType,
			'database' => $_ENV['DB_NAME'],
			'host'     => $_ENV['DB_HOST'],
			'user'     => $_ENV['DB_USER'],
			'password' => $_ENV['DB_PASS'],
			'prefix'   => 'akr_',
			'select'   => true,
		]);

		return $db;
	}
}