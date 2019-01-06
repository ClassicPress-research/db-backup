<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine;

use InvalidArgumentException;

/**
 * A trait to implement the DomainAwareInterface
 *
 * @package ClassicPress\SimpleDBBackup\Engine
 */
trait DomainAware
{
	/**
	 * The current engine domain
	 *
	 * @var string
	 */
	private $domain = '';

	/**
	 * Get the name of the engine domain this part is processing.
	 *
	 * @return  mixed
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Set the current engine domain
	 *
	 * @param   string  $domain
	 *
	 * @throws InvalidArgumentException
	 */
	protected function setDomain($domain)
	{
		if (!is_string($domain))
		{
			throw new InvalidArgumentException(sprintf("Parameter \$domain to %s::%s must be a string, %s given", __CLASS__, __METHOD__, gettype($domain)));
		}

		$this->domain = $domain;
	}
}