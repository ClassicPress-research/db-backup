<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\Core\Action;


use ClassicPress\SimpleDBBackup\Engine\Core\Action\ActionAware;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\ActionAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\LoggerAware;
use ClassicPress\SimpleDBBackup\Logger\LoggerAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;

class ActionAwareStub implements ActionAwareInterface, WarningsAwareInterface, LoggerAwareInterface
{
	use ActionAware;
	use WarningsAware;
	use LoggerAware;

	/**
	 * ActionAwareStub constructor.
	 */
	public function __construct()
	{
		$logger = new NullLogger();

		$this->setLogger($logger);
	}
}