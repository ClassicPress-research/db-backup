<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core;

use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

/**
 * Configuration for Simple DB Backup
 *
 * The purpose of this class is to provide a single point of collection for the configuration of a backup job. These
 * configuration parameters will be used by the actual domain objects which do work. This lets us have less verbose
 * constructors at the expense of introducing an Anaemic Domain Model anti-pattern. It's still better than magic
 * key-value arrays which provide no validation.
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core
 */
class Configuration
{
	/**
	 * Output SQL file path. Empty = no SQL output.
	 *
	 * @var  string
	 */
	private $outputSQLFile = '';

	/**
	 * Log file path. Empty = no log.
	 *
	 * @var  string
	 */
	private $logFile = '';

	/**
	 * Minimum severity level to report to the log
	 *
	 * @var  int
	 */
	private $minLogLevel = LoggerInterface::SEVERITY_DEBUG;

	/**
	 * Minimum execution time, in seconds. It must be a positive, non-zero float.
	 *
	 * Note that depending on the server the implementation may not take into account the decimal part of the value. On
	 * most servers we will use usleep() which lets us apply microsecond accuracy but on some servers we have to resort
	 * to sleep() which only takes integer seconds. On those servers we will round up the time to sleep.
	 *
	 * @var  float
	 */
	private $minExecutionTime = 2;

	/**
	 * Maximum execution time, in seconds
	 *
	 * @var  int
	 */
	private $maxExecutionTime = 5;

	/**
	 * Execution time runtime bias, in percentage points (valid range: 1 to 100 inclusive)
	 *
	 * @var int
	 */
	private $runtimeBiasPercent = 75;

	/**
	 * Maximum number of database rows to process at once
	 *
	 * @var  int
	 */
	private $maxBatchSize = 1000;

	/**
	 * Maximum compound query size. Set to zero to create a new INSERT INTO statement per row.
	 *
	 * @var  int
	 */
	private $maxQuerySize = 263168;

	/**
	 * The human-readable description for the backup job which will be recorded in the database
	 *
	 * @var  string
	 */
	private $description = '';

	/**
	 * Configuration constructor.
	 *
	 * Creates a Configuration object from a configuration keyed array.
	 *
	 * @param   array  $params  A key-value array with the configuration variables.
	 *
	 * @codeCoverageIgnore
	 */
	public function __construct(array $params)
	{
		$this->setFromParameters($params);
	}

	/**
	 * Return the output SQL file path. Empty = no SQL output
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getOutputSQLFile()
	{
		return $this->outputSQLFile;
	}

	/**
	 * Set the output SQL file path. Empty = no SQL output
	 *
	 * @param   string  $outputSQLFile
	 *
	 * @return  Configuration
	 *
	 * @codeCoverageIgnore
	 */
	protected function setOutputSQLFile($outputSQLFile)
	{
		if (!is_string($outputSQLFile))
		{
			return $this;
		}

		$this->outputSQLFile = $outputSQLFile;

		return $this;
	}

	/**
	 * Get the log file pathname
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getLogFile()
	{
		return $this->logFile;
	}

	/**
	 * Set the log file pathname
	 *
	 * @param  string  $logFile
	 *
	 * @codeCoverageIgnore
	 */
	protected function setLogFile($logFile)
	{
		$this->logFile = $logFile;
	}

	/**
	 * Get the minimum log level
	 *
	 * @return  int
	 *
	 * @codeCoverageIgnore
	 */
	public function getMinLogLevel()
	{
		return $this->minLogLevel;
	}

	/**
	 * Set the minimum log level
	 *
	 * @param  int  $minLogLevel
	 *
	 * @codeCoverageIgnore
	 */
	protected function setMinLogLevel($minLogLevel)
	{
		$this->minLogLevel = $minLogLevel;
	}

	/**
	 * Get the minimum execution time
	 *
	 * @return  float
	 *
	 * @codeCoverageIgnore
	 */
	public function getMinExecutionTime()
	{
		return $this->minExecutionTime;
	}

