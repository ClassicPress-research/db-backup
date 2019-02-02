<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table;

class NonCore extends AbstractFilter implements FilterInterface
{
	/**
	 * Remove any non-core tables. Non-core tables are those whose name does not start with the configured prefix of the
	 * site.
	 *
	 * @param   string[]  $tables
	 *
	 * @return  array
	 */
	public function filter(array $tables)
	{
		// Get the configured prefix and its length
		$prefix       = $this->getDbo()->getPrefix();
		$prefixLength = strlen($prefix);

		return array_filter($tables, function ($table) use ($prefix, $prefixLength) {
			/**
			 * Only include this table if its name begins with the prefix (case-insensitive match).
			 *
			 * Why case-insensitive? Because MySQL can act weird on case-insensitive filesystems such as FAT, FAT32,
			 * exFAT, NTFS, HFS+ (case-insensitive variant) or APFS (case-insensitive variant).
			 */
			return @substr_compare($table, $prefix, 0, $prefixLength, true) === 0;
		});
	}

}