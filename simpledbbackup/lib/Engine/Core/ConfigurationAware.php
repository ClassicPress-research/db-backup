<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core;

/**
 * Trait for classes implementing an Akeeba Replace engine configuration
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core
 */
trait ConfigurationAware
{
	/**
	 * The engine configuration known to the object
	 *
	 * @var  Configuration
	 */
	protected $config;

	/**
	 * Set the configuration
	 *
	 * @param   Configuration  $config
	 *
	 * @return  void
	 */
	protected function setConfig(Configuration $config)
	{
		$this->config = $config;
	}

	/**
	 * Return the configuration object
	 *
	 * @return  Configuration
	 */
	public function getConfig()
	{
		return $this->config;
	}
}