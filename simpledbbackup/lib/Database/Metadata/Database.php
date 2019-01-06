<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database\Metadata;

/**
 * A database's metadata
 *
 * @package ClassicPress\SimpleDBBackup\Database\Metadata
 */
class Database
{
	/**
	 * Database name
	 *
	 * @var  string
	 */
	private $name;

	/**
	 * Default character set
	 *
	 * @var  string
	 */
	private $characterSet;

	/**
	 * Default collation
	 *
	 * @var  string
	 */
	private $collation;

	/**
	 * Create a database definition from a query result against INFORMATION_SCHEMA.SCHEMATA
	 *
	 * @param   array  $result  A row of the INFORMATION_SCHEMA.SCHEMATA table
	 *
	 * @return  static
	 */
	public static function fromDatabaseResult(array $result)
	{
		$name         = $result['SCHEMA_NAME'];
		$characterSet = $result['DEFAULT_CHARACTER_SET_NAME'];
		$collation    = $result['DEFAULT_COLLATION_NAME'];

		return new static($name, $characterSet, $collation);
	}

	/**
	 * DatabaseDefinition constructor.
	 *
	 * @param   string  $name          The database name
	 * @param   string  $characterSet  The default character set of the database
	 * @param   string  $collation     The default collation of the database
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct($name, $characterSet = 'utf8', $collation = 'utf8_general_ci')
	{
		$this->name         = $name;
		$this->characterSet = $characterSet;
		$this->collation    = $collation;
	}

	/**
	 * Returns the database name
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
	 * Returns the default character set of the database
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getCharacterSet()
	{
		return $this->characterSet;
	}

	/**
	 * Returns the default collation of the database
	 *
	 * @return string
	 *
	 * @codeCoverageIgnore
	 */
	public function getCollation()
	{
		return $this->collation;
	}


}