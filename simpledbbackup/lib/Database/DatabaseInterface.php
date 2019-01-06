<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database;

/**
 * Database Interface
 *
 * @codeCoverageIgnore
 */
interface DatabaseInterface
{
	/**
	* Test to see if the connector is available.
	*
	* @return  boolean  True on success, false otherwise.
	*/
	public static function isSupported();
}
