<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database;


interface DatabaseAwareInterface
{
	/**
	 * Return the database driver object
	 *
	 * @return  Driver
	 */
	public function getDbo();
}