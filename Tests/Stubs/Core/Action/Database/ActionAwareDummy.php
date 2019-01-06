<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Stubs\Core\Action\Database;


use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAwareInterface;

class ActionAwareDummy extends ActionAwareDummyNoWarnings implements WarningsAwareInterface
{
	use WarningsAware;
}