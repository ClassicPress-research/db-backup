<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Action\Database;

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Database\Metadata\Database;
use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

/**
 * Interface to per-database action classes
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Action\Database
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
	 * Take a database connection and figure out if we need to run database-level DDL queries.
	 *
	 * @param   Database  $db  The metadata of the database we are processing
	 *
	 * @return  SQL
	 */
	public function processDatabase(Database $db);
}