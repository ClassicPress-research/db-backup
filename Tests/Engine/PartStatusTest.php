<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine;

use ClassicPress\SimpleDBBackup\Engine\AbstractPart;
use ClassicPress\SimpleDBBackup\Engine\PartStatus;
use ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\AbstractPartStub;
use ClassicPress\SimpleDBBackup\Timer\Timer;

class PartStatusTest extends \PHPUnit_Framework_TestCase
{

	public function test__constructEmpty()
	{
		$emptyParams = [];
		$dummy = new PartStatus($emptyParams);

		self::assertSame(false, $this->getObjectAttribute($dummy, 'done'), 'Default "done" must be false');
		self::assertSame('', $this->getObjectAttribute($dummy, 'domain'), 'Default "domain" must be an empty string');
		self::assertSame('', $this->getObjectAttribute($dummy, 'step'), 'Default "step" must be an empty string');
		self::assertSame('', $this->getObjectAttribute($dummy, 'substep'), 'Default "substep" must be an empty string');
		self::assertSame(null, $this->getObjectAttribute($dummy, 'error'), 'Default "error" must be null');
		self::assertSame([], $this->getObjectAttribute($dummy, 'warnings'), 'Default "warnings" must be null');
	}

	/**
	 * @param   mixed  $param
	 * @param   bool   $expected
	 *
	 * @dataProvider \ClassicPress\SimpleDBBackup\Tests\Engine\PartStatusProvider::test__constructWithDoneProvider()
	 */
	public function test__constructWithDone($param, $expected)
	{
		$message = sprintf("Passing 'Done' => %s in the params must result in \$done === %s in the constructed object", print_r($param, true), $expected ? 'true' : 'false');

		$parameters = [
			'Done' => $param
		];

		$dummy = new PartStatus($parameters);
		self::assertSame($expected, $this->getObjectAttribute($dummy, 'done'), $message);
	}

	/**
	 * @param   mixed  $param
	 * @param   bool   $expected
	 *
	 * @dataProvider \ClassicPress\SimpleDBBackup\Tests\Engine\PartStatusProvider::test__constructWithDoneProvider()
	 *
	 * Since HasRun is the polar opposite to Done we are using the same data provider and looking for the *opposite*
	 * result than what was expected there.
	 */
	public function test__constructWithHasRun($param, $expected)
	{
		$message = sprintf("Passing 'HasRun' => %s in the params must result in \$done === %s in the constructed object", print_r($param, true), !$expected ? 'true' : 'false');

		$parameters = [
			'HasRun' => $param
		];

		$dummy = new PartStatus($parameters);
		self::assertSame(!$expected, $this->getObjectAttribute($dummy, 'done'), $message);
	}

	/**
	 * @param $param
	 * @param $expected
	 *
	 * @dataProvider \ClassicPress\SimpleDBBackup\Tests\Engine\PartStatusProvider::test__constructWithErrorProvider()
	 *
	 * @covers \ClassicPress\SimpleDBBackup\Engine\PartStatus::setError
	 */
	public function test__constructWithError($param, $expected)
	{
		$parameters = [
			'Error' => $param
		];

		$dummy = new PartStatus($parameters);
		self::assertEquals($expected, $this->getObjectAttribute($dummy, 'error'));
	}

	/**
	 * @param $param
	 * @param $expected
	 *
	 * @dataProvider \ClassicPress\SimpleDBBackup\Tests\Engine\PartStatusProvider::test__constructWithWarningsProvider()
	 *
	 * @covers \ClassicPress\SimpleDBBackup\Engine\PartStatus::setWarnings
	 * @covers \ClassicPress\SimpleDBBackup\Engine\PartStatus::addWarning
	 */
	public function test__constructWithWarnings($param, $expected)
	{
		$parameters = [
			'Warnings' => $param
		];

		$dummy = new PartStatus($parameters);
		self::assertEquals($expected, $this->getObjectAttribute($dummy, 'warnings'));
	}


	public function testFromPart()
	{
		$part   = $this->makeDummyPart();
		$status = PartStatus::fromPart($part);

		self::assertFalse($status->isDone());
		self::assertEquals($status->getDomain(), 'foo');
		self::assertEquals($status->getStep(), 'bar');
		self::assertEquals($status->getSubstep(), 'bat');
		self::assertSame($status->getError(), $part->getError());
		self::assertSame($status->getWarnings(), $part->getWarnings());
	}

	public function testToArray()
	{
		$part   = $this->makeDummyPart();
		$status = PartStatus::fromPart($part);

		$actual = $status->toArray();

		$expected = [
			'HasRun' => 1,
			'Done' => 0,
			'Domain' => 'foo',
			'Step' => 'bar',
			'Substep' => 'bat',
			'Error' => 'Foo bar baz',
			'Warnings' => [
				'Foo',
				'Bar',
				'Baz',
			]
		];

		self::assertEquals($expected, $actual);
	}

	private function callObjectMethod($object, $method, $params)
	{
		$refObj = new \ReflectionObject($object);
		$refMethod = $refObj->getMethod($method);
		$refMethod->setAccessible(true);
		$refMethod->invoke($object, $params);
	}

	/**
	 * @return AbstractPart
	 */
	private function makeDummyPart()
	{
		$timer = $this->getMockBuilder(Timer::class)->getMock();
		/** @var AbstractPart $part */
		$part = new AbstractPartStub($timer, []);

		$this->callObjectMethod($part, 'setDomain', 'foo');
		$this->callObjectMethod($part, 'setStep', 'bar');
		$this->callObjectMethod($part, 'setSubstep', 'bat');
		$part->setErrorMessage('Foo bar baz');
		$part->addWarningMessage('Foo');
		$part->addWarningMessage('Bar');
		$part->addWarningMessage('Baz');

		return $part;
	}
}
