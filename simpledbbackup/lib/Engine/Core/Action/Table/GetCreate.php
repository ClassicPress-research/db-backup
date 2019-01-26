<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Action\Table;

use ClassicPress\SimpleDBBackup\Database\Metadata\Column;
use ClassicPress\SimpleDBBackup\Database\Metadata\Table;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;

/**
 * A table action to backup the CREATE statement of a table or VIEW.
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Action\Table
 */
class GetCreate extends AbstractAction implements ActionInterface
{
	/**
	 * Return the DDL to create a table or a view
	 *
	 * @param   Table    $table   The metadata of the table to be processed
	 * @param   Column[] $columns The metadata of the table columns
	 *
	 * @return  SQL
	 *
	 * @throws  \RuntimeException  On database error
	 */
	public function processTable(Table $table, array $columns)
	{
		/**
		 * Step 1. Get the raw CREATE query from MySQL.
		 *
		 * If the database user does not have adequate privileges to run SHOW CREATE the DB layer generates a
		 * RuntimeException we let bubble up.
		 */
		$db              = $this->getDbo();
		$tableName       = $table->getName();
		$temp            = $db->setQuery("SHOW CREATE TABLE `$tableName`")->loadRow();
		$createStatement = $temp[1];
		unset($temp);

		/**
		 * Step 2. Detect if this is a table or a view based on the CREATE statement
		 */
		$pattern = '/^CREATE(.*) VIEW (.*)/i';
		$result  = preg_match($pattern, $createStatement, $matches);
		$isView  = $result === 1;

		/**
		 * Step 3. Post-process the CREATE statement to prevent common restoration issues.
		 *
		 * This step is slightly "lossy" but allows you to restore the generated SQL on a different MySQL / MariaDB /
		 * Percona version than the one it was taken on.
		 */

		// Replace newlines with spaces. This helps us simplify the restoration script because one line = 1 SQL query.
		$createStatement = str_replace("\n", " ", $createStatement) . ";\n";
		$createStatement = str_replace("\r", " ", $createStatement);
		$createStatement = str_replace("\t", " ", $createStatement);

		// Post processing depending on whether it's a view or a table.
		$createStatement = $isView ? $this->postProcessView($createStatement) : $this->postProcessTable($createStatement);

		return new SQL([$createStatement]);
	}

	/**
	 * Post process a CREATE VIEW statement.
	 *
	 * @param   string  $createStatement
	 *
	 * @return  string
	 */
	protected function postProcessView($createStatement)
	{
		/**
		 * Views may contain the database name followed by the table name, always quoted e.g. `db`.`table_name`
		 * We need to replace all these instances with just the table name. The only reliable way to do that is to look
		 * for "`db`.`" and replace it with "`".
		 */
		$db = $this->getDbo();
		$dbName          = $db->qn($db->getDatabase());
		$dummyQuote      = $db->qn('foo');
		$findWhat        = $dbName . '.' . substr($dummyQuote, 0, 1);
		$replaceWith     = substr($dummyQuote, 0, 1);
		$createStatement = str_replace($findWhat, $replaceWith, $createStatement);

		/**
		 * Newer versions of MySQL return a CREATE VIEW with ALGORITHM and DEFINER properties before the VIEW keyword:
		 * CREATE ALGORITHM=UNDEFINED DEFINER='root@localhost' VIEW `foobar`...
		 * The DEFINER should not be backed up because restoration is not guaranteed to be made by the same user or a
		 * root user (which can override the definer). The ALGORITHM, however, is very important. Without it the VIEW
		 * may break. So we have to check for it, isolate it, and reconstruct the CREATE statement in a portable
		 * manner.
		 */
		$pos_view = strpos($createStatement, ' VIEW ');

		if ($pos_view > 7)
		{
			// Only post process if there are view properties between the CREATE and VIEW keywords
			$propString = substr($createStatement, 7, $pos_view - 7); // Properties string
			// Fetch the ALGORITHM={UNDEFINED | MERGE | TEMPTABLE} keyword
			$algoString = '';
			$algo_start = strpos($propString, 'ALGORITHM=');

			if ($algo_start !== false)
			{
				$algo_end   = strpos($propString, ' ', $algo_start);
				$algoString = substr($propString, $algo_start, $algo_end - $algo_start + 1);
			}

			// Create our modified create statement
			$createStatement = 'CREATE OR REPLACE ' . $algoString . substr($createStatement, $pos_view);
		}

		return $createStatement;
	}

	/**
	 * Post process a CREATE TABLE statement.
	 *
	 * @param   string  $createStatement
	 *
	 * @return  string
	 */
	protected function postProcessTable($createStatement)
	{
		// USING BTREE / USING HASH in indices causes issues migrating from MySQL 5.1+ hosts to MySQL 5.0 hosts
		$createStatement = str_replace(' USING BTREE', ' ', $createStatement);
		$createStatement = str_replace(' USING HASH', ' ', $createStatement);

		// Translate TYPE= to ENGINE=
		$createStatement = str_replace('TYPE=', 'ENGINE=', $createStatement);

		return $createStatement;
	}
}