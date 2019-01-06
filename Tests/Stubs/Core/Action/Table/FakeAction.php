<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Stubs\Core\Action\Table;


use ClassicPress\SimpleDBBackup\Database\Metadata\Column;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table;
use ClassicPress\SimpleDBBackup\Engine\Core\Action\Table\AbstractAction;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;

class FakeAction extends AbstractAction
{
	public function processTable(Table $table, array $columns)
	{
		return new SQL(['Foo'], ['Bar']);
	}
}