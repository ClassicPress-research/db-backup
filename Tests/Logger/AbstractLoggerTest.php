<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Logger;

use ClassicPress\SimpleDBBackup\Logger\AbstractLogger;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;
use Prophecy\Prophet;

class AbstractLoggerTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @param   int     $setTo     Minimum severity level to set
	 * @param   int     $expected  Actual minimum severity set
	 *
	 * @dataProvider setMinimumSeverityProvider
	 *
	 * @return  void
	 */
	public function testSetMinimumSeverity($setTo, $expected)
	{
		/** @var LoggerInterface $logger */
		$logger = $this->getMockForAbstractClass(AbstractLogger::class);
		$logger->setMinimumSeverity($setTo);

		$actual = $this->getObjectAttribute($logger, 'minimumSeverity');
		self::assertEquals($expected, $actual);
	}

	public function setMinimumSeverityProvider()
	{
		return [
			'Invalid value' => [-1, LoggerInterface::SEVERITY_DEBUG],
			'Set to Debug' => [LoggerInterface::SEVERITY_DEBUG, LoggerInterface::SEVERITY_DEBUG],
			'Set to Info' => [LoggerInterface::SEVERITY_INFO, LoggerInterface::SEVERITY_INFO],
			'Set to Warning' => [LoggerInterface::SEVERITY_WARNING, LoggerInterface::SEVERITY_WARNING],
			'Set to Error' => [LoggerInterface::SEVERITY_ERROR, LoggerInterface::SEVERITY_ERROR],
		];
	}

	public function testLog()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->getMockForAbstractClass(AbstractLogger::class);
		$logger
			->expects($this->atLeastOnce())
			->method('writeToLog')
			->with($this->equalTo(LoggerInterface::SEVERITY_ERROR), $this->equalTo('Foo'))
		;
		$logger->log(LoggerInterface::SEVERITY_ERROR, 'Foo');
	}

	public function testLogWithMessageOfUnreportedLevel()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->getMockForAbstractClass(AbstractLogger::class);
		$logger
			->expects($this->never())
			->method('writeToLog')
		;
		$logger->setMinimumSeverity(LoggerInterface::SEVERITY_INFO);
		$logger->log(LoggerInterface::SEVERITY_DEBUG, 'Foo');
	}

	public function testError()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->getMockForAbstractClass(AbstractLogger::class);
		$logger
			->expects($this->atLeastOnce())
			->method('writeToLog')
			->with($this->equalTo(LoggerInterface::SEVERITY_ERROR), $this->equalTo('Foo'))
		;
		$logger->error('Foo');
	}

	public function testWarning()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->getMockForAbstractClass(AbstractLogger::class);
		$logger
			->expects($this->atLeastOnce())
			->method('writeToLog')
			->with($this->equalTo(LoggerInterface::SEVERITY_WARNING), $this->equalTo('Foo'))
		;
		$logger->warning('Foo');

	}

	public function testDebug()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->getMockForAbstractClass(AbstractLogger::class);
		$logger
			->expects($this->atLeastOnce())
			->method('writeToLog')
			->with($this->equalTo(LoggerInterface::SEVERITY_DEBUG), $this->equalTo('Foo'))
		;
		$logger->debug('Foo');
	}

	public function testInfo()
	{
		/** @var LoggerInterface $logger */
		$logger = $this->getMockForAbstractClass(AbstractLogger::class);
		$logger
			->expects($this->atLeastOnce())
			->method('writeToLog')
			->with($this->equalTo(LoggerInterface::SEVERITY_INFO), $this->equalTo('Foo'))
		;
		$logger->info('Foo');
	}
}
