<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace Akeeba\Replace\Tests\Engine\Core\Filter\Table;

use ClassicPress\SimpleDBBackup\Database\Driver\Fake;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table\Sorter;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;

class SorterTest extends \PHPUnit_Framework_TestCase
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
		$db->tableMeta['test_2_bar'] = new Table('test_2_bar', null, 0, '');
		$db->tableMeta['test_zorg'] = new Table('test_zorg', null, 0, '');
		$config = new Configuration([]);
		$filter = new Sorter($logger, $db, $config);

		$tables = [
			'test_zorg',
			'test_foo',
			'test_bar',
			'test_2_foo',
			'test_2_bar',
		];

		$actual = $filter->filter($tables);
		$expected = [
			'test_2_foo',
			'test_bar',
			'test_foo',
			'test_2_bar',
			'test_zorg',
		];

		self::assertCount(5, $actual);
		self::assertEquals($expected, $actual);
	}
}
