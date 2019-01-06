<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database;

/**
 * A trait for objects which have a database connection object
 *
 * @package ClassicPress\SimpleDBBackup\Database
 */
trait DatabaseAware
{
	/**
	 * The database connection known to this object
	 *
	 * @var  Driver
	 */
	protected $db;

	/**
	 * Set the database driver object
	 *
	 * @param   Driver   $db
	 *
	 * @return  void
	 */
	protected function setDriver(Driver $db)
	{
		$this->db = $db;
	}

	/**
	 * Return the database driver object
	 *
	 * @return  Driver
	 */
	public function getDbo()
	{
		return $this->db;
	}
}