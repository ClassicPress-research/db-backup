<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Timer;

/**
 * A Trait to implement the TimerAwareInterface
 *
 * @package ClassicPress\SimpleDBBackup\Timer
 */
trait TimerAware
{
	/**
	 * The timer object
	 *
	 * @var  TimerInterface
	 */
	protected $timer = null;

	/**
	 * Assigns a Timer object.
	 *
	 * This should only be used internally by the constructor. The constructor itself should use explicit dependency
	 * injection.
	 *
	 * @param   TimerInterface  $timer  The timer object to assign
	 *
	 * @return  void
	 */
	public function setTimer(TimerInterface $timer)
	{
		$this->timer = $timer;
	}

	/**
	 * Returns a reference to the timer object. This should only be used internally.
	 *
	 * @return  TimerInterface
	 */
	public function getTimer()
	{
		return $this->timer;
	}
}