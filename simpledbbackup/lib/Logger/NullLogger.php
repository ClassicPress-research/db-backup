<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Logger;

/**
 * A logger which does absolutely nothing. It is to be used when you want to disable logging, e.g. during Unit Testing.
 *
 * @package ClassicPress\SimpleDBBackup\Logger
 */
class NullLogger extends AbstractLogger
{
	/**
	 * This is the internal method which performs the actual logging. In this implementation it does nothing.
	 *
	 * @param   int     $severity  Log message severity (see LoggerInterface)
	 * @param   string  $message   The message to log
	 *
	 * @return  void
	 */
	protected function writeToLog($severity, $message)
	{
	}

	/**
	 * Resets the log. In this implementation it does nothing since there is no log to begin with.
	 *
	 * This should be called when a new run starts and we want to write to a fresh log.
	 *
	 * @return  void
	 */
	public function reset()
	{
	}
}