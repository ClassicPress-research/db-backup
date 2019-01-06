<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database\Driver;

class MysqlTest extends MysqliTest
{
	/**
	 * @var   string  The name of the database driver to instantiate
	 */
	protected static $driverName = 'mysql';

	/**
	 * Test isSupported method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testIsSupported()
	{
		self::assertThat(\ClassicPress\SimpleDBBackup\Database\Driver\Mysql::isSupported(), $this->isTrue(), __LINE__);
	}

}
