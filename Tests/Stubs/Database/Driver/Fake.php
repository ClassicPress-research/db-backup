<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database\Driver;

require_once DBBACKUP_TEST_ROOT . '/Stubs/Database/Query/Fake.php';

use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Database\Query\Fake as FakeQuery;

/**
 * Fake database driver. Does absolutely nothing of substance.
 *
 * @package ClassicPress\SimpleDBBackup\Tests\Stubs\Database
 */
class Fake extends Driver
{

	public $name = 'fake';

	protected $nameQuote = '``';

	protected $nullDate = '1BC';

	protected static $dbMinimum = '12.1';

	public function __construct(array $options = [])
	{
		parent::__construct($options);

		if (isset($options['nameQuote']))
		{
			$this->nameQuote = $options['nameQuote'];
		}
	}


	public function connect()
	{
		return true;
	}

	public function connected()
	{
		return true;
	}

	public function disconnect()
	{
		return;
	}

	public function dropTable($table, $ifExists = true)
	{
		return $this;
	}

	public function escape($text, $extra = false)
	{
		return $extra ? "/$text//" : "_{$text}_";
	}

	protected function fetchArray($cursor = null)
	{
		return array();
	}

	public function fetchAssoc($cursor = null)
	{
		return array();
	}

	public function fetchObject($cursor = null, $class = 'stdClass')
	{
		return new $class;
	}

	public function freeResult($cursor = null)
	{
		return null;
	}

	public function getAffectedRows()
	{
		return 0;
	}

	public function getCollation()
	{
		return false;
	}

	public function getNumRows($cursor = null)
	{
		return 0;
	}

	public function getTableColumns($table, $typeOnly = true)
	{
		return array();
	}

	public function getTableCreate($tables)
	{
		return '';
	}

	public function getTableKeys($tables)
	{
		return array();
	}

	public function getTableList()
	{
		return array();
	}

	public function getVersion()
	{
		return '12.1';
	}

	public function insertid()
	{
		return 0;
	}

	public function lockTable($tableName)
	{
		return $this;
	}

	public function execute()
	{
		return false;
	}

	public function renameTable($oldTable, $newTable, $backup = null, $prefix = null)
	{
		return $this;
	}

	public function select($database)
	{
		return false;
	}

	public function setUTF()
	{
		return false;
	}

	public static function isSupported()
	{
		return true;
	}

	public function transactionCommit($toSavepoint = false)
	{
	}

	public function transactionRollback($toSavepoint = false)
	{
	}

	public function transactionStart($asSavepoint = false)
	{
	}

	public function unlockTables()
	{
		return $this;
	}
}
