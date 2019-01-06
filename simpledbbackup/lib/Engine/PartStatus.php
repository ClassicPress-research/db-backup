<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine;

use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorAwareInterface;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorException;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningException;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAwareInterface;
use Exception;

/**
 * An immutable object describing the status of an Engine Part
 *
 * @package ClassicPress\SimpleDBBackup\Engine
 */
final class PartStatus implements StatusInterface
{
	/**
	 * Have we finished running?
	 *
	 * @var  bool
	 */
	private $done = false;

	/**
	 * The name of the current engine domain
	 *
	 * @var  string
	 */
	private $domain = '';

	/**
	 * The name of the current engine part
	 *
	 * @var  string
	 */
	private $step = '';

	/**
	 * The user-friendly description of what we are doing now
	 *
	 * @var  string
	 */
	private $substep = '';

	/**
	 * The latest exception thrown by the engine part which prevents us from doing any more work
	 *
	 * @var  ErrorException
	 */
	private $error = null;

	/**
	 * A collection of exceptions about lesser issues which are not show-stoppers
	 *
	 * @var  WarningException[]
	 */
	private $warnings = array();

	/**
	 * Creates a new status object from an Engine Part object.
	 *
	 * This static method automatically detects whether the part supports reporting domains, steps / substeps, errors
	 * and warnings and adjusts itself accordingly.
	 *
	 * @param   PartInterface  $part
	 *
	 * @return  PartStatus
	 */
	public static function fromPart(PartInterface $part)
	{
		$options = [
			'Done'     => false,
			'Domain'   => '',
			'Step'     => '',
			'Substep'  => '',
			'Error'    => null,
			'Warnings' => [],
		];

		$options['Done'] = $part->getState() == PartInterface::STATE_FINALIZED;

		if ($part instanceof DomainAwareInterface)
		{
			$options['Domain'] = $part->getDomain();
		}

		if ($part instanceof StepAwareInterface)
		{
			$options['Step']    = $part->getStep();
			$options['Substep'] = $part->getSubstep();
		}

		if ($part instanceof ErrorAwareInterface)
		{
			$options['Error'] = $part->getError();
		}

		if ($part instanceof WarningsAwareInterface)
		{
			$options['Warnings'] = $part->getWarnings();
		}

		return new self($options);
	}

	/**
	 * Creates a new status object from a status array. This is a trivial interface to the constructor for fluency
	 * reasons only.
	 *
	 * @param   array  $retArray
	 *
	 * @return  PartStatus
	 *
	 * @codeCoverageIgnore
	 */
	public static function fromReturnArray(array $retArray)
	{
		return new self($retArray);
	}

	/**
	 * PartStatus constructor.
	 *
	 * The $parameters array has smart handling about errors and warnings. The error can be an ErrorException, any kind
	 * of exception or a string. The warnings can be an array containing any mix of WarningException, any kind of
	 * Exception or strings.
	 *
	 * @param   array   $parameters  A return array with the parameters.
	 */
	public function __construct(array $parameters)
	{
		if (isset($parameters['Done']))
		{
			$this->done = $parameters['Done'] == 1;
		}
		elseif (isset($parameters['HasRun']))
		{
			$this->done = $parameters['HasRun'] == 0;
		}

		if (isset($parameters['Domain']))
		{
			$this->domain = $parameters['Domain'];
		}

		if (isset($parameters['Step']))
		{
			$this->step = $parameters['Step'];
		}

		if (isset($parameters['Substep']))
		{
			$this->substep = $parameters['Substep'];
		}

		if (isset($parameters['Error']))
		{
			$this->setError($parameters['Error']);
		}

		if (isset($parameters['Warnings']))
		{
			$this->setWarnings($parameters['Warnings']);
		}
	}

