<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database\Driver;

use ClassicPress\SimpleDBBackup\Tests\Database\DriverTestCase;

class MysqliTest extends DriverTestCase
{
	/**
	 * @var   string  The name of the database driver to instantiate
	 */
	protected static $driverName = 'mysqli';

	/**
	 * Test connected method.
	 *
	 * @return  void
	 */
	public function testConnected()
	{
		$driver = static::getDriver();

		$driver->connect();
		self::assertTrue($driver->connected(), 'Driver must report itself connected after successful connection');

		$driver->disconnect();
		self::assertFalse($driver->connected(), 'Driver must report itself disconnected after successful disconnection');
	}

	/**
	 * Tests the dropTable method.
	 *
	 * @return  void
	 */
	public function testDropTable()
	{
		$driver = static::getDriver();

		// Make sure dropping a table (if it exists) works
		self::assertThat(
			$driver->dropTable('#__bar', true),
			$this->isInstanceOf(get_class($driver)),
			'The table is dropped if present.'
		);

		// Make sure dropping a table which doesn't exist errors out with an exception
		$this->expectExceptionMessage('SQL=DROP TABLE `akr_bar`');
		$driver->dropTable('#__bar', false);
	}

	/**
	 * Tests the escape method.
	 *
	 * @param   string  $text     The string to be escaped.
	 * @param   boolean $extra    Optional parameter to provide extra escaping.
	 * @param   string  $expected The expected result.
	 *
	 * @return  void
	 *
	 * @dataProvider  \ClassicPress\SimpleDBBackup\Tests\Database\MySQLProvider::testEscapeProvider()
	 */
	public function testEscape($text, $extra, $expected)
	{
		$driver = static::getDriver();

		self::assertEquals($expected, $driver->escape($text, $extra));
	}

	/**
	 * Test getAffectedRows method.
	 *
	 * @return  void
	 */
	public function testGetAffectedRows()
	{
		$driver = static::getDriver();

		$query = $driver->getQuery(true);
		$query->delete();
		$query->from('#__dbtest');

		$driver->setQuery($query);
		$driver->execute();

		self::assertEquals(4, $driver->getAffectedRows());
	}

	/**
	 * Test getCollation method.
	 *
	 * @return  void
	 */
	public function testGetCollation()
	{
		$driver = static::getDriver();

		self::assertEquals('utf8mb4_unicode_520_ci', $driver->getCollation(), 'The getCollation method should return the collation of the database, not the first created table.');
	}

	/**
	 * Test getNumRows method.
	 *
	 * @return  void
	 */
	public function testGetNumRows()
	{
		$driver = static::getDriver();

		$query = $driver->getQuery(true);
		$query->select('*');
		$query->from('#__dbtest');
		$query->where('description = ' . $driver->quote('one'));
		$driver->setQuery($query);

		$res = $driver->execute();

		self::assertEquals(2, $driver->getNumRows($res));
	}

	/**
	 * Tests the getTableCreate method.
	 *
	 * @return  void
	 */
	public function testGetTableCreate()
	{
		$driver = static::getDriver();
		$create = $driver->getTableCreate('#__dbtest');

		self::assertInternalType('array', $create, 'The statement to create the table is returned in an array.');
		self::assertCount(1, $create, 'Only one element must be present in the CREATE TABLE results array');
		self::assertArrayHasKey('#__dbtest', $create, 'The CREATE TABLE array must be keyed against the table name.');
		self::assertContains('CREATE TABLE `akr_dbtest`', $create['#__dbtest'], 'The CREATE TABLE result must contain DDL for the requested table.');
	}

