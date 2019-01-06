<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core\Action;

use ClassicPress\SimpleDBBackup\Database\Driver\Fake;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;
use ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\Core\Action\ActionAwareStub;
use ClassicPress\SimpleDBBackup\Tests\vfsAware;
use ClassicPress\SimpleDBBackup\Writer\FileWriter;

class ActionAwareTest extends \PHPUnit_Framework_TestCase
{
	use vfsAware;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		require_once DBBACKUP_TEST_ROOT . '/Stubs/Database/Driver/Fake.php';
	}

	protected function setUp()
	{
		parent::setUp();

		$this->setUp_vfsAware();
	}

	/**
	 * @param   SQL    $response
	 * @param   string $expectedContent
	 *
	 * @dataProvider providerApplyActionQueries
	 */
	public function testApplyActionQueries(SQL $response, $expectedContent)
	{
		$filePath = $this->root->url() . '/test.sql';
		$writer   = new FileWriter($filePath, true);
		$dummy    = new ActionAwareStub();
		$db       = new Fake();

		$dummy->applyActionQueries($response, $writer);

		$actualContent = file_get_contents($filePath);

		self::assertEquals($expectedContent, $actualContent);
		self::assertEmpty($dummy->getWarnings());
	}

	public static function providerApplyActionQueries()
	{
		return [
			// SQL $response, $expectedContent, $expectedError
			'No query'                     => [
				new SQL([]),
				'',
			],
			// SQL $response, $expectedContent
			'Working query'                => [
				new SQL(['Foo']),
				'Foo;' . "\n",
			],
			// SQL $response, $expectedContent
			'Working queries, two of them' => [
				new SQL(['Foo', 'Bar'], []),
				'Foo;' . "\n" . 'Bar;' . "\n",
			],
		];
	}
}
