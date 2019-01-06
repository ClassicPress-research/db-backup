<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\ErrorHandling;

use ClassicPress\SimpleDBBackup\Logger\LoggerAwareInterface;

/**
 * A Trait to implement WarningsAwareInterface functionality
 *
 * @package ClassicPress\SimpleDBBackup\Engine\ErrorHandling
 */
trait WarningsAware
{
	/**
	 * The warnings known to this object
	 *
	 * @var  WarningException[]
	 */
	protected $warnings = array();

	/**
	 * The maximum number of warnings to be help in the warnings queue
	 *
	 * @var  int
	 */
	private $warningsQueueSize = 10;

	/**
	 * Return the latest error exception thrown by the object implementing this interface
	 *
	 * @return  WarningException[]
	 */
	public function getWarnings()
	{
		return $this->warnings;
	}

	/**
	 * Adds a warning exception to the queue.
	 *
	 * @param   WarningException  $e  The warning exception to add to the queue
	 *
	 * @return  void
	 */
	public function addWarning(WarningException $e)
	{
		$numWarnings = count($this->warnings);

		if (($this->warningsQueueSize != 0) && ($numWarnings >= $this->warningsQueueSize))
		{
			$offset         = $numWarnings - $this->warningsQueueSize + 1;
			$this->warnings = array_slice($this->warnings, $offset);
		}

		$this->warnings[] = $e;

		if (($this instanceof LoggerAwareInterface) && is_object($this->getLogger()))
		{
			$this->getLogger()->warning($e->getMessage());
		}
	}

	/**
	 * Adds a warning to the queue from a warning message string. This creates a WarningException, adds it to the queue
	 * and returns it to the caller.
	 *
	 * @param   string  $message  The warning message to add
	 *
	 * @return  WarningException
	 */
	public function addWarningMessage($message)
	{
		if (!is_string($message))
		{
			throw new \InvalidArgumentException(sprintf("Parameter \$message to %s::%s must be a string, %s given", __CLASS__, __METHOD__, gettype($message)));
		}

		if (empty($message))
		{
			return null;
		}

		$warning = new WarningException($message);

		$this->addWarning($warning);

		return $warning;
	}

	/**
	 * Clears the warnings queue
	 *
	 * @return  void
	 */
	public function resetWarnings()
	{
		$this->warnings = array();
	}

	/**
	 * Inherits the warnings from another WarningsAware object and clears its queue
	 *
	 * @param   WarningsAwareInterface $object The object to inherit from
	 *
	 * @return  void
	 */
	public function inheritWarningsFrom(WarningsAwareInterface $object)
	{
		$this->warnings = $object->getWarnings();

		$object->resetWarnings();
	}

	/**
	 * Get the warnings queue length
	 *
	 * The object only holds up to this many warnings in a sliding buffer. That is to say, adding a warning past the
	 * maximum queue length makes the oldest warning slide away from the buffer; the second warning becomes first and
	 * so on and so forth and finally the new warning is appended to the end of the queue.
	 *
	 * Set the queue length to zero to allow an infinite number of warnings, bound only by the PHP memory limit.
	 *
	 * @return  int
	 *
	 * @codeCoverageIgnore
	 */
	public function getWarningsQueueLength()
	{
		return $this->warningsQueueSize;
	}

	/**
	 * Set the warnings queue length.
	 *
	 * The object only holds up to this many warnings in a sliding buffer. That is to say, adding a warning past the
	 * maximum queue length makes the oldest warning slide away from the buffer; the second warning becomes first and
	 * so on and so forth and finally the new warning is appended to the end of the queue.
	 *
	 * Set the queue length to zero to allow an infinite number of warnings, bound only by the PHP memory limit.
	 *
	 * @param   int  $length
	 *
	 * @return  void
	 */
	public function setWarningsQueueLength($length)
	{
		// Make sure length is a *positive* integer
		$length = max($length, 0);
		$this->warningsQueueSize = $length;
		$numWarnings = count($this->warnings);

		if (($this->warningsQueueSize != 0) && ($numWarnings >= $this->warningsQueueSize))
		{
			$offset         = $numWarnings - $this->warningsQueueSize;
			$this->warnings = array_slice($this->warnings, $offset);
		}
	}

}