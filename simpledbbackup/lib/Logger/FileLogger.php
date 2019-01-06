<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Logger;


use ClassicPress\SimpleDBBackup\Writer\FileWriter;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;
use RuntimeException;

class FileLogger extends AbstractLogger
{
	/**
	 * The file writer we will use to record the log
	 *
	 * @var   WriterInterface
	 */
	protected $writer = null;

	/**
	 * Create a file logger given a file path
	 *
	 * @param   string  $filePath  Absolute file path to the log file
	 * @param   bool    $reset     Should I reset the log?
	 *
	 * @return  FileLogger
	 *
	 * @throws  RuntimeException  When we cannot open the file for writing.
	 */
	public static function fromFile($filePath, $reset = false)
	{
		$writer = new FileWriter($filePath, $reset);

		return new static($writer);
	}

	/**
	 * Logger constructor.
	 *
	 * @param   WriterInterface   $writer  The file writer to user
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct(WriterInterface $writer)
	{
		$this->writer = $writer;
	}

	/**
	 * Reset the log file (start fresh)
	 *
	 * @return void
	 *
	 * @codeCoverageIgnore
	 */
	public function reset()
	{
		$this->writer->reset();
	}

	/**
	 * Write a single line to the log
	 *
	 * @param   int     $severity
	 * @param   string  $message
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	protected function writeToLog($severity, $message)
	{
		$this->writer->writeLine($this->formatMessage($severity, $message));
	}

	/**
	 * Format a log entry for writing to a text log
	 *
	 * @param   int     $severity  Severity of the message
	 * @param   string  $message   The message to record
	 * @param   int     $when      Timestamp to record in the formatted message line
	 *
	 * @return  string
	 */
	protected function formatMessage($severity, $message, $when = -1)
	{
		// If no timestamp is specified use the current date and time
		$when = ($when < 0) ? time() : $when;

		// Convert the severity to human-readable text
		$severityText = 'UNKNOWN';

		switch ($severity)
		{
			case LoggerInterface::SEVERITY_DEBUG:
				$severityText = 'DEBUG';
				break;

			case LoggerInterface::SEVERITY_INFO:
				$severityText = 'INFO';
				break;

			case LoggerInterface::SEVERITY_WARNING:
				$severityText = 'WARNING';
				break;

			case LoggerInterface::SEVERITY_ERROR:
				$severityText = 'ERROR';
				break;
		}

		// Format the message and return it
		// Example line:
		// 2018-01-02 03:04:05 | WARNING | Message
		$lineFormat    = "%-19s | %-8s | %s";
		$dateFormat    = "Y-m-d H:i:s";
		$formattedDate = date($dateFormat, $when);

		return sprintf($lineFormat, $formattedDate, $severityText, $message);
	}
}