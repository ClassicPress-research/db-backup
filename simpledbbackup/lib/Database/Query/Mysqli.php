<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database\Query;

use ClassicPress\SimpleDBBackup\Database;

/**
 * Query Building Class for databases using the MySQLi connector.
 *
 * @codeCoverageIgnore
 */
class Mysqli extends Database\Query implements Database\QueryLimitable
{
	use LimitAware;
}