	/**
	 * Test getTableColumns method.
	 *
	 * @return  void
	 */
	public function testGetTableColumns()
	{
		$driver = static::getDriver();
		$tableCol = array('id' => 'int unsigned', 'title' => 'varchar', 'start_date' => 'datetime', 'description' => 'varchar');

		self::assertEquals(
			$tableCol,
			$driver->getTableColumns('#__dbtest'),
			'The columns list must be accurate'
		);

		/* not only type field */
		$id = new \stdClass;
		$id->Default = null;
		$id->Field = 'id';
		$id->Type = 'int(10) unsigned';
		$id->Null = 'NO';
		$id->Key = 'PRI';
		$id->Collation = null;
		$id->Extra = 'auto_increment';
		$id->Privileges = 'select,insert,update,references';
		$id->Comment = '';

		$title = new \stdClass;
		$title->Default = null;
		$title->Field = 'title';
		$title->Type = 'varchar(50)';
		$title->Null = 'NO';
		$title->Key = '';
		$title->Collation = 'utf8mb4_unicode_520_ci';
		$title->Extra = '';
		$title->Privileges = 'select,insert,update,references';
		$title->Comment = '';

		$start_date = new \stdClass;
		$start_date->Default = null;
		$start_date->Field = 'start_date';
		$start_date->Type = 'datetime';
		$start_date->Null = 'NO';
		$start_date->Key = '';
		$start_date->Collation = null;
		$start_date->Extra = '';
		$start_date->Privileges = 'select,insert,update,references';
		$start_date->Comment = '';

		$description = new \stdClass;
		$description->Default = null;
		$description->Field = 'description';
		$description->Type = 'varchar(255)';
		$description->Null = 'NO';
		$description->Key = '';
		$description->Collation = 'utf8mb4_unicode_520_ci';
		$description->Extra = '';
		$description->Privileges = 'select,insert,update,references';
		$description->Comment = '';

		self::assertEquals(				array(
				'id'          => $id,
				'title'       => $title,
				'start_date'  => $start_date,
				'description' => $description
			),
			$driver->getTableColumns('#__dbtest', false),
			'The column metadata must be accurate'
		);
	}

	/**
	 * Tests the getTableKeys method.
	 *
	 * @return  void
	 */
	public function testGetTableKeys()
	{
		$driver = static::getDriver();

		$tableKeys = $driver->getTableKeys('#__dbtest');
		$expected  = array(
			0 =>
				(object) array(
					'Table'         => 'akr_dbtest',
					'Non_unique'    => '0',
					'Key_name'      => 'PRIMARY',
					'Seq_in_index'  => '1',
					'Column_name'   => 'id',
					'Collation'     => null,
					'Cardinality'   => '4',
					'Sub_part'      => null,
					'Packed'        => null,
					'Null'          => '',
					'Index_type'    => 'HASH',
					'Comment'       => '',
					'Index_comment' => '',
				),
		);

		self::assertInternalType('array', $tableKeys, 'The list of table keys for the table is returned in an array.');
		self::assertCount(1, $tableKeys, 'The number of table keys returned must be accurate.');
		self::assertEquals($expected, $tableKeys, 'The metadata of table keys returned must be accurate.');
	}

	/**
	 * Tests the getTableList method.
	 *
	 * @return  void
	 */
	public function testGetTableList()
	{
		$driver = static::getDriver();
		$tableList  = $driver->getTableList();

		self::assertInternalType('array', $tableList, 'The list of tables for the database is returned in an array.');

		/**
		 * Why not use assertArraySubset?
		 *
		 * Because it's looking for an exact subset. In an array [1,2,3,4] you can find the subset [1,2] but not the
		 * set [1,3] because 1 and 3 are not consecutive elements. Likewise, the subset [2,1] does not exist because
		 * the elements 2 and 1 are in the opposite order than the one specified in the subset. This caused this test
		 * to fail even though both tables in my subset were present in the $tableList result. However, it was not
		 * guaranteed they'd be in a specific order or without anything else between them. So, I'm back to using the
		 * good, old assertContains with one element at a time.
		 */
		self::assertContains('akr_dbtest', $tableList);
		self::assertContains('akr_dbtest_innodb', $tableList);
	}

	/**
	 * Test getVersion method.
	 *
	 * @return  void
	 */
	public function testGetVersion()
	{
		$driver = static::getDriver();
		$version = $driver->getVersion();
		self::assertGreaterThan(
			0,
			strlen($version),
			'The getVersion method should return something without error.'
		);
		self::assertTrue(version_compare($version, '5.0.0', 'ge'), 'The returned version must look like a MySQL / MariaDB version number of a supported MySQL-class database server.');
	}

	/**
	 * Test insertid method.
	 *
	 * @return  void
	 */
	public function testInsertid()
	{
		$driver = static::getDriver();
		$driver->truncateTable('#__dbtest');

		$query = $driver->getQuery(true)
			->insert('#__dbtest')
			->columns(array('title', 'start_date', 'description'))
			->values($driver->q('New record') . ', ' . $driver->q('2014-06-18 00:00:00') . ', ' . $driver->q('Something something something text'));
		$driver->setQuery($query)->execute();

		$insertId = $driver->insertid();

		self::assertEquals(1, $insertId);
	}

	/**
	 * Test loadAssoc method.
	 *
	 * @return  void
	 */
	public function testLoadAssoc()
	{
		$driver = static::getDriver();
		$query = $driver->getQuery(true);
		$query->select('title');
		$query->from('#__dbtest');
		$driver->setQuery($query);
		$result = $driver->loadAssoc();

		self::assertEquals(array('title' => 'Testing'), $result);
	}

