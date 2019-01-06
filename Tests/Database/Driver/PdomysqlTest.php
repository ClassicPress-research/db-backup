<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database\Driver;

class PdomysqlTest extends MysqliTest
{
	/**
	 * @var   string  The name of the database driver to instantiate
	 */
	protected static $driverName = 'pdomysql';

	/**
	 * Test isSupported method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testIsSupported()
	{
		self::assertThat(\ClassicPress\SimpleDBBackup\Database\Driver\Pdomysql::isSupported(), $this->isTrue(), __LINE__);
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
		$this->expectExceptionMessage('Base table or view not found: 1051 Unknown table');
		$driver->dropTable('#__bar', false);
	}

	/**
	 * Test select method.
	 *
	 * @return  void
	 */
	public function testSelect()
	{
		$driver = static::getDriver();

		$this->expectExceptionMessage('1044 Access denied for user');
		$driver->select('DOES_NOT_EXIST');
	}

}
