<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Timer;


interface TimerInterface
{
	/**
	 * Public constructor, creates the timer object and calculates the execution
	 * time limits.
	 *
	 * @param   integer  $max_exec_time  Maximum execution time, in seconds
	 * @param   integer  $runtime_bias   Runtime bias factor, as percent points of the max execution time
	 *
	 * @return  void
	 */
	public function __construct($max_exec_time = 5, $runtime_bias = 75);

	/**
	 * Gets the number of seconds left, before we hit the "must break" threshold
	 *
	 * @return  float
	 */
	public function getTimeLeft();

	/**
	 * Gets the time elapsed since object creation/unserialization, effectively
	 * how long this step is running
	 *
	 * @return  float
	 */
	public function getRunningTime();

	/**
	 * Reset the timer
	 *
	 * @return  void
	 */
	public function resetTime();
}