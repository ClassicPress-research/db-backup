<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Helper;

use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Logger\FileLogger;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;
use ClassicPress\SimpleDBBackup\Writer\FileWriter;
use ClassicPress\SimpleDBBackup\Writer\NullWriter;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * An object to help setting up output files (log, backup SQL, output SQL), returning the relevant objects.
 *
 * This is to be used by the user interfaces to construct the dependencies which are passed to core Engine Parts.
 *
 * @package  ClassicPress\SimpleDBBackup\Engine\Core\Helper
 */
class OutFileSetup
{
	/**
	 * The time which will be used for variable replacement in file names
	 *
	 * @var  DateTime
	 */
	private $dateTime;

	/**
	 * The time zone which will be used for variable replacement in file names
	 *
	 * @var  DateTimeZone
	 */
	private $timeZone;

	/**
	 * OutFileSetup constructor.
	 *
	 * @param   DateTime|string     $dateTime
	 * @param   DateTimeZone|string $timeZone
	 *
	 * @throws  Exception
	 */
	public function __construct($dateTime = 'now', $timeZone = 'UTC')
	{
		if (!is_object($timeZone))
		{
			try
			{
				$timeZone = new DateTimeZone($timeZone);
			}
			catch (Exception $e)
			{
				$timeZone = new DateTimeZone('UTC');
			}
		}

		$this->timeZone = $timeZone;

		if (!is_object($dateTime))
		{
			$dateTime = is_int($dateTime) ? ('@' . $dateTime) : $dateTime;

			try
			{
				$dateTime = new DateTime($dateTime, $this->timeZone);
			}
			catch (Exception $e)
			{
				$dateTime = new DateTime('now', $this->timeZone);
			}
		}

		$this->dateTime = $dateTime;
	}

	/**
	 * Get a timestamp in the local time zone (set up in the constructor).
	 *
	 * The $dateTime parameter can be:
	 * - 'now'                : Set to the current timestamp
	 * - an integer           : Set to the UNIX timestamp expressed by the integer
	 * - a DateTime object    : Used as-is
	 * - anything else / null : Use the DateTime given in the object constructor (fixed point in time)
	 *
	 * @param   string               $format   Date/time format (see date())
	 * @param   string|null|DateTime $dateTime The date and time to format. See above.
	 *
	 * @return  string
	 *
	 * @throws  Exception
	 */
	public function getLocalTimeStamp($format = 'Y-m-d H:i:s', $dateTime = null)
	{
		if ($dateTime == 'now')
		{
			$utcTimeZone = new DateTimeZone('UTC');
			$dateTime    = new DateTime('now', $utcTimeZone);
		}
		elseif (is_int($dateTime))
		{
			$utcTimeZone = new DateTimeZone('UTC');
			$dateTime    = new DateTime('@' . $dateTime, $utcTimeZone);
		}
		elseif (is_string($dateTime))
		{
			$utcTimeZone = new DateTimeZone('UTC');
			$dateTime    = new DateTime($dateTime, $utcTimeZone);
		}
		elseif (!is_object($dateTime) || !($dateTime instanceof DateTime))
		{
			$dateTime = $this->dateTime;
		}

		$dateNow = clone $dateTime;

		return $dateNow->setTimezone($this->timeZone)->format($format);
	}

	/**
	 * Return the file naming variables for the specific point in time.
	 *
	 * @param   string|int $timestamp The date/time or UNIX timestamp of the point in time the variables will be replaced for
	 *
	 * @return  array
	 *
	 * @throws  Exception
	 */
	public function getVariables($timestamp = null)
	{
		/**
		 * Time components. Expressed in whatever timezone the Platform decides to use.
		 */
		// Raw timezone, e.g. "EEST"
		$rawTz     = $this->getLocalTimeStamp("T", $timestamp);
		// Filename-safe timezone, e.g. "eest". Note the lowercase letters.
		$fsSafeTZ  = strtolower(str_replace(array(' ', '/', ':'), array('_', '_', '_'), $rawTz));

		return [
			'[DATE]'             => $this->getLocalTimeStamp("Ymd", $timestamp),
			'[YEAR]'             => $this->getLocalTimeStamp("Y", $timestamp),
			'[MONTH]'            => $this->getLocalTimeStamp("m", $timestamp),
			'[DAY]'              => $this->getLocalTimeStamp("d", $timestamp),
			'[TIME]'             => $this->getLocalTimeStamp("His", $timestamp),
			'[TIME_TZ]'          => $this->getLocalTimeStamp("His", $timestamp) . $fsSafeTZ,
			'[WEEK]'             => $this->getLocalTimeStamp("W", $timestamp),
			'[WEEKDAY]'          => $this->getLocalTimeStamp("l", $timestamp),
			'[GMT_OFFSET]'       => $this->getLocalTimeStamp("O", $timestamp),
			'[TZ]'               => $fsSafeTZ,
			'[TZ_RAW]'           => $rawTz,
		];
	}

	/**
	 * Replace the variables in a given string.
	 *
	 * @param   string     $input      The string to replace variables in
	 * @param   array      $additional Any additional replacements to make
	 * @param   string|int $timestamp  The date/time or UNIX timestamp of the point in time the variables will be replaced for
	 *
	 * @return  string
	 *
	 * @throws  Exception
	 *
	 * @codeCoverageIgnore
	 */
	public function replaceVariables($input, array $additional = [], $timestamp = null)
	{
		$variables = $this->getVariables($timestamp);
		$variables = array_merge($variables, $additional);

		return str_replace(array_keys($variables), array_values($variables), $input);
	}

	/**
	 * Create a new output SQL file writer object based on the file path set up in the configuration.
	 *
	 * @param   Configuration $config     The engine configuration
	 * @param   bool          $reset      Should I delete existing files by that name?
	 * @param   array         $additional Any additional replacements to make
	 *
	 * @throws  Exception
	 *
	 * @return  WriterInterface
	 */
	public function makeOutputWriter(Configuration $config, $reset = true, array $additional = [])
	{
		$filePath = $config->getOutputSQLFile();

		if (empty($filePath))
		{
			return new NullWriter('');
		}

		$filePath = $this->replaceVariables($filePath, $additional);

		return new FileWriter($filePath, $reset);
	}

	/**
	 * Create a new logger object based on the log file path set up in the configuration. A null logger is returned if
	 * the log path is empty.
	 *
	 * @param   Configuration $config     The engine configuration
	 * @param   bool          $reset      Should I delete existing files by that name?
	 * @param   array         $additional Any additional replacements to make
	 *
	 * @throws  Exception
	 *
	 * @return  LoggerInterface
	 */
	public function makeLogger(Configuration $config, $reset = true, array $additional = [])
	{
		$filePath = $config->getLogFile();

		if (empty($filePath))
		{
			return new NullLogger();
		}

		$filePath  = $this->replaceVariables($filePath, $additional);
		$logWriter = new FileWriter($filePath, $reset);
		$logger    = new FileLogger($logWriter);

		$logger->setMinimumSeverity($config->getMinLogLevel());

		return $logger;
	}
}