<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database\Driver;


use ClassicPress\SimpleDBBackup\Database\WordPressTrait;

/**
 * A MySQLi/mysqlnd (MySQL Improved and MySQL Native Driver) connection driver which automatically connects
 * to WordPress' database.
 *
 * @package ClassicPress\SimpleDBBackup\Database\Driver
 */
class WordPressMySQLi extends Mysqli
{
	use WordPressTrait;

	/**
	 * Test to see if the MySQL connector is available and the WordPress database is also using a MySQL classic
	 * connection.
	 *
	 * @return  boolean  True on success, false otherwise.
	 *
	 */
	public static function isSupported()
	{
		if (!parent::isSupported())
		{
			return false;
		}

		// If we are running inside WordPress we can perform more accurate checks
		if (defined('WPINC'))
		{
			$dbh = self::getWordPressDBConnectionObject();

			if (!is_object($dbh))
			{
				return false;
			}

			if (!($dbh instanceof \mysqli))
			{
				return false;
			}

			return true;
		}

		return true;
	}

	/**
	 * Constructor.
	 *
	 * @param   array  $options  List of options used to configure the connection
	 *
	 */
	public function __construct(array $options)
	{
		parent::__construct($options);

		$wpOptions = $this->getWordPressConnectionInformation();
		$this->reinitializeConnectionWith($wpOptions);
	}

	/**
	 * Destructor.
	 *
	 * If we are reusing WordPress' connection object we just dispose of it. Otherwise we close the database
	 * connection.
	 */
	public function __destruct()
	{
		// If we are reusing another DB driver's connection we just remove the reference
		if (isset($this->options['connection']))
		{
			unset($this->options['connection']);

			$this->connection = null;

			return;
		}

		// Otherwise we manage our own connection, therefore we need to disconnect
		if (is_callable($this->connection, 'close'))
		{
			mysqli_close($this->connection);
		}
	}

	/**
	 * Called on unserialization. Reconnects to the WordPress database and reinitializes the database object,
	 * resetting stats.
	 *
	 * @return void
	 */
	public function __wakeup()
	{
		$wpOptions = $this->getWordPressConnectionInformation();
		$this->reinitializeConnectionWith($wpOptions);

		$this->connect();
	}

	public function connect()
	{
		if ($this->connection)
		{
			return;
		}

		$this->connection = $this->getWordPressDBConnectionObject();

		if ($this->connection)
		{
			return;
		}

		parent::connect();
	}


}