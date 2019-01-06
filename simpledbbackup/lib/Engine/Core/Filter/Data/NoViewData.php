<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Filter\Data;


use ClassicPress\SimpleDBBackup\Database\Metadata\Table;

/**
 * A data dump filter to prevent dumping the data of VIEWs
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Filter\Data
 */
class NoViewData extends AbstractFilter
{
	/**
	 * Disable backing up the data of VIEWs
	 *
	 * @param   Table  $table
	 *
	 * @return  bool
	 */
	public function filter(Table $table)
	{
		// If there's no ENGINE this is a VIEW and I have to NOT dump its data (I can't insert data back into a view).
		if (empty($table->getEngine()))
		{
			return false;
		}

		return true;
	}

}