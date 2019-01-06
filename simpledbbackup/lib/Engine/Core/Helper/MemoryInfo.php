<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Helper;

/**
 * Provides information about the usage and limits of PHP memory
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Helper
 */
class MemoryInfo
{
	/**
	 * Get the PHP memory limit in bytes
	 *
	 * @return int  Memory limit in bytes
	 */
	public function getMemoryLimit()
	{
		// If we can't get the real memory limit we assume a conservative 32MB
		// @codeCoverageIgnoreStart
		if (!function_exists('ini_get'))
		{
			return 33554432;
		}
		// @codeCoverageIgnoreEnd

		$memLimit = ini_get("memory_limit");

		if (is_numeric($memLimit) && ($memLimit < 0))
		{
			// A negative memory limit means no memory limit, see http://php.net/manual/en/ini.core.php#ini.memory-limit
			return 0;
		}

		$memLimit = $this->humanToIntegerBytes($memLimit);

		return max($memLimit, 0);
	}

	/**
	 * Returns the memory currently in use, in bytes.
	 *
	 * The reason we have this trivial method is merely to be able to mock it during testing.
	 *
	 * @return  int
	 *
	 * @codeCoverageIgnore
	 */
	public function getMemoryUsage()
	{
		return memory_get_usage();
	}

	/**
	 * Converts a human formatted size to integer representation of bytes,
	 * e.g. 1M to 1024768
	 *
	 * @param   string  $setting  The value in human readable format, e.g. "1M"
	 *
	 * @return  integer  The value in bytes
	 */
	public function humanToIntegerBytes($setting)
	{
		$val = trim($setting);
		$last = strtolower(substr($val, -1));

		$oneToLast = '0';

		if ($last == 'b')
		{
			$oneToLast = substr($val, -2);
			$newLast   = strtolower(substr($val, -2));
		}

		if (!is_numeric($oneToLast))
		{
			$last = $newLast;
		}

		if (is_numeric($last))
		{
			return $setting;
		}

		$val = trim(substr($val, 0, -strlen($last)));
		$val = floatval($val);

		switch ($last)
		{
			case 'p':
			case 'pb':
				$val *= 1024 * 1024 * 1024 * 1024 * 1024;
				break;

			case 't':
			case 'tb':
				$val *= 1024 * 1024 * 1024 * 1024;
				break;

			case 'g':
			case 'gb':
				$val *= 1024 * 1024 * 1024;
				break;

			case 'm':
			case 'mb':
				$val *= 1024 * 1024;
				break;

			case 'k':
			case 'kb':
				$val *= 1024;
				break;

			case 'b':
				break;
		}

		return (int) $val;
	}

	/**
	 * Converts an integer to a human formatter representation, e.g. 1024768 to 1M
	 *
	 * @param   int  $size       The size to convert
	 * @param   int  $precision  Decimal points precision
	 *
	 * @return  string
	 */
	public function integerBytesToHuman($size, $precision = 2)
	{
		$precision = max(0, $precision);
		$unit      = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
		$i         = (int) floor(log($size, 1024));
		$format    = ($precision > 0) ? "%0.{$precision}f" : '%u';
		$rounded   = @round($size / pow(1024, $i), $precision);

		return sprintf($format, $rounded) . ' ' . $unit[$i];
	}
}