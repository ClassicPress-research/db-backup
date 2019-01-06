<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database\Query;


use ClassicPress\SimpleDBBackup\Database\QueryLimitable;

/**
 * Query builder class for databases using the PDO connector
 *
 * @codeCoverageIgnore
 */
class Pdomysql extends Pdo implements QueryLimitable
{
	use LimitAware;
}