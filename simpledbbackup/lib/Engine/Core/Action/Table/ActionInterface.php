<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Action\Table;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Database\Metadata\Column;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

/**
 * Interface to per-table actions
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Action\Table
 */
interface ActionInterface
{
	/**
	 * ActionInterface constructor.
	 *
	 * @param   Driver           $db      The database driver this action will be using
	 * @param   LoggerInterface  $logger  The logger this action will be using
	 * @param   Configuration    $config  The configuration for this object
	 */
	public function __construct(Driver $db, LoggerInterface $logger, Configuration $config);

	/**
	 * Take a table connection and figure out if we need to run table-level DDL queries.
	 *
	 * @param   Table     $table    The metadata of the table to be processed
	 * @param   Column[]  $columns  The metadata of the table columns
	 *
	 * @return  SQL
	 */
	public function processTable(Table $table, array $columns);
}