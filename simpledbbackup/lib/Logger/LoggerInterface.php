<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Logger;

/**
 * Interface to a logger.
 *
 * Loggers are used by Engine Parts and the user interface to record information which helps the user and the developer
 * understand which actions took place and, in case of a problem, what exactly has happened.
 *
 * @package ClassicPress\SimpleDBBackup\Logger
 */
interface LoggerInterface
{
	const SEVERITY_ERROR = 40;

	const SEVERITY_WARNING = 30;

	const SEVERITY_INFO = 20;

	const SEVERITY_DEBUG = 10;

	/**
	 * Records information to the log
	 *
	 * @param   int     $severity  The severity of the message being recorded
	 * @param   string  $message   The recorded message
	 *
	 * @return  void
	 */
	public function log($severity, $message);

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
	public function setMinimumSeverity($severity);

	/**
	 * Resets the log.
	 *
	 * This should be called when a new run starts and we want to write to a fresh log.
	 *
	 * @return  void
	 */
	public function reset();

	/**
	 * Record a message with severity 'error' to the log.
	 *
	 * Errors are high priority messages, indicating an unrecoverable problem situation. The application is reasonably
	 * expected to crash soon afterwards.
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public function error($message);

	/**
	 * Record a message with severity 'warning' to the log.
	 *
	 * Warnings are medium priority messages, indicating a situation which could lead to unwanted behavior. However, the
	 * application should be able to recover from it and it is reasonably expected to continue working.
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public function warning($message);

	/**
	 * Record a message with severity 'info' to the log.
	 *
	 * Information messages are low priority messages. They are used to convey information to the end user.
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public function info($message);

	/**
	 * Record a message with severity 'debug' to the log.
	 *
	 * Debug messages are the lowest priority messages. They are used to convey information to the developer, recording
	 * internal actions and decisions which do not necessarily mean anything to an end user.
	 *
	 * @param   string  $message
	 *
	 * @return  void
	 */
	public function debug($message);
}