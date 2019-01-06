<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Database;

use ClassicPress\SimpleDBBackup\Database\Driver\Mysqli;
use ClassicPress\SimpleDBBackup\Detection\WordPress;

/**
 * A trait which allows a Driver to retrieve WordPress database connection information.
 *
 * @package ClassicPress\SimpleDBBackup\Database
 */
trait WordPressTrait
{
	/**
	 * The root of the WordPress site installation. Used to automatically find connection details when we are running
	 * outside of the WordPress application.
	 *
	 * @var  string|null
	 */
	protected $wpSiteRoot = null;

	/**
	 * Returns an options array used to connect to the default WordPress database.
	 *
	 * @return  array
	 */
	public function getWordPressConnectionInformation()
	{
		$defaultOptions = [];

		// If we are inside WordPress set the site root to ABSPATH
		if (defined('ABSPATH'))
		{
			$defaultOptions['site_root'] = ABSPATH;
		}

		// If we are inside WordPress set the db info from what WordPress gives us
		if (defined('WPINC'))
		{
			global $wpdb;

			$defaultOptions['prefix'] = $wpdb->prefix;
			$defaultOptions['host'] = DB_HOST;
			$defaultOptions['user'] = DB_USER;
			$defaultOptions['password'] = DB_PASSWORD;
			$defaultOptions['database'] = DB_NAME;
			$defaultOptions['connection'] = self::getWordPressDBConnectionObject();

			if (is_null($defaultOptions['connection']))
			{
				/**
				 * If we failed to get a reference to the connection we unset this key and let the driver connect to the
				 * database with its own connection. This will work on *most* servers.
				 */
				unset ($defaultOptions['connection']);
			}
		}

		/**
		 * Make sure WordPress' internal connection object is the same kind as our object. That is:
		 *
		 * -- WP is MySQLi, our object is MySQLi driver: use it
		 * -- WP is MySQL classic, our object is MySQLi driver: do NOT use it
		 * -- WP is MySQLi, our object is MySQL classic driver: do NOT use it
		 * -- WP is MySQL classic, our object is MySQL classic driver: use it
		 *
		 * In the cases we cannot use the connection the Driver object will fall back to creating a fresh database
		 * connection using the database name, hostname, username, password and prefix.
		 */
		if (array_key_exists('connection', $defaultOptions))
		{
			$isConnectionMySQLi = is_object($defaultOptions['connection']) and ($defaultOptions['connection'] instanceof \mysqli);
			$isDriverMySQLi = $this instanceof Mysqli;

			if ($isConnectionMySQLi !== $isDriverMySQLi)
			{
				unset($defaultOptions['connection']);
			}
		}

		// We have either a connection or connection information; return
		if (array_key_exists('host', $defaultOptions) || array_key_exists('connection', $defaultOptions))
		{
			return $defaultOptions;
		}

		// So, we were not inside WordPress. Do we have adequate connection information to reconnect?
		if (isset($this->options['host']) && !empty($this->options['host']) && !empty($this->_database) && !empty($this->tablePrefix))
		{
			return $defaultOptions;
		}

		// Ugh, we do not have enough information to reconnect. Do I have a site root?
		$siteRoot = !empty($this->wpSiteRoot) ? $this->wpSiteRoot : null;
		$siteRoot = (empty($siteRoot) && array_key_exists('site_root', $defaultOptions)) ? $defaultOptions['site_root'] : null;

		// No site root. Ka-boom.
		if (empty($siteRoot))
		{
			throw new \LogicException("I do not run inside WordPress and I am not given a path to its root. Cannot figure out to connect to the database. I am dying now.");
		}

		// Detect WP's connection information and hope they are right
		$wpDetection = new WordPress($siteRoot);
		$detected = $wpDetection->getDbInformation();

		$defaultOptions['host'] = $detected['host'];
		$defaultOptions['user'] = $detected['username'];
		$defaultOptions['password'] = $detected['password'];
		$defaultOptions['database'] = $detected['name'];
		$defaultOptions['prefix'] = $detected['nprefixame'];

		return $defaultOptions;
	}

	/**
	 * Get the WordPress database connection handler, either a mysql resource or a mysqli object
	 *
	 * @return  resource|\mysqli|null
	 */
	public static function getWordPressDBConnectionObject()
	{
		global $wpdb;

		if (!isset($wpdb))
		{
			return null;
		}

		if (isset($wpdb->dbh))
		{
			return $wpdb->dbh;
		}

		try
		{
			$refObject = new \ReflectionObject($wpdb);
			$refProp = $refObject->getProperty('dbh');
			$refProp->setAccessible(true);
			return $refProp->getValue($wpdb);
		}
		catch (\ReflectionException $e)
		{
			// If this fails we fall back to connecting to the database WITHOUT reusing WP's connection
		}

		return null;
	}

	/**
	 * Re-initializes the driver object with the specified options
	 *
	 * @param   array  $defaultOptions
	 *
	 * @return  void
	 */
	public function reinitializeConnectionWith(array $defaultOptions = [])
	{
		$options = array_merge($defaultOptions, $this->options);

		$options['host']     = (isset($options['host'])) ? $options['host'] : 'localhost';
		$options['user']     = (isset($options['user'])) ? $options['user'] : 'root';
		$options['password'] = (isset($options['password'])) ? $options['password'] : '';
		$options['database'] = (isset($options['database'])) ? $options['database'] : '';
		$options['select']   = (isset($options['select'])) ? (bool) $options['select'] : true;
		$options['port']     = null;
		$options['socket']   = null;

		$this->_database   = (isset($options['database'])) ? $options['database'] : '';
		$this->tablePrefix = (isset($options['prefix'])) ? $options['prefix'] : '';
		$this->wpSiteRoot  = (isset($options['site_root'])) ? $options['site_root'] : '';

		// Did we get passed a connection resource?
		if (isset($options['connection']))
		{
			$this->connection = $options['connection'];
		}

		// Set class options.
		$this->options = $options;

		// Reset internal counters, logs, etc
		$this->count       = 0;
		$this->errorNum    = 0;
		$this->log         = [];

		// Remember to actually reconnect the database (in case we don't have a connection handler)
		$this->connect();
	}
}