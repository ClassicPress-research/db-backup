<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database\Metadata;

/**
 * A database table's metadata
 *
 * @package ClassicPress\SimpleDBBackup\Database\Metadata
 */
class Table
{
	/**
	 * Table name
	 *
	 * @var  string
	 */
	private $name = '';

	/**
	 * Database engine
	 *
	 * @var  string
	 */
	private $engine = '';

	/**
	 * Average row length in bytes
	 *
	 * @var  int
	 */
	private $averageRowLength = 0;

	/**
	 * Table collation, if it's different than the database's
	 *
	 * @var  string
	 */
	private $collation = '';

	/**
	 * Creates a table definition from the results of a MySQL query, either SHOW TABLE STATUS or by selecting from
	 * information_schema.TABLES.
	 *
	 * Example queries understood by this method:
	 *
	 * SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'replacetest' AND TABLE_NAME = 'akr_dbtest';
	 * SHOW TABLE STATUS WHERE Name = 'akr_dbtest';
	 *
	 * @param   array  $result  The MySQL result I will be processing
	 *
	 * @return  static
	 */
	public static function fromDatabaseResult(array $result)
	{
		$name             = array_key_exists('Name', $result) ? $result['Name'] : $result['TABLE_NAME'];
		$engine           = array_key_exists('Engine', $result) ? $result['Engine'] : $result['ENGINE'];
		$averageRowLength = array_key_exists('Avg_row_length', $result) ? $result['Avg_row_length'] : $result['AVG_ROW_LENGTH'];
		$collation        = array_key_exists('Collation', $result) ? $result['Collation'] : $result['TABLE_COLLATION'];

		return new static($name, $engine, $averageRowLength, $collation);
	}

	/**
	 * TableDefinition constructor.
	 *
	 * @param   string  $name              The name of the table
	 * @param   string  $engine            The table engine
	 * @param   int     $averageRowLength  Average row length, in bytes
	 * @param   string  $collation         Table collation (if different to database's)
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct($name, $engine, $averageRowLength, $collation)
	{
		$this->name             = $name;
		$this->engine           = $engine;
		$this->averageRowLength = $averageRowLength;
		$this->collation        = $collation;
	}

	/**
	 * The name of the table
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * The storage engine of the table
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getEngine()
	{
		return $this->engine;
	}

	/**
	 * The average row length, in bytes
	 *
	 * @return  int
	 *
	 * @codeCoverageIgnore
	 */
	public function getAverageRowLength()
	{
		return $this->averageRowLength;
	}

	/**
	 * The collation of the table, if different to the database's default collation
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getCollation()
	{
		return $this->collation;
	}
}