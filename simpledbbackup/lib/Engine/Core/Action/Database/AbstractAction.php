<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Action\Database;


use ClassicPress\SimpleDBBackup\Database\DatabaseAware;
use ClassicPress\SimpleDBBackup\Database\DatabaseAwareInterface;
use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAware;
use ClassicPress\SimpleDBBackup\Engine\Core\ConfigurationAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\LoggerAware;
use ClassicPress\SimpleDBBackup\Logger\LoggerAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

abstract class AbstractAction implements ActionInterface, DatabaseAwareInterface, LoggerAwareInterface,
	ConfigurationAwareInterface
{
	use DatabaseAware;
	use LoggerAware;
	use ConfigurationAware;

	public function __construct(Driver $db, LoggerInterface $logger, Configuration $config)
	{
		$this->setDriver($db);
		$this->setLogger($logger);
		$this->setConfig($config);
	}
}