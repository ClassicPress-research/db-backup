<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\ErrorHandling;

use ClassicPress\SimpleDBBackup\Logger\LoggerAwareInterface;

/**
 * A Trait to implement ErrorAwareInterface functionality
 *
 * @package ClassicPress\SimpleDBBackup\Engine\ErrorHandling
 */
trait ErrorAware
{
	/**
	 * The error known to this object
	 *
	 * @var  ErrorException
	 */
	protected $error = null;

	/**
	 * Return the latest error exception thrown by the object implementing this interface
	 *
	 * @return  ErrorException
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Sets the error exception to the object
	 *
	 * @param   ErrorException  $e  The error to set
	 *
	 * @return  void
	 */
	public function setError(ErrorException $e)
	{
		$this->error = $e;

		if (($this instanceof LoggerAwareInterface) && is_object($this->getLogger()))
		{
			$this->getLogger()->error($e->getMessage());
		}
	}

	/**
	 * Sets the error from an error message string. This creates an ErrorException, assigns it to the object and returns
	 * it to the caller.
	 *
	 * @param   string  $message
	 *
	 * @return  ErrorException
	 */
	public function setErrorMessage($message)
	{
		if (!is_string($message))
		{
			throw new \InvalidArgumentException(sprintf("Parameter \$message to %s::%s must be a string, %s given", __CLASS__, __METHOD__, gettype($message)));
		}

		if (empty($message))
		{
			$this->resetError();

			return null;
		}

		$error = new ErrorException($message);

		$this->setError($error);

		return $error;
	}

	/**
	 * Clears the error
	 *
	 * @return  void
	 */
	public function resetError()
	{
		$this->error = null;
	}

	/**
	 * Inherits the error from another ErrorAware object and clears its error
	 *
	 * @param   ErrorAwareInterface $object The object to inherit from
	 *
	 * @return  void
	 */
	public function inheritErrorFrom(ErrorAwareInterface $object)
	{
		$this->error = $object->getError();

		$object->resetError();
	}
}