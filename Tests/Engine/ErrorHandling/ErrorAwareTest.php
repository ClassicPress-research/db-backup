<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\ErrorHandling;

use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorException;

class ErrorAwareTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @covers \ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware::setError
	 */
	public function testSetError()
	{
		/** @var ErrorAware $dummyObject */
		$dummyObject = $this->getObjectForTrait('\\ClassicPress\\SimpleDBBackup\\Engine\\ErrorHandling\\ErrorAware');

		$expected    = new ErrorException('Foo bar baz');

		$dummyObject->setError($expected);
		$actual = $this->getObjectAttribute($dummyObject, 'error');
		self::assertSame($expected, $actual, "Setting an error must assign the error object");
	}

	/**
	 * @covers \ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware::setErrorMessage
	 */
	public function testSetErrorMessageWithObject()
	{
		/** @var ErrorAware $dummyObject */
		$dummyObject = $this->getObjectForTrait('\\ClassicPress\\SimpleDBBackup\\Engine\\ErrorHandling\\ErrorAware');
		$errorObject = new ErrorException('Foo bar baz');

		$this->expectException('\\InvalidArgumentException');
		$this->expectExceptionMessage('Parameter $message to ');
		$dummyObject->setErrorMessage($errorObject);
	}

	/**
	 * @covers \ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware::setErrorMessage
	 */
	public function testSetErrorMessageWithString()
	{
		/** @var ErrorAware $dummyObject */
		$dummyObject = $this->getObjectForTrait('\\ClassicPress\\SimpleDBBackup\\Engine\\ErrorHandling\\ErrorAware');

		$message = 'Foo bar baz';
		$actual  = $dummyObject->setErrorMessage($message);
		self::assertInternalType('object', $actual, 'setErrorMessage must return an object when a non-empty string is passed');
		self::assertEquals('ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorException', get_class($actual), 'setErrorMessage must return an ExceptionException object when a non-empty string is passed');
		self::assertEquals($message, $actual->getMessage(), 'setErrorMessage must return an ExceptionException object with the correct message');
		self::assertEquals(0, $actual->getCode(), 'setErrorMessage must return an ExceptionException object with the correct code (zero)');
		$internal = $this->getObjectAttribute($dummyObject, 'error');
		self::assertSame($actual, $internal, "The returned object must be a reference to the internal object");
	}

	/**
	 * @covers \ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware::setErrorMessage
	 */
	public function testSetErrorMessageWithNull()
	{
		/** @var ErrorAware $dummyObject */
		$dummyObject = $this->getObjectForTrait('\\ClassicPress\\SimpleDBBackup\\Engine\\ErrorHandling\\ErrorAware');

		$message = '';
		$actual  = $dummyObject->setErrorMessage($message);
		self::assertInternalType('null', $actual, 'setErrorMessage must return null when a non-empty string is passed');
		$internal = $this->getObjectAttribute($dummyObject, 'error');
		self::assertNull($internal, "The internal error object must be cleared when setting an empty error message");
	}

	/**
	 * @covers \ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware::getError
	 */
	public function testGetError()
	{
		/** @var ErrorAware $dummyObject */
		$dummyObject = $this->getObjectForTrait('\\ClassicPress\\SimpleDBBackup\\Engine\\ErrorHandling\\ErrorAware');

		$actual = $dummyObject->getError();
		self::assertNull($actual, "getError must return null when there is no error");

		$exception = new ErrorException('Foo bar baz');
		$dummyObject->setError($exception);

		$actual = $dummyObject->getError();
		self::assertSame($exception, $actual, "getError must return the assigned object");

	}

	public function testResetError()
	{
		/** @var ErrorAware $dummyObject */
		$dummyObject = $this->getObjectForTrait('\\ClassicPress\\SimpleDBBackup\\Engine\\ErrorHandling\\ErrorAware');

		$exception = new ErrorException('Foo bar baz');
		$dummyObject->setError($exception);
		$actual = $dummyObject->getError();

		if ($actual !== $exception)
		{
			// We failed to set an exception. Therefore a positive test result does not necessarily indicate success!
			$this->markAsRisky();
		}

		$dummyObject->resetError();
		$actual = $dummyObject->getError();
		self::assertNull($actual, "reset() failed to reset the error object");
	}

	public function testInheritErrorFrom()
	{
		/**
		 * IMPORTANT! The $parentObject needs to implement the ErrorAwareInterface. This is only possible by using a
		 *            dummy abstract class and getMockForAbstractClass(). That's because getObjectForTrait() will return
		 *            an object which does not implement any interface. Moreover, traits cannot be implementing
		 *            interfaces (as of PHP 7.2) even if they actually contain all the methods to do so. That's a PHP
		 *            shortcoming.
		 */

		/** @var ErrorAware|ErrorAwareInterface $parentObject */
		$parentObject = $this->getMockForAbstractClass('ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\ErrorHandling\ErrorAwareTraitDummy');

		/** @var ErrorAware $childObject */
		$childObject = $this->getObjectForTrait('\\ClassicPress\\SimpleDBBackup\\Engine\\ErrorHandling\\ErrorAware');

		if ($childObject === $parentObject)
		{
			// Should never happen: same object returned by PHPUnit. This makes our test inconclusive.
			$this->markAsRisky();
		}

		$exception = new ErrorException('Foo bar baz');
		$parentObject->setError($exception);

		$actual = $childObject->getError();
		self::assertNull($actual, "No error must be set in the child object before we inherit from our parent.");

		$childObject->inheritErrorFrom($parentObject);

		$actual = $childObject->getError();
		self::assertSame($exception, $actual, "Inheriting an error must set a reference to the original error exception");

		$actual = $parentObject->getError();
		self::assertNull($actual, "After inheriting from the parent, the parent's error must be cleared.");

	}
}
