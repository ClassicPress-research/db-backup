<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Stubs\Engine;


use ClassicPress\SimpleDBBackup\Engine\AbstractPart;

class AbstractPartStub extends AbstractPart
{
	public $prepareThing = false;
	public $afterPrepareThing = false;
	public $finalizeThing = false;
	public $processCalls = 0;

	public function prepare()
	{
		$this->prepareThing = true;
	}

	public function afterPrepare()
	{
		$this->afterPrepareThing = true;
	}

	public function process()
	{
		$this->processCalls++;

		return $this->processCalls !== 2;
	}

	public function finalize()
	{
		$this->finalizeThing = true;
	}
}