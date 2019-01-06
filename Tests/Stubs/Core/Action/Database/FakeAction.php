<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Stubs\Core\Action\Database;


use ClassicPress\SimpleDBBackup\Database\Metadata\Database;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\Database\AbstractAction;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;

class FakeAction extends AbstractAction
{
	public function processDatabase(Database $db)
	{
		return new SQL(['Foo'], ['Bar']);
	}

}