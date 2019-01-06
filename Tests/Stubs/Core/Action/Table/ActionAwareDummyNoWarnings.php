<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Stubs\Core\Action\Table;


use ClassicPress\SimpleDBBackup\Engine\Core\Action\ActionAware as GenericActionAware;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\ActionAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\Table\ActionAware;

class ActionAwareDummyNoWarnings implements ActionAwareInterface
{
	use ActionAware;
	use GenericActionAware;
}