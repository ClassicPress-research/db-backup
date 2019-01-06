<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine;

use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAwareInterface;
use ClassicPress\SimpleDBBackup\Timer\TimerAware;
use ClassicPress\SimpleDBBackup\Timer\TimerAwareInterface;
use ClassicPress\SimpleDBBackup\Timer\TimerInterface;

/**
 * An abstract class which implements the PartInterface
 *
 * @package ClassicPress\SimpleDBBackup\Engine
 */
abstract class AbstractPart implements PartInterface, TimerAwareInterface, ErrorAwareInterface, WarningsAwareInterface, StepAwareInterface, DomainAwareInterface
{
	use TimerAware;
	use ErrorAware;
	use WarningsAware;
	use StepAware;
	use DomainAware;

	/**
	 * The current state of the engine part
	 *
	 * @var  int
	 */
	protected $state = PartInterface::STATE_INIT;

	/**
	 * The configuration parameters for this engine part
	 *
	 * @var  Configuration
	 */
	protected $config;

	/**
	 * AbstractPart constructor.
	 *
	 * @param   TimerInterface       $timer   The timer object used by this part
	 * @param   array|Configuration  $config  Configuration parameters as a keyed array
	 */
	public function __construct(TimerInterface $timer, $config)
	{
		$this->setTimer($timer);

		$this->state  = PartInterface::STATE_INIT;

		if (is_array($config))
		{
			$config = new Configuration($config);
		}

		if (!is_object($config) || !($config instanceof Configuration))
		{
			$config = new Configuration([]);
		}

		$this->config = $config;

	}

	/**
	 * Process one or more steps, until the timer tells us that we are running out of time.
	 *
	 * This method calls one of _prepare(), _run() and _finalize() depending on the internal state. If the state is
	 * STATE_FINALIZED no further action will be taken, just the status object returned.
	 *
	 * @return  PartStatus
	 */
	public final function tick()
	{
		switch ($this->state)
		{
			case PartInterface::STATE_INIT:
				$this->prepare();
				$this->nextState();
				break;

			case PartInterface::STATE_PREPARED:
			case PartInterface::STATE_RUNNING:
				$this->mainProcessing();
				break;

			case PartInterface::STATE_POSTRUN:
				$this->finalize();
				$this->nextState();
				break;
		}

		return $this->getStatus();
	}

	/**
	 * Bump the internal state to the next one. The state progression is Init, Prepared, Running, Post-run, Finalized.
	 * Call this from _prepare(), _run() and _finalize() as necessary
	 *
	 * @return  void
	 */
	protected final function nextState()
	{
		switch ($this->state)
		{
			case PartInterface::STATE_INIT:
				$this->state = PartInterface::STATE_PREPARED;
				break;

			case PartInterface::STATE_PREPARED:
				$this->state = PartInterface::STATE_RUNNING;
				break;

			case PartInterface::STATE_RUNNING:
				$this->state = PartInterface::STATE_POSTRUN;
				break;

			case PartInterface::STATE_POSTRUN:
				$this->state = PartInterface::STATE_FINALIZED;
				break;
		}
	}

	/**
	 * Executes when the state is STATE_INIT. You are supposed to set up internal objects and do any other kind of
	 * preparatory work which does not take too much time.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	abstract protected function prepare();

	/**
	 * Main processing. Calls _afterPrepare() exactly once and process() at least once.
	 *
	 * @return  void
	 */
	private final function mainProcessing()
	{
		// Is this the first tick of a running state? Run afterPrepare().
		if ($this->state == PartInterface::STATE_PREPARED)
		{
			$this->afterPrepare();
			$this->nextState();

			return;
		}

		if ($this->process() === false)
		{
			$this->nextState();
		}
	}

	/**
	 * Executes exactly once, at the first step of the run loop. Use it to perform any kind of set up that takes a
	 * non-trivial amount of time. This is optional and can be left blank.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	protected function afterPrepare()
	{

	}

	/**
	 * Main processing. Here you do the bulk of the work. When you no longer have any more work to do return boolean
	 * false.
	 *
	 * @return  bool  false to indicate you are done, true to indicate more work is to be done.
	 *
	 * @codeCoverageIgnore
	 */
	abstract protected function process();

	/**
	 * Finalization. Here you are supposed to perform any kind of tear down after your work is done.
	 *
	 * @return  void
	 *
	 * @codeCoverageIgnore
	 */
	abstract protected function finalize();

	/**
	 * Returns the status object for this Engine Part.
	 *
	 * @return  PartStatus
	 *
	 * @codeCoverageIgnore
	 */
	public final function getStatus()
	{
		return PartStatus::fromPart($this);
	}

	/**
	 * Get the Engine Part running state. See the constants defined in the PartInterface.
	 *
	 * @return  int
	 *
	 * @codeCoverageIgnore
	 */
	public final function getState()
	{
		return $this->state;
	}

	/**
	 * Propagate errors and warnings from an object, if the object supports the ErrorAwareInterface and / or
	 * WarningsAwareInterface. Also propagates the step and substep if the object supports StepAwareInterface.
	 *
	 * @param   object  $object  The object to propagate from
	 *
	 * @return  void
	 */
	public final function propagateFromObject($object)
	{
		if ($object instanceof ErrorAwareInterface)
		{
			$this->inheritErrorFrom($object);
		}

		if ($object instanceof WarningsAwareInterface)
		{
			$this->inheritWarningsFrom($object);
		}

		if ($object instanceof StepAwareInterface)
		{
			$this->setStep($object->getStep());
			$this->setSubstep($object->getSubstep());
		}
	}
}