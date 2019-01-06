<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core\Action\Database;

use ClassicPress\SimpleDBBackup\Database\Driver\Fake;
use ClassicPress\SimpleDBBackup\Database\Metadata\Database;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;
use ClassicPress\SimpleDBBackup\Tests\Stubs\Core\Action\Database\ActionAwareDummy;
use ClassicPress\SimpleDBBackup\Tests\Stubs\Core\Action\Database\ActionAwareDummyNoWarnings;
use ClassicPress\SimpleDBBackup\Tests\Stubs\Core\Action\Database\FakeAction;
use ClassicPress\SimpleDBBackup\Tests\vfsAware;
use ClassicPress\SimpleDBBackup\Writer\FileWriter;
use ClassicPress\SimpleDBBackup\Writer\NullWriter;

class ActionAwareTest extends \PHPUnit_Framework_TestCase
{
	use vfsAware;

	protected function setUp()
	{
		parent::setUp();

		$this->setUp_vfsAware();
	}

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		require_once DBBACKUP_TEST_ROOT . '/Stubs/Database/Driver/Fake.php';
	}

	public function testRunPerDatabaseAction_non_existent_class()
	{
		$class = 'ThisClassDoesNotExist';
		/** @var ActionAwareDummy $dummy */
		list($numQueries, $dummy) = $this->doRunAction($class);

		self::assertEquals(0, $numQueries);

		$warnings = $dummy->getWarnings();
		self::assertCount(1, $warnings);
		self::assertEquals('Action class “ThisClassDoesNotExist” does not exist', $warnings[0]->getMessage());
	}

	public function testRunPerDatabaseAction_Invalid_class()
	{
		$class = __CLASS__;
		/** @var ActionAwareDummy $dummy */
		list($numQueries, $dummy) = $this->doRunAction($class);

		self::assertEquals(0, $numQueries);

		$warnings = $dummy->getWarnings();
		self::assertCount(1, $warnings);
		self::assertEquals('Action class “' . __CLASS__ . '” is not a valid per-database action', $warnings[0]->getMessage());
	}

	public function testRunPerDatabaseAction_With_action()
	{
		$class        = FakeAction::class;
		$databaseMeta = new Database('foobar', 'utf8', 'utf8_general_ci');
		$logger       = new NullLogger();
		$db           = new Fake();
		$config       = new Configuration([]);
		$dummy        = new ActionAwareDummy();

		$outputWriter = $this->getMockBuilder(FileWriter::class)
			->setConstructorArgs([$this->root->url() . '/test.txt'])
			->getMock();
		$outputWriter
			->expects($this->any())
			->method('getFilePath')
			->willReturn('testOut.sql');
		$outputWriter
			->expects($this->once())
			->method('writeLine')
			->willReturn(null);

		$refObj    = new \ReflectionObject($dummy);
		$refMethod = $refObj->getMethod('runPerDatabaseAction');
		$refMethod->setAccessible(true);

		$numQueries = $refMethod->invoke($dummy, $class, $databaseMeta, $logger, $outputWriter, $db, $config);

		self::assertEquals(1, $numQueries);
	}

	/**
	 * @param $class
	 *
	 * @return mixed
	 */
	protected function doRunAction($class, $withWarnings = true)
	{
		$databaseMeta = new Database('foobar', 'utf8', 'utf8_general_ci');
		$logger       = new NullLogger();
		$nullWriter   = new NullWriter('');
		$db           = new Fake();
		$config       = new Configuration([]);
		$dummy        = new ActionAwareDummy();

		if (!$withWarnings)
		{
			$dummy = new ActionAwareDummyNoWarnings();
		}

		$refObj    = new \ReflectionObject($dummy);
		$refMethod = $refObj->getMethod('runPerDatabaseAction');
		$refMethod->setAccessible(true);

		return [
			$refMethod->invoke($dummy, $class, $databaseMeta, $logger, $nullWriter, $db, $config),
			$dummy
		];
}
}
