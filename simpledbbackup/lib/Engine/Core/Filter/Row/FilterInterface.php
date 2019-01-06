<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Filter\Row;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

/**
 * Interface to a row list filter
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Filter\Row
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
	 * Check whether the table row should be processed or not
	 *
	 * @param   $tableName  string  The name of the table being processed
	 * @param   $row        array   The row being processed
	 *
	 * @return  bool  True to allow processing
	 */
	public function filter($tableName, array $row);
}