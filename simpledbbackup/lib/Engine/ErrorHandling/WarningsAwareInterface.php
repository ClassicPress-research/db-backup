<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */


namespace ClassicPress\SimpleDBBackup\Engine\ErrorHandling;

/**
 * Interface to objects which support reporting non-show-stopper warnings known to them
 *
 * @package ClassicPress\SimpleDBBackup\Engine
 */
interface WarningsAwareInterface
{
	/**
	 * Return the warning exceptions known to the object implementing this interface
	 *
	 * @return  WarningException[]
	 */
	public function getWarnings();

	/**
	 * Adds a warning exception to the queue.
	 *
	 * @param   WarningException  $e  The warning exception to add to the queue
	 *
	 * @return  void
	 */
	public function addWarning(WarningException $e);

	/**
	 * Adds a warning to the queue from a warning message string. This creates a WarningException, adds it to the queue
	 * and returns it to the caller.
	 *
	 * @param   string  $message  The warning message to add
	 *
	 * @return  WarningException
	 */
	public function addWarningMessage($message);

	/**
	 * Clears the warnings queue
	 *
	 * @return  void
	 */
	public function resetWarnings();

	/**
	 * Inherits the warnings from another WarningsAware object and clears its queue
	 *
	 * @param   WarningsAwareInterface $object The object to inherit from
	 *
	 * @return  void
	 */
	public function inheritWarningsFrom(WarningsAwareInterface $object);

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
	 */
	public function getWarningsQueueLength();

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
	public function setWarningsQueueLength($length);
}