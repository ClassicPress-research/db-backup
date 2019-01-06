<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database\Metadata;

/**
 * A table column's metadata
 *
 * @package ClassicPress\SimpleDBBackup\Database\Metadata
 */
class Column
{
	/**
	 * The name of the column
	 *
	 * @var  string
	 */
	private $columnName = '';

	/**
	 * The full type definition of the column
	 *
	 * @var  string
	 */
	private $type = '';

	/**
	 * The column collation
	 *
	 * @var  string
	 */
	private $collation = '';

	/**
	 * The name of the key this column belongs to
	 *
	 * @var  string
	 */
	private $keyName = '';

	/**
	 * Is this column an auto-incrementing one?
	 *
	 * @var  bool
	 */
	private $autoIncrement = false;

	/**
	 * The default value for this column
	 *
	 * @var  mixed
	 */
	private $default = null;

	/**
	 * Creates a column definition from a MySQL result describing the column, either from SHOW FULL COLUMNS or from a
	 * query to information_schema.COLUMNS.
	 *
	 * Example queries whose results I understand:
	 *
	 * SHOW FULL COLUMNS FROM `example`
	 * SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'yourDB' AND TABLE_NAME = 'example';
	 *
	 * @param   array  $result  The MySQL result I will be processing
	 *
	 * @return  static
	 */
	public static function fromDatabaseResult(array $result)
	{
		$columnName    = array_key_exists('Field', $result) ? $result['Field'] : $result['COLUMN_NAME'];
		$type          = array_key_exists('Type', $result) ? $result['Type'] : $result['COLUMN_TYPE'];
		$collation     = array_key_exists('Collation', $result) ? $result['Collation'] : $result['COLLATION_NAME'];
		$keyName       = array_key_exists('Key', $result) ? $result['Key'] : $result['COLUMN_KEY'];
		$autoIncrement = (array_key_exists('Extra', $result) ? $result['Extra'] : $result['EXTRA']) == 'auto_increment';
		$default       = array_key_exists('Default', $result) ? $result['Default'] : $result['COLUMN_DEFAULT'];

		return new static($columnName, $type, $collation, $keyName, $autoIncrement);
	}

	/**
	 * ColumnDefinition constructor.
	 *
	 * @param   string $columnName    Name of the column
	 * @param   string $type          Full type, e.g. "varchar(255)" or "int(10) unsigned"
	 * @param   string $collation     The collation for this column
	 * @param   string $keyName       The key name it belongs to. Key "PRI" means "part of primary key"
	 * @param   bool   $autoIncrement Is it an auto-increment column? If it is it's also considered a primary key
	 * @param   mixed  $default       The default value for this column
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct($columnName, $type, $collation, $keyName, $autoIncrement, $default = null)
	{
		$this->columnName    = $columnName;
		$this->type          = $type;
		$this->collation     = $collation;
		$this->keyName       = $keyName;
		$this->autoIncrement = $autoIncrement;
		$this->default       = $default;
	}

	/**
	 * Get the name of the column
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getColumnName()
	{
		return $this->columnName;
	}

	/**
	 * Get the full type definition for the column
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Get the column's collation, if different to the table's collation
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getCollation()
	{
		return $this->collation;
	}

	/**
	 * Get the name of the key the table belongs to (if any)
	 *
	 * @return  string|null
	 *
	 * @codeCoverageIgnore
	 */
	public function getKeyName()
	{
		return $this->keyName;
	}

	/**
	 * Is this an auto-increment field?
	 *
	 * @codeCoverageIgnore
	 *
	 * @return  bool
	 */
	public function isAutoIncrement()
	{
		return $this->autoIncrement;
	}

	/**
	 * Is this field a primary key to the table?
	 *
	 * @codeCoverageIgnore
	 *
	 * @return  bool
	 */
	public function isPK()
	{
		return $this->autoIncrement || ($this->keyName == 'PRI');
	}

	/**
	 * Is this field of a text type?
	 *
	 * @return  bool
	 */
	public function isText()
	{
		$type = $this->type;

		if (empty($type))
		{
			return false;
		}

		// Remove parentheses, indicating field options / size (they don't matter in type detection)
		if (strpos($type, '(') === false)
		{
			$type .= '()';
		}

		list($type, $parameters) = explode('(', $type);

		// If we have options after a space, remove them
		if (strpos($type, ' ') !== false)
		{
			list($type, $otherOptions) = explode(' ', $type);
		}

		$type = strtolower($type);

		$textTypes = [
			'varchar', 'text', 'char', 'character varying', 'nvarchar', 'nchar', 'tinytext', 'smalltext', 'longtext', 'mediumtext'
		];

		return in_array($type, $textTypes);
	}

	/**
	 * The default value for this column
	 *
	 * @return  mixed
	 */
	public function getDefault()
	{
		return $this->default;
	}
}