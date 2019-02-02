<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table;

class Sorter extends AbstractFilter implements FilterInterface
{
	/**
	 * A simple sorter which puts VIEWs after TABLEs
	 *
	 * @param   array  $tables
	 *
	 * @return  array
	 */
	public function filter(array $tables)
	{
		$realTables = [];
		$views      = [];
		$db         = $this->getDbo();

		// Sort tables into actual tables and views.
		foreach ($tables as $table)
		{
			$meta = $db->getTableMeta($table);

			// We know it's a view when it does not have a table engine.
			if (empty($meta->getEngine()))
			{
				$views[] = $table;

				continue;
			}

			$realTables[] = $table;
		}

		// Alpha sorting is not necessary but helps us with testing
		asort($realTables);
		asort($views);

		return array_merge($realTables, $views);
	}

}