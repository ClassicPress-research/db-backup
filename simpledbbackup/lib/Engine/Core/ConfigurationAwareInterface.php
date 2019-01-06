<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core;

/**
 * Interface to classes implementing an Akeeba Replace engine configuraiton
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core
 */
interface ConfigurationAwareInterface
{
	/**
	 * Return the configuration object
	 *
	 * @return  Configuration
	 */
	public function getConfig();
}