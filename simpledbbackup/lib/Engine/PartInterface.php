<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine;

/**
 * Interface to an engine part.
 *
 * Each engine part is a class which is designed to break work down to smaller chunks. Each engine tick processes one of
 * these work chunks. The actions of the engine are recorded by the logger. The engine ticks as long as the attached
 * timer has not run out of available time. The engine part returns a status which will bubble up to the outermost part,
 * the only one that the interface talks to.
 *
 * @package ClassicPress\SimpleDBBackup\Engine
 */
interface PartInterface
{
	const STATE_INIT = 1;

	const STATE_PREPARED = 2;

	const STATE_RUNNING = 3;

	const STATE_POSTRUN = 4;

	const STATE_FINALIZED = 5;

	/**
	 * Process one or more steps, until the timer tells us that we are running out of time.
	 *
	 * @return  PartStatus
	 */
	public function tick();

	/**
	 * Returns the status object for this Engine Part.
	 *
	 * @return  PartStatus
	 */
	public function getStatus();

	/**
	 * Get the Engine Part running state. See the constants defined in the PartInterface.
	 *
	 * @return  int
	 */
	public function getState();

	/**
	 * Propagate errors and warnings from an object, if the object supports the ErrorAwareInterface and / or
	 * WarningsAwareInterface. Also propagates the step and substep if the object supports StepAwareInterface.
	 *
	 * @param   object  $object  The object to propagate from
	 *
	 * @return  void
	 */
	public function propagateFromObject($object);
}
