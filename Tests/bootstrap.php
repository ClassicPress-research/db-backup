<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

// Export the test path as a constant
define('DBBACKUP_TEST_ROOT', __DIR__);

// Maximise error reporting.
ini_set('zend.ze1_compatibility_mode', '0');
error_reporting(E_ALL & ~E_STRICT);
ini_set('display_errors', 1);

// This is necessary for the session testing
ini_set('session.use_only_cookies', false);
ini_set('session.use_cookies', false);
ini_set('session.use_trans_sid', false);
ini_set('session.cache_limiter', null);

// Timezone fix; avoids errors printed out by PHP 5.3.3+
if (function_exists('date_default_timezone_get') && function_exists('date_default_timezone_set'))
{
	if (function_exists('error_reporting'))
	{
		$oldLevel = error_reporting(0);
	}

	$serverTimezone = @date_default_timezone_get();

	if (empty($serverTimezone) || !is_string($serverTimezone))
	{
		$serverTimezone = 'UTC';
	}

	if (function_exists('error_reporting'))
	{
		error_reporting($oldLevel);
	}

	@date_default_timezone_set($serverTimezone);
}

// Include the library autoloader
if (false == include __DIR__ . '/../simpledbbackup/lib/Autoloader/Autoloader.php')
{
	echo 'ERROR: The Autoloader was not found' . "\n";

	exit(1);
}

// Register the test classes with our PSR-4 autoloader
\ClassicPress\SimpleDBBackup\Autoloader\Autoloader::getInstance()->addMap('ClassicPress\\SimpleDBBackup\\Tests\\', __DIR__);

/**
 * Load the environment variables from the .env file(s)
 *
 * We are catching the InvalidPathException exception which we expect in the Travis CI environment. In that case we are
 * checking if the environment variables are set manually.
 **/
try
{
	$dotEnv = new \Dotenv\Dotenv(__DIR__);
	$dotEnv->load();

	// Make sure the tests are properly configured
	$dotEnv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);
	$dotEnv->required(['DB_HOST', 'DB_NAME', 'DB_USER'])->notEmpty();
}
catch (\Dotenv\Exception\InvalidPathException $e)
{
	foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $k)
	{
		if (!isset($_ENV[$k]))
		{
			throw $e;
		}
	}

	foreach (['DB_HOST', 'DB_NAME', 'DB_USER'] as $k)
	{
		if (empty($k))
		{
			throw new RuntimeException("Environment variable $k must not be empty.");
		}
	}
}