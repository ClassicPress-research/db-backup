<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Filter\Data;


use ClassicPress\SimpleDBBackup\Database\Metadata\Table;

/**
 * A data dump filter to prevent dumping the data of database tables with special engine types
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Filter\Data
 */
class SpecialEngines extends AbstractFilter
{
	/**
	 * Disable backing up the data of tables with special engine types
	 *
	 * @param   Table  $table
	 *
	 * @return  bool
	 */
	public function filter(Table $table)
	{
		return !in_array(strtoupper($table->getEngine()), [
			'BLACKHOLE',
			'EXAMPLE',
			'FEDERATED',
			'MEMORY',
			'HEAP',
			'MERGE',
			'MRG_MYISAM',
		]);
	}

}