	/**
	 * Set the minimum execution time. It must be a positive float.
	 *
	 * @param   float  $minExecutionTime
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	public function setMinExecutionTime($minExecutionTime)
	{
		$minExecutionTime = max(0, $minExecutionTime);

		$this->minExecutionTime = $minExecutionTime;
	}

	/**
	 * Return the maximum execution time in seconds.
	 *
	 * @return  int
	 */
	public function getMaxExecutionTime()
	{
		return $this->maxExecutionTime;
	}

	/**
	 * Set the maximum execution time in seconds. It must be a positive (and non-zero) integer.
	 *
	 * @param   int  $maxExecutionTime
	 *
	 * @codeCoverageIgnore
	 */
	public function setMaxExecutionTime($maxExecutionTime)
	{
		$this->maxExecutionTime = max(1, (int)$maxExecutionTime);
	}

	/**
	 * Return the execution time bias as an integer amount of percentage points.
	 *
	 * @return  int
	 *
	 * @codeCoverageIgnore
	 */
	public function getRuntimeBiasPercent()
	{
		return $this->runtimeBiasPercent;
	}

	/**
	 * Set the execution time bias as an integer amount of percentage points.
	 *
	 * @param   int  $runtimeBiasPercent
	 *
	 * @codeCoverageIgnore
	 */
	public function setRuntimeBiasPercent($runtimeBiasPercent)
	{
		$runtimeBiasPercent       = max(1, (int) $runtimeBiasPercent);
		$this->runtimeBiasPercent = min($runtimeBiasPercent, 100);
	}

	/**
	 * Get the maximum number of rows to process at once
	 *
	 * @return  int
	 *
	 * @codeCoverageIgnore
	 */
	public function getMaxBatchSize()
	{
		return $this->maxBatchSize;
	}

	/**
	 * Set the maximum number of rows to process at once
	 *
	 * @param   int  $maxBatchSize
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	protected function setMaxBatchSize($maxBatchSize)
	{
		$this->maxBatchSize = max((int) $maxBatchSize, 1);
	}

	/**
	 * Get the maximum query size in bytes.
	 *
	 * @return  int
	 *
	 * @codeCoverageIgnore
	 */
	public function getMaxQuerySize()
	{
		return $this->maxQuerySize;
	}

	/**
	 * Set the maximum query size in bytes. Must be a positive integer (non-zero).
	 *
	 * @param   int  $maxQuerySize
	 */
	public function setMaxQuerySize($maxQuerySize)
	{
		$this->maxQuerySize = max(0, (int)$maxQuerySize);
	}

	/**
	 * Populates the Configuration from a key-value parameters array.
	 *
	 * @param   array  $params  A key-value array with the configuration variables.
	 *
	 * @return void
	 */
	protected function setFromParameters(array $params)
	{
		if (empty($params))
		{
			return;
		}

		foreach ($params as $k => $v)
		{
			if (!property_exists($this, $k))
			{
				continue;
			}

			$method = 'set' . ucfirst($k);

			if (!method_exists($this, $method))
			{
				continue;
			}

			call_user_func_array([$this, $method], [$v]);
		}
	}

	/**
	 * Get the human-readable description
	 *
	 * @return  string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set the human-readable description
	 *
	 * @param   string  $description
	 */
	protected function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * Convert the configuration to a key-value array. The result can be fed to the setFromParameters() method to create
	 * the configuration object afresh. Therefore it can be used to save an Akeeba Replace job.
	 *
	 * @return  array
	 */
	public function toArray()
	{
		$ret = [];

		$refObject = new \ReflectionObject($this);

		foreach ($refObject->getProperties(\ReflectionProperty::IS_PRIVATE) as $refProp)
		{
			$propName = $refProp->getName();
			$methods = [
				'get' . ucfirst($propName),
				'is' . ucfirst($propName)
			];

			foreach ($methods as $method)
			{
				if (method_exists($this, $method))
				{
					break;
				}

				$method = '';
			}

			if (empty($method))
			{
				continue;
			}

			$ret[$propName] = $this->{$method}();
		}

		return $ret;
	}
}