	/**
	 * Export the status as an array.
	 *
	 * This is the same "return array" format we use in our other products such as Akeeba Backup, Akeeba Kickstart and
	 * Admin Tools. It's meant to be consumed by client-side JavaScript.
	 *
	 * @return  array
	 */
	public function toArray()
	{
		return [
			/**
			 * For legacy reasons HasRun is supposed to mean "this engine part has more steps left to run", therefore it
			 * is the opposite of done. That was an architecture decision made in 2006. For sanity's sake we keep it
			 * like that.
			 */
			'HasRun'   => (!$this->done) ? 1 : 0,
			'Done'     => $this->done ? 1 : 0,
			'Domain'   => $this->domain,
			'Step'     => $this->step,
			'Substep'  => $this->substep,
			'Error'    => $this->getErrorAsString(),
			'Warnings' => $this->getWarningsAsStings(),
		];
	}

	/**
	 * Used to convert the warning exceptions array into an array of strings. Only meant to be used internally by the
	 * toArray() method.
	 *
	 * @return  array
	 */
	private function getWarningsAsStings()
	{
		$warnings     = [];

		foreach ($this->warnings as $warning)
		{
			if (!($warning instanceof Exception))
			{
				continue;
			}

			$warnings[] = $warning->getMessage();
		}

		return $warnings;
	}

	/**
	 * Used to convert the error exception, if present, into a string. Only meant to be used internally by the toArray()
	 * method.
	 *
	 * @return  string
	 */
	private function getErrorAsString()
	{
		if ($this->error instanceof Exception)
		{
			return $this->error->getMessage();
		}

		return '';
	}

	/**
	 * Is the part done processing?
	 *
	 * @return  bool
	 *
	 * @codeCoverageIgnore
	 */
	public function isDone()
	{
		return $this->done;
	}

	/**
	 * Get the domain of the engine part
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * Get the step of the engine part
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getStep()
	{
		return $this->step;
	}

	/**
	 * Get the substep of the engine part
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getSubstep()
	{
		return $this->substep;
	}

	/**
	 * Get the error exception or null if no error is set
	 *
	 * @return  ErrorException
	 *
	 * @codeCoverageIgnore
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * Get the array of warning exceptions (warnings queue)
	 *
	 * @return  WarningException[]
	 *
	 * @codeCoverageIgnore
	 */
	public function getWarnings()
	{
		return $this->warnings;
	}

	/**
	 * Set the error. It can be an ErrorException, any other Exception (converted to ErrorException automatically) or
	 * a string. Empty strings and nulls result in the error being reset.
	 *
	 * @param   mixed  $error
	 *
	 * @return  void
	 */
	private function setError($error)
	{
		$this->error = null;

		if (empty($error))
		{
			return;
		}

		if (is_object($error) && ($error instanceof ErrorException))
		{
			$this->error = $error;

			return;
		}

		if (is_object($error) && ($error instanceof Exception))
		{
			$this->error = new ErrorException($error->getMessage(), $error->getCode(), $error->getPrevious());

			return;
		}

		if (is_string($error))
		{
			$this->error = new ErrorException($error, 0);

			return;
		}
	}

	/**
	 * Add a warning to the warnings queue. The warning can be a WarningException, any other exception (converted to a
	 * WarningException automatically) or a string. Empty strings and nulls result in no warning being added.
	 *
	 * @param   mixed  $warning
	 *
	 * @return  void
	 */
	private function addWarning($warning)
	{
		if (empty($warning))
		{
			return;
		}

		if (is_object($warning) && ($warning instanceof WarningException))
		{
			$this->warnings[] = $warning;

			return;
		}

		if (is_object($warning) && ($warning instanceof Exception))
		{
			$this->warnings[] = new WarningException($warning->getMessage(), $warning->getCode(), $warning->getPrevious());

			return;
		}

		if (is_string($warning))
		{
			$this->warnings[] = new WarningException($warning, 0);

			return;
		}
	}

	/**
	 * Set the warnings queue from an array. If the array is empty or consists of a mix of empty strings / nulls the
	 * warnings queue is reset. Same thing happens if it's not an array.
	 *
	 * @param   mixed  $warnings
	 *
	 * @return  void
	 */
	private function setWarnings($warnings)
	{
		$this->warnings = array();

		if (empty($warnings))
		{
			return;
		}

		if (!is_array($warnings))
		{
			return;
		}

		foreach ($warnings as $warning)
		{
			$this->addWarning($warning);
		}
	}
}