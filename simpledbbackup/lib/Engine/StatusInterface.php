<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine;

/**
 * Interface to an Engine Part status object
 *
 * @package ClassicPress\SimpleDBBackup\Engine
 */
interface StatusInterface
{
	/**
	 * Export the status as an array.
	 *
	 * This is the same "return array" format we use in our other products such as Akeeba Backup, Akeeba Kickstart and
	 * Admin Tools. It's meant to be consumed by client-side JavaScript.
	 *
	 * @return  array
	 */
	public function toArray();


}