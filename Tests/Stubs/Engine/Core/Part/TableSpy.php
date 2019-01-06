<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\Core\Part;

use ClassicPress\SimpleDBBackup\Database\Metadata\Table;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\PartStatus;
use ClassicPress\SimpleDBBackup\Engine\StepAware;
use ClassicPress\SimpleDBBackup\Engine\StepAwareInterface;

class TableSpy implements WarningsAwareInterface, ErrorAwareInterface, StepAwareInterface
{
	use ErrorAware, WarningsAware, StepAware;

	public static $instanceParams = [];

	public $meta;

	public function __construct($timer, $db, $logger, $config, $outputWriter, $backupWriter, Table $tableMeta, $memInfo)
	{
		self::$instanceParams[] = $tableMeta;

		$this->meta = $tableMeta;
		$this->setStep($this->meta->getName());
	}

	public function tick()
	{
		$this->setSubstep('1/1');

		return new PartStatus([
			'Done' => 1,
			'Domain' => '',
			'Step' => $this->meta->getName(),
			'Substep' => '1/1',
		]);
	}
}