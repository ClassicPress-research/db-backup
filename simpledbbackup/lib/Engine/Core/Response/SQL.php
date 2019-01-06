<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Response;

use ClassicPress\SimpleDBBackup\Database\Query;

/**
 * Describes the immutable response returned by a database or table Action object.
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core
 */
class SQL
{
	/**
	 * The query to perform an action.
	 *
	 * @var  string[]
	 */
	private $actionQueries = [];

	/**
	 * SQLResponse constructor.
	 *
	 * @param   string[] $actionQueries
	 */
	public function __construct($actionQueries)
	{
		$this->actionQueries      = is_array($actionQueries) ? $actionQueries : null;
	}

	/**
	 * Does this response define action queries?
	 *
	 * @return  bool
	 */
	public function hasActionQueries()
	{
		return !empty($this->actionQueries);
	}

	/**
	 * Get the action queries.
	 *
	 * @return  string[]
	 */
	public function getActionQueries()
	{
		return $this->actionQueries;
	}
}