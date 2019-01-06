<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database\Query;

use ClassicPress\SimpleDBBackup\Database\Query\Mysqli;
use ClassicPress\SimpleDBBackup\Tests\Database\DriverTestCase;

class MysqliTest extends DriverTestCase
{
	/**
	 * @var   string  The name of the database driver to instantiate
	 */
	protected static $driverName = 'mysqli';

	/**
	 * Test for LIMIT and OFFSET clause.
	 *
	 * @return  void
	 *
	 * @covers \ClassicPress\SimpleDBBackup\Database\Query\Mysqli::setLimit
	 */
	public function testSetLimitAndOffset()
	{
		$driver = static::getDriver();

		$q = new Mysqli($driver);
		$q->setLimit('5', '10');

		self::assertThat(
			trim($this->getObjectAttribute($q, 'limit')),
			$this->equalTo('5'),
			'Tests limit was set correctly.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($q, 'offset')),
			$this->equalTo('10'),
			'Tests offset was set correctly.'
		);
	}

	/**
	 * Tests the \Awf\Database\Query\Mysqli::processLimit method.
	 *
	 * @return  void
	 *
	 * @covers \ClassicPress\SimpleDBBackup\Database\Query\Mysqli::processLimit
	 */
	public function testProcessLimit()
	{
		$driver = static::getDriver();
		$q      = new Mysqli($driver);

		self::assertThat(
			trim($q->processLimit('SELECT foo FROM bar', 5, 10)),
			$this->equalTo('SELECT foo FROM bar LIMIT 10, 5'),
			'Tests rendered value.'
		);
	}

	/**
	 * Test for "concatenate" words.
	 *
	 * @return  void
	 */
	public function testConcatenate()
	{
		$driver = static::getDriver();
		$q      = new Mysqli($driver);

		self::assertThat(
			$q->concatenate(array('foo', 'bar')),
			$this->equalTo('CONCAT(foo,bar)'),
			'Tests without separator.'
		);

		self::assertThat(
			$q->concatenate(array('foo', 'bar'), ' and '),
			$this->equalTo("CONCAT_WS(' and ', foo, bar)"),
			'Tests without separator.'
		);
	}
}
