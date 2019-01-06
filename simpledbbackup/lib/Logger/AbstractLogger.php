<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Logger;

/**
 * An abstract class with the minimum common code a LoggerInterface implementation needs.
 *
 * @package ClassicPress\SimpleDBBackup\Logger
 */
abstract class AbstractLogger implements LoggerInterface
{
	/**
	 * The minimum severity a message must have to be recorded to the log. Default is LoggerInterface::SEVERITY_DEBUG
	 *
	 * @var  int
	 */
	protected $minimumSeverity = LoggerInterface::SEVERITY_DEBUG;

	/**
	 * Records information to the log. This implementation makes sure $severity is equal to or greater than the
	 * minimum configured severity. If not, it won't log anything.
	 *
	 * @param   int     $severity  The severity of the message being recorded
	 * @param   string  $message   The recorded message
	 *
	 * @return  void
	 *
	 * @see     self::writeToLog
	 */
	public final function log($severity, $message)
	{
		if ($severity < $this->minimumSeverity)
		{
			return;
		}

		$this->writeToLog($severity, $message);
	}

	/**
	 * This method must be implemented by children classes. This is the internal method which performs the actual
	 * logging.
	 *
	 * @param   int     $severity  Log message severity (see LoggerInterface)
	 * @param   string  $message   The message to log
	 *
	 * @return  void
	 */
	protected abstract function writeToLog($severity, $message);

	/**
	 * Sets the minimum severity which will be recorded by this logger.
	 *
	 * While you can send it messages of lower severity, they will not be recorded. Set the minimum severity to debug to
	 * record everything.
	 *
	 * @param   int  $severity
	 *
	 * @return  void
	 */
	public function setMinimumSeverity($severity)
	{
		if (!in_array($severity, [
			LoggerInterface::SEVERITY_DEBUG, LoggerInterface::SEVERITY_INFO, LoggerInterface::SEVERITY_WARNING,
			LoggerInterface::SEVERITY_ERROR
		]))
		{
			return;
		}

		$this->minimumSeverity = $severity;
	}

	/**
	 * Record a message with severity 'error' to the log. This method is final and simply calls log() with the correct
	 * severity.
	 *
	 * Errors are high priority messages, indicating an unrecoverable problem situation. The application is reasonably
	 * expected to crash soon afterwards.
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public final function error($message)
	{
		$this->log(LoggerInterface::SEVERITY_ERROR, $message);
	}

	/**
	 * Record a message with severity 'warning' to the log. This method is final and simply calls log() with the correct
	 * severity.
	 *
	 * Warnings are medium priority messages, indicating a situation which could lead to unwanted behavior. However, the
	 * application should be able to recover from it and it is reasonably expected to continue working.
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public final function warning($message)
	{
		$this->log(LoggerInterface::SEVERITY_WARNING, $message);
	}

	/**
	 * Record a message with severity 'info' to the log. This method is final and simply calls log() with the correct
	 * severity.
	 *
	 * Information messages are low priority messages. They are used to convey information to the end user.
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public final function info($message)
	{
		$this->log(LoggerInterface::SEVERITY_INFO, $message);
	}

	/**
	 * Record a message with severity 'debug' to the log. This method is final and simply calls log() with the correct
	 * severity.
	 *
	 * Debug messages are the lowest priority messages. They are used to convey information to the developer, recording
	 * internal actions and decisions which do not necessarily mean anything to an end user.
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public final function debug($message)
	{
		$this->log(LoggerInterface::SEVERITY_DEBUG, $message);
	}

}