	/**
	 * Test loadAssocList method.
	 *
	 * @return  void
	 */
	public function testLoadAssocList()
	{
		$driver = static::getDriver();
		$query = $driver->getQuery(true);
		$query->select('title');
		$query->from('#__dbtest');
		$driver->setQuery($query);
		$result = $driver->loadAssocList();

		self::assertEquals(
			array(
				array('title' => 'Testing'),
				array('title' => 'Testing2'),
				array('title' => 'Testing3'),
				array('title' => 'Testing4')
			),
			$result
		);
	}

	/**
	 * Test loadColumn method
	 *
	 * @return  void
	 */
	public function testLoadColumn()
	{
		$driver = static::getDriver();
		$query = $driver->getQuery(true);
		$query->select('title');
		$query->from('#__dbtest');
		$driver->setQuery($query);
		$result = $driver->loadColumn();

		self::assertEquals(array('Testing', 'Testing2', 'Testing3', 'Testing4'), $result);
	}

	/**
	 * Test loadObject method
	 *
	 * @return  void
	 */
	public function testLoadObject()
	{
		$driver = static::getDriver();
		$query = $driver->getQuery(true);
		$query->select('*');
		$query->from('#__dbtest');
		$query->where('description=' . $driver->quote('three'));
		$driver->setQuery($query);
		$result = $driver->loadObject();

		$objCompare = new \stdClass;
		$objCompare->id = 3;
		$objCompare->title = 'Testing3';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'three';

		self::assertEquals($objCompare, $result);
	}

	/**
	 * Test loadObjectList method
	 *
	 * @return  void
	 */
	public function testLoadObjectList()
	{
		$driver = static::getDriver();
		$query = $driver->getQuery(true);
		$query->select('*');
		$query->from('#__dbtest');
		$query->order('id');
		$driver->setQuery($query);
		$result = $driver->loadObjectList();

		$expected = array();

		$objCompare = new \stdClass;
		$objCompare->id = 1;
		$objCompare->title = 'Testing';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'one';

		$expected[] = clone $objCompare;

		$objCompare = new \stdClass;
		$objCompare->id = 2;
		$objCompare->title = 'Testing2';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'one';

		$expected[] = clone $objCompare;

		$objCompare = new \stdClass;
		$objCompare->id = 3;
		$objCompare->title = 'Testing3';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'three';

		$expected[] = clone $objCompare;

		$objCompare = new \stdClass;
		$objCompare->id = 4;
		$objCompare->title = 'Testing4';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'four';

		$expected[] = clone $objCompare;

		self::assertEquals($expected, $result);
	}

	/**
	 * Test loadResult method
	 *
	 * @return  void
	 */
	public function testLoadResult()
	{
		$driver = static::getDriver();
		$query = $driver->getQuery(true);
		$query->select('id');
		$query->from('#__dbtest');
		$query->where('title=' . $driver->quote('Testing2'));

		$driver->setQuery($query);
		$result = $driver->loadResult();

		self::assertEquals(2, $result);
	}

	/**
	 * Test loadRow method
	 *
	 * @return  void
	 */
	public function testLoadRow()
	{
		$driver = static::getDriver();
		$query = $driver->getQuery(true);
		$query->select('*');
		$query->from('#__dbtest');
		$query->where('description=' . $driver->quote('three'));
		$driver->setQuery($query);
		$result = $driver->loadRow();

		$expected = array(3, 'Testing3', '1980-04-18 00:00:00', 'three');

		self::assertEquals($expected, $result);
	}

	/**
	 * Test loadRowList method
	 *
	 * @return  void
	 */
	public function testLoadRowList()
	{
		$driver = static::getDriver();
		$query = $driver->getQuery(true);
		$query->select('*');
		$query->from('#__dbtest');
		$query->where('description=' . $driver->quote('one'));
		$driver->setQuery($query);
		$result = $driver->loadRowList();

		$expected = array(array(1, 'Testing', '1980-04-18 00:00:00', 'one'), array(2, 'Testing2', '1980-04-18 00:00:00', 'one'));

		self::assertThat($result, $this->equalTo($expected), __LINE__);
	}

	/**
	 * Tests the renameTable method.
	 *
	 * @return  void
	 */
	public function testRenameTable()
	{
		$driver = static::getDriver();
		$newTableName = 'bak_renamed';

		// Drop the cloned table if it already exists
		$driver->dropTable('#__for_rename', true);

		// Create a clone of the test table
		$tablesDDL = $driver->getTableCreate([
			'#__dbtest'
		]);
		$sql = str_replace('akr_dbtest', '#__for_rename', $tablesDDL['#__dbtest']);
		$driver->setQuery($sql)->execute();

		// Rename the cloned table
		$driver->renameTable('#__for_rename', $newTableName);

		// Check name change
		$tableList = $driver->getTableList();
		self::assertThat(in_array($newTableName, $tableList), $this->isTrue());

		// Restore initial state
		$driver->dropTable($newTableName, true);
	}

	/**
	 * Test the execute method
	 *
	 * @return  void
	 */
	public function testExecute()
	{
		$driver = static::getDriver();
		$driver->setQuery("REPLACE INTO `#__dbtest` SET `id` = 5, `title` = 'testTitle', `start_date` = '2018-09-12 00:00:00', `description` = 'Test'");

		self::assertTrue($driver->execute());

		self::assertEquals(5, $driver->insertid());
	}

	/**
	 * Test select method.
	 *
	 * @return  void
	 */
	public function testSelect()
	{
		$driver = static::getDriver();

		$this->expectExceptionMessage('Could not connect to database.');
		$driver->select('DOES_NOT_EXIST');
	}

	/**
	 * Test setUTF method.
	 *
	 * @return  void
	 */
	public function testSetUTF()
	{
		$driver = static::getDriver();
		$driver->setUTF();

		$query = "show variables like 'character_set_client'";
		$currentCharset = $driver->setQuery($query)->loadColumn(1);

		self::assertEquals('utf8', $currentCharset[0]);
	}

	/**
	 * Tests the transactionCommit method.
	 *
	 * @return  void
	 */
	public function testTransactionCommit()
	{
		$driver = static::getDriver();
		$driver->transactionStart();
		$queryIns = $driver->getQuery(true);
		$queryIns->insert('#__dbtest')
			->columns('id, title, start_date, description')
			->values("6, 'testTitle', '1970-01-01', 'testDescription'");

		$driver->setQuery($queryIns)->execute();

		$driver->transactionCommit();

		/* check if value is present */
		$queryCheck = $driver->getQuery(true);
		$queryCheck->select('*')
			->from('#__dbtest')
			->where('id = 6');
		$driver->setQuery($queryCheck);
		$result = $driver->loadRow();

		$expected = array('6', 'testTitle', '1970-01-01 00:00:00', 'testDescription');

		self::assertEquals($expected, $result);
	}

	/**
	 * Tests the transactionRollback method, with and without savepoint.
	 *
	 * @param   string $toSavepoint Savepoint name to rollback transaction to
	 * @param   int    $tupleCount  Number of tuple found after insertion and rollback
	 *
	 * @return  void
	 *
	 * @dataProvider \ClassicPress\SimpleDBBackup\Tests\Database\MySQLProvider::testTransactionRollbackProvider()
	 */
	public function testTransactionRollback($toSavepoint, $tupleCount)
	{
		$driver = static::getDriver();
		$driver->transactionStart();

		/* try to insert this tuple, inserted only when savepoint != null */
		$queryIns = $driver->getQuery(true);
		$queryIns->insert('#__dbtest_innodb')
			->columns('id, title, start_date, description')
			->values("7, 'testRollback', '1970-01-01', 'testRollbackSp'");
		$driver->setQuery($queryIns)->execute();

		/* create savepoint only if is passed by data provider */
		if (!is_null($toSavepoint))
		{
			$driver->transactionStart((boolean)$toSavepoint);
		}

		/* try to insert this tuple, always rolled back */
		$queryIns = $driver->getQuery(true);
		$queryIns->insert('#__dbtest_innodb')
			->columns('id, title, start_date, description')
			->values("8, 'testRollback', '1972-01-01', 'testRollbackSp'");
		$driver->setQuery($queryIns)->execute();

		$driver->transactionRollback((boolean)$toSavepoint);

		/* release savepoint and commit only if a savepoint exists */
		if (!is_null($toSavepoint))
		{
			$driver->transactionCommit();
		}

		/* find how many rows have description='testRollbackSp' :
		 *   - 0 if a savepoint doesn't exist
		 *   - 1 if a savepoint exists
		 */
		$queryCheck = $driver->getQuery(true);
		$queryCheck->select('*')
			->from('#__dbtest_innodb')
			->where("description = 'testRollbackSp'");
		$driver->setQuery($queryCheck);
		$result = $driver->loadRowList();

		self::assertThat(count($result), $this->equalTo($tupleCount), __LINE__);
	}

	/**
	 * Test isSupported method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testIsSupported()
	{
		$driver = static::getDriver();
		self::assertThat(\ClassicPress\SimpleDBBackup\Database\Driver\Mysqli::isSupported(), $this->isTrue(), __LINE__);
	}

	/**
	 * Test insertObject method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testInsertObject()
	{
		$driver = static::getDriver();
		$sampleData = (object)array(
			'id'          => null,
			'title'       => 'test_insert',
			'start_date'  => '2014-06-17 00:00:00',
			'description' => 'Testing object insert'
		);
		$table = '#__dbtest';
		$db = $driver;

		// Inserting really adds to database
		$db->truncateTable($table);
		$result = $db->insertObject($table, $sampleData);
		self::assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($sampleData->title));
		self::assertNotEmpty($db->setQuery($query)->loadResult());
		self::assertNull($sampleData->id);

		// Inserting and specifying key updates the object
		$db->truncateTable($table);
		$result = $db->insertObject($table, $sampleData, 'id');
		self::assertTrue($result);
		self::assertNotNull($sampleData->id);

		// Bad keys are ignored
		$newSampleData = array_merge((array)$sampleData, array('doesnotexist' => 1234));
		$db->truncateTable($table);
		$result = $db->insertObject($table, $sampleData);
		self::assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($sampleData->title));
		self::assertNotEmpty($db->setQuery($query)->loadResult());

		// "Internal" keys (starting with underscore) and non-scalars are ignored
		$newSampleData = array_merge((array)$sampleData, array('_internal' => 1234, 'baz' => array(1, 2, 3), 'whatever' => (object)array('foo' => 'bar')));
		$db->truncateTable($table);
		$result = $db->insertObject($table, $sampleData);
		self::assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($sampleData->title));
		self::assertNotEmpty($db->setQuery($query)->loadResult());

		// Failures result in exception
		$this->setExpectedException('\RuntimeException');
		$result = $db->insertObject($table . '_foobar', $sampleData, 'id');
	}

	/**
	 * Test updateObject method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testUpdateObject()
	{
		$driver = static::getDriver();
		$sampleData = (object)array(
			'id'          => null,
			'title'       => 'test_insert',
			'start_date'  => '2014-06-17 00:00:00',
			'description' => 'Testing object insert'
		);
		$table = '#__dbtest';
		$db = $driver;

		// Updating full object really updates the database
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = (object)array(
			'id'          => $sampleData->id,
			'title'       => 'test_update',
			'start_date'  => '2005-08-15 18:00:00',
			'description' => 'Updated record'
		);
		$result = $db->updateObject($table, $newObject, 'id');
		self::assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($sampleData->title));
		self::assertEmpty($db->setQuery($query)->loadResult());
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($newObject->title));
		self::assertNotEmpty($db->setQuery($query)->loadResult());

		// Ignoring nulls does not modify data already in the database
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = (object)array(
			'id'          => $sampleData->id,
			'title'       => null,
			'start_date'  => '2005-08-15 18:00:00',
			'description' => 'Updated again record'
		);
		$result = $db->updateObject($table, $newObject, 'id', false);
		self::assertTrue($result);
		$query = $db->getQuery(true)
			->select('title')
			->from($table)
			->where('id = ' . $db->q($sampleData->id));
		self::assertEquals($sampleData->title, $db->setQuery($query)->loadResult());

		// "Internal" keys (starting with underscore) and non-scalars are ignored
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = array_merge((array)$sampleData, array(
			'_internal' => 1234,
			'baz' => array(1, 2, 3),
			'whatever' => (object)array('foo' => 'bar'),
		));
		$newObject = (object)$newObject;
		$result = $db->updateObject($table, $newObject, 'id', false);
		self::assertTrue($result);

		// Wrong ID does not throw error (as no SQL error is raised by the database: it's a valid query with 0 affeced rows)
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = (object)array(
			'id'          => $sampleData->id + 10000,
			'title'       => 'this_will_fail',
			'start_date'  => '2005-08-15 18:00:00',
			'description' => 'Updated again record'
		);
		$result = $db->updateObject($table, $newObject, 'id');
		self::assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($newObject->title));
		self::assertEmpty($db->setQuery($query)->loadResult());

		// Nonexistent fields result in exception
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = array_merge((array)$sampleData, array(
			'iamnothere' => 1234,
		));
		$newObject = (object)$newObject;
		$this->setExpectedException('\RuntimeException');
		$result = $db->updateObject($table, $newObject, 'id', false);
	}
}
