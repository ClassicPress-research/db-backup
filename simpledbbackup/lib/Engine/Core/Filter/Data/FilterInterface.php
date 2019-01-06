<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Filter\Data;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

/**
 * Interface to a table data dump filter.
 *
 * Remember to add the filters to ClassicPress\SimpleDBBackup\Engine\Core\Table::$dataFilters
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table
 */
interface FilterInterface
{
	/**
	 * FilterInterface  constructor.
	 *
	 * @param   LoggerInterface  $logger   The logger used to log our actions
	 * @param   Driver           $db       The database connection object
	 * @param   Configuration    $config   The engine configuration
	 */
	public function __construct(LoggerInterface $logger, Driver $db, Configuration $config);

	/**
	 * Should I back up the contents of this TABLE / VIEW?
	 *
	 * Return true to allow the table data to be backed up.
	 *
	 * @param   Table  $table  The metadata of the table whose data we're about to back up.
	 *
	 * @return  bool
	 */
	public function filter(Table $table);
}