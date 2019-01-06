<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table;


use ClassicPress\SimpleDBBackup\Database\DatabaseAware;
use ClassicPress\SimpleDBBackup\Database\DatabaseAwareInterface;
use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAware;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\LoggerAware;
use ClassicPress\SimpleDBBackup\Logger\LoggerAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

/**
 * Abstract class for table list filters
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table
 */
abstract class AbstractFilter implements FilterInterface, LoggerAwareInterface, DatabaseAwareInterface,
	ConfigurationAwareInterface
{
	use LoggerAware;
	use DatabaseAware;
	use ConfigurationAware;

	/**
	 * AbstractFilter constructor.
	 *
	 * @param   LoggerInterface  $logger  The logger object
	 * @param   Driver           $db      The database connection object
	 * @param   Configuration    $config  The engine configuration object
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct(LoggerInterface $logger, Driver $db, Configuration $config)
	{
		$this->setLogger($logger);
		$this->setDriver($db);
		$this->setConfig($config);
	}
}