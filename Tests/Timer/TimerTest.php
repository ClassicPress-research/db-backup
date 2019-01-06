<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Timer;

use ClassicPress\SimpleDBBackup\Timer\Timer;

class TimerTest extends \PHPUnit_Framework_TestCase
{

	public function test__construct()
	{
		$timer = new Timer(10, 10);

		/**
		 * IMPORTANT: The constructor gets the max_exec_time in seconds and a runtime_bias in percent The actual maximum
		 * execution time recorded in the object properties is their multiple. So 10 seconds times 10% equals 1 second.
		 * Hence the test for 1 below.
		 */
		self::assertEquals(
			1,
			$this->getObjectAttribute($timer, 'max_exec_time'),
			'Execution time is not set correctly (runtime bias is ignored)'
		);

		$now = microtime(true);
		self::assertEquals(
			$now, $this->getObjectAttribute($timer, 'start_time'),
			'Start time is different than the current time (max allowed deviation: 0.1 seconds)', 0.1);
	}

	public function test__wakeup()
	{
		$timer = new Timer(1, 100);
		$this->setObjectAttribute($timer, 'start_time', '12345');

		$serialised = serialize($timer);
		unset($timer);
		$newTimer = unserialize($serialised);

		self::assertNotEquals(
			12345,
			$this->getObjectAttribute($newTimer, 'start_time'),
			'Start time must not survive unserialization.'
		);

		$now = microtime(true);
		self::assertEquals(
			$now, $this->getObjectAttribute($newTimer, 'start_time'),
			'Start time is different than the current time (max allowed deviation: 0.1 seconds)', 0.1);
	}

	public function testGetRunningTime()
	{
		$timer = new Timer(1, 100);

		$runningTime = $timer->getRunningTime();
		self::assertEquals(0, $runningTime,
			'Running time on a new timer is not zero (max deviation: 0.1 seconds)', 0.1);

		$originalMicrotime = $this->getObjectAttribute($timer, 'start_time');
		$this->setObjectAttribute($timer, 'start_time', $originalMicrotime - 1);

		$runningTime = $timer->getRunningTime();
		self::assertEquals(
			1, $runningTime,
			'Running time is not calculated correctly (max deviation: 0.1 seconds)', 0.1
		);
	}

	public function testResetTime()
	{
		$timer = new Timer(1, 100);
		$this->setObjectAttribute($timer, 'start_time', 12345);

		self::assertEquals(
			12345,
			$this->getObjectAttribute($timer, 'start_time'),
			'THE TEST IS WRONG. We failed to set a custom start time.'
		);

		$timer->resetTime();

		$now = microtime(true);
		self::assertEquals(
			$now, $this->getObjectAttribute($timer, 'start_time'),
			'Start time is different than the current time after reset (max allowed deviation: 0.1 seconds)', 0.1);
	}

	public function testGetTimeLeft()
	{
		$timer = new Timer(1, 100);
		$this->setObjectAttribute($timer, 'start_time', microtime(true) - 0.3);

		self::assertLessThanOrEqual(
			0.7,
			$timer->getTimeLeft(),
			'Time left must be calculated based on the start_time and the current microtime'
		);
	}

	private function setObjectAttribute($object, $attributeName, $value)
	{
		$refObject = new \ReflectionObject($object);
		$refProperty = $refObject->getProperty($attributeName);

		if ($refProperty->isPublic())
		{
			$refProperty->setValue($object, $value);

			return;
		}

		$refProperty->setAccessible(true);
		$refProperty->setValue($object, $value);
		$refProperty->setAccessible(false);
	}
}
