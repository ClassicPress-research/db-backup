<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace Akeeba\Replace\Tests\Engine\Core\Filter\Table;

use ClassicPress\SimpleDBBackup\Database\Driver\Fake;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table\NonCore;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;

class NonCoreTest extends \PHPUnit_Framework_TestCase
{
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		require_once DBBACKUP_TEST_ROOT . '/Stubs/Database/Driver/Fake.php';
	}

	public function testFilter()
	{
		$logger = new NullLogger();
		$db     = new Fake([
			'prefix' => 'test_',
		]);
		$config = new Configuration([]);
		$filter = new NonCore($logger, $db, $config);

		$tables = [
			'test_foo',
			'test_bar',
			'test2_foo',
			'test2_bar',
			'test_2_foo',
			'test_2_bar',
			'tests_foo',
			'tests_bar',
			'foo',
			'bar',
		];

		$actual = $filter->filter($tables);

		self::assertCount(4, $actual);
		self::assertContains('test_foo', $actual);
		self::assertContains('test_bar', $actual);
		self::assertContains('test_2_foo', $actual);
		self::assertContains('test_2_bar', $actual);
	}
}
