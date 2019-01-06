<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Filter\Table;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

/**
 * Interface to a table list filter.
 *
 * Remember to add the filters to ClassicPress\SimpleDBBackup\Engine\Core\Database::$filters
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
	 * Filter the table list, returning the filtered result
	 *
	 * @param   array  $tables
	 *
	 * @return  array
	 */
	public function filter(array $tables);
}