<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Logger;

use ClassicPress\SimpleDBBackup\Logger\FileLogger;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;
use ClassicPress\SimpleDBBackup\Writer\FileWriter;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class FileLoggerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Virtual filesystem, used for testing
	 *
	 * @var vfsStreamDirectory
	 */
	private $root;

	protected function setUp()
	{
		parent::setUp();

		$this->root = vfsStream::setup('testing');
	}

	public function testFromFile()
	{
		$filePath = $this->root->url() . '/log.txt';
		$logger = FileLogger::fromFile($filePath, true);

		self::assertTrue($this->root->hasChild('log.txt'), 'Log file must be created');
	}

	/**
	 * @param int    $severity
	 * @param string $message
	 * @param int    $timestamp
	 * @param string $expected
	 *
	 * @return void
	 *
	 * @dataProvider formatMessageProvider
	 */
	public function testFormatMessage($severity, $message, $timestamp, $expected)
	{
		// Set timezone to UTC for test consistency
		$serverTimezone = @date_default_timezone_get();
		@date_default_timezone_set('UTC');

		/** @var WriterInterface $writer */
		$writer    = $this->prophesize(FileWriter::class)
			->willImplement(WriterInterface::class)
			->reveal();
		$logger    = new FileLogger($writer);
		$refObj    = new \ReflectionObject($logger);
		$refMethod = $refObj->getMethod('formatMessage');
		$refMethod->setAccessible(true);
		$actual = $refMethod->invoke($logger, $severity, $message, $timestamp);

		// Reset the timezone
		@date_default_timezone_set($serverTimezone);

		self::assertEquals($expected, $actual);
	}

	public function formatMessageProvider()
	{
		$tz        = new \DateTimeZone('UTC');
		$date      = new \DateTime('2018-01-02 03:04:05', $tz);
		$timestamp = $date->getTimestamp();

		return [
			// $severity, $message, $timestamp, $expected
			'Debug' => [
				LoggerInterface::SEVERITY_DEBUG, 'Foo', $timestamp,
				'2018-01-02 03:04:05 | DEBUG    | Foo',
			],
			'Info' => [
				LoggerInterface::SEVERITY_INFO, 'Foo', $timestamp,
				'2018-01-02 03:04:05 | INFO     | Foo',
			],
			'Warning' => [
				LoggerInterface::SEVERITY_WARNING, 'Foo', $timestamp,
				'2018-01-02 03:04:05 | WARNING  | Foo',
			],
			'Error' => [
				LoggerInterface::SEVERITY_ERROR, 'Foo', $timestamp,
				'2018-01-02 03:04:05 | ERROR    | Foo',
			],
		];
	}
}
