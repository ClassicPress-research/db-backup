<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine;

use ClassicPress\SimpleDBBackup\Engine\AbstractPart;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorException;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAware;
use ClassicPress\SimpleDBBackup\Engine\PartInterface;
use ClassicPress\SimpleDBBackup\Engine\StepAware;
use ClassicPress\SimpleDBBackup\Tests\Engine\ErrorHandling\ErrorAwareTest;
use ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\AbstractPartStub;
use ClassicPress\SimpleDBBackup\Tests\Stubs\Timer\TimerAlwaysRunning;

class AbstractPartTest extends \PHPUnit_Framework_TestCase
{
	public function test__construct()
	{
		// Fake timer for testing
		$timer = $this->createMock('ClassicPress\SimpleDBBackup\Timer\Timer');
		// Fake parameters
		$params = ['liveMode' => false, 'excludeTables' => ['foo', 'bar', 'baz']];
		// Create a dummy object from the abstract class
		$dummy = $this->getMockForAbstractClass('ClassicPress\SimpleDBBackup\Engine\AbstractPart', [
			$timer, $params,
		]);

		// Make sure we got the correct object type
		self::assertInstanceOf('ClassicPress\SimpleDBBackup\Engine\AbstractPart', $dummy, 'TEST ERROR: We do not have the correct class.');

		$actualTimer    = $this->getObjectAttribute($dummy, 'timer');
		$actualState    = $this->getObjectAttribute($dummy, 'state');
		$actualConfig   = $this->getObjectAttribute($dummy, 'config');
		$expectedConfig = new Configuration($params);

		self::assertInstanceOf('ClassicPress\SimpleDBBackup\Timer\TimerInterface', $actualTimer, 'The Timer must be set by the constructor');
		self::assertSame($timer, $actualTimer, 'The actual Timer object must be set by the constructor');
		self::assertEquals(PartInterface::STATE_INIT, $actualState, 'The initial state must be STATE_INIT');
		self::assertEquals($expectedConfig, $actualConfig, 'The parameters must be set by the constructor');
	}

	public function testPropagateFromObjectInvalid()
	{
		$dummy = $this->makeDummyObject();

		$invalid = (object)array(
			'error' => new ErrorException('Foobar')
		);

		$dummy->propagateFromObject($invalid);

		self::assertNull($dummy->getError());
	}

	/**
	 * Test error propagation from objects implementing ErrorAwareInterface. For this to work we need to use an abstract
	 * class. For the reasons why, see the referenced test below.
	 *
	 * Note: I cannot use PHPUnit's mock builder with a concrete object implementing an interface because the mock does
	 *       NOT implement the interface. As a result, propagateFromObject never executes the expected code and
	 *       rightfully so (since we check if the other object implements an interface). I also cannot use a mock of an
	 *       abstract class because in this case PHPUnit cannot find the public inheritErrorFrom method I am trying to
	 *       mock. Therefore I have to write a smelly test which tests the result of a foreign class' execution rather
	 *       than my own code :(
	 *
	 * @see ErrorAwareTest::testInheritErrorFrom()
	 */
	public function testPropagateFromObjectErrors()
	{
		$dummy = $this->makeDummyObject();

		/** @var ErrorAware $errorAware */
		$errorAware = $this->getMockForAbstractClass('\\ClassicPress\\SimpleDBBackup\\Tests\\Stubs\\Engine\\ErrorHandling\\ErrorAwareTraitDummy');
		$error = $errorAware->setErrorMessage('Foo bar baz');

		$dummy->propagateFromObject($errorAware);

		self::assertSame($error, $this->getObjectAttribute($dummy, 'error'), 'propagateFromObject must pull in the error from the other object');
		self::assertNull($errorAware->getError(), 'propagateFromObject must clear errors on the other object');
	}

	/**
	 * Test warnings propagation from objects implementing WarningsAwareInterface. Kinda smelly. See the referenced test
	 * below.
	 *
	 * @see self::testPropagateFromObjectErrors
	 */
	public function testPropagateFromObjectWarnings()
	{
		$dummy = $this->makeDummyObject();

		/** @var WarningsAware $warningsAware */
		$warningsAware = $this->getMockForAbstractClass('\\ClassicPress\\SimpleDBBackup\\Tests\\Stubs\\Engine\\ErrorHandling\\WarningsAwareTraitDummy');
		$warnings = $warningsAware->getWarnings();

		$mustNotExist = $dummy->addWarningMessage('This warning is removed');
		$dummy->propagateFromObject($warningsAware);

		$actualWarnings = $dummy->getWarnings();

		self::assertNotContains($mustNotExist, $actualWarnings, 'propagateFromObject must replace our warnings');
		self::assertEquals($warnings, $actualWarnings, 'propagateFromObject must replace our warnings with the other object\'s warnings');
	}

	/**
	 * Test step / substep propagation from objects implementing StepAwareInterface. Kinda smelly. See the referenced
	 * test below.
	 *
	 * @see self::testPropagateFromObjectErrors
	 */
	public function testPropagateFromObjectSteps()
	{
		$dummy = $this->makeDummyObject();

		/** @var StepAware $stepAware */
		$stepAware = $this->getMockForAbstractClass('\\ClassicPress\\SimpleDBBackup\\Tests\\Stubs\\Engine\\StepAwareTraitDummy');

		$this->setProperty($stepAware, 'step', 'Foo');
		$this->setProperty($stepAware, 'substep', 'Bar');

		$this->setProperty($dummy, 'step', 'Baz');
		$this->setProperty($dummy, 'substep', 'Bat');

		$dummy->propagateFromObject($stepAware);

		/**
		 * Heads up! Unlike errors and warnings, propagating steps and substeps MUST NOT reset them on the other object.
		 * The idea is that errors and warnings are transient and bubble up to the outermost part where the application
		 * can interact with them (display them, take a decision based on their existence, ...) whereas step and substep
		 * may in fact be used for internal logging by the Engine Part after propagation, e.g. on __destruct() or
		 * __sleep().
		 */

		self::assertEquals('Foo', $dummy->getStep(), 'propagateFromObject must pull the step from the other object');
		self::assertNotEmpty($stepAware->getStep(), 'propagateFromObject must NOT reset the step on the other object');

		self::assertEquals('Bar', $dummy->getSubstep(), 'propagateFromObject must pull the substep from the other object');
		self::assertNotEmpty($stepAware->getSubstep(), 'propagateFromObject must NOT reset the substep on the other object');
	}

	public function testTick()
	{
		// Fake timer for testing - always tells us we have time left
		$timerProphet = $this->prophesize('ClassicPress\SimpleDBBackup\Timer\Timer')
		                     ->willImplement('ClassicPress\SimpleDBBackup\Timer\TimerInterface')
		;
		$timerProphet->getTimeLeft()->willReturn(1);
		$timer = $timerProphet->reveal();

		// Fake parameters, no used
		$params = ['foo' => 'bar', 'baz' => 'bat'];

		/** @var AbstractPart $dummy Object under test */
		$dummy = new AbstractPartStub($timer, $params);

		// Our expectations each time we run tick. The number corresponds to the run number reported by assertions.
		$expectations = [
			0 => ['state' => PartInterface::STATE_INIT, 'prepareThing' => false],
			1 => ['state' => PartInterface::STATE_PREPARED, 'prepareThing' => true],
			2 => ['state' => PartInterface::STATE_RUNNING, 'afterPrepareThing' => true],
			3 => ['state' => PartInterface::STATE_RUNNING, 'processCalls' => 1],
			4 => ['state' => PartInterface::STATE_POSTRUN, 'processCalls' => 2],
			5 => ['state' => PartInterface::STATE_FINALIZED, 'finalizeThing' => true],
		];

		$currentExpectation = [];
		$run                = 0;
		$status             = $dummy->getStatus();

		// Loop until our object finishes running
		do
		{
			$currentExpectation = array_merge($currentExpectation, array_shift($expectations));

			foreach ($currentExpectation as $prop => $expected)
			{
				self::assertEquals($expected, $this->getObjectAttribute($dummy, $prop), sprintf("Run %d: Property %s does not match expected value", $run, $prop));
			}

			if ($status->isDone())
			{
				break;
			}

			$run++;
			$status = $dummy->tick();
			$error  = $status->getError();

			if (!is_null($error))
			{
				throw $error;
			}
		} while (true);

		self::assertEmpty($expectations, "tick() ran fewer times than expected!");
	}

	/**
	 * @return AbstractPart
	 */
	private function makeDummyObject()
	{
		// Fake timer for testing
		$timer = $this->createMock('ClassicPress\SimpleDBBackup\Timer\Timer');
		// Fake parameters
		$params = ['foo' => 'bar', 'baz' => 'bat'];
		/** @var AbstractPart $dummy */
		$dummy = $this->getMockForAbstractClass('ClassicPress\SimpleDBBackup\Engine\AbstractPart', [
			$timer,
			$params,
		]);

		return $dummy;
	}

	private function setProperty($object, $property, $value)
	{
		$refObj = new \ReflectionObject($object);
		$refProp = $refObj->getProperty($property);
		$refProp->setAccessible(true);
		$refProp->setValue($object, $value);
	}
}
