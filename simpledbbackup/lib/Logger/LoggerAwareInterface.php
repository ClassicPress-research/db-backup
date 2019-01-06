<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Logger;

/**
 * Interface to a class which knows about using a logger
 *
 * @package ClassicPress\SimpleDBBackup\Logger
 */
interface LoggerAwareInterface
{
	/**
	 * Returns a reference to the logger object. This should only be used internally.
	 *
	 * @return  LoggerInterface
	 */
	public function getLogger();
}