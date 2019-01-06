<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core;


use ClassicPress\SimpleDBBackup\Writer\NullWriter;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;

/**
 * Trait for classes implementing an output SQL writer
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core
 */
trait OutputWriterAware
{
	/**
	 * The writer to use for action SQL file output
	 *
	 * @var  WriterInterface
	 */
	protected $outputWriter;

	/**
	 * Get the output writer object
	 *
	 * @return WriterInterface
	 */
	public function getOutputWriter()
	{
		if (empty($this->outputWriter))
		{
			$this->outputWriter = new NullWriter('');
		}

		return $this->outputWriter;
	}

	/**
	 * Set the output writer
	 *
	 * @param   WriterInterface  $outputWriter
	 */
	protected function setOutputWriter(WriterInterface $outputWriter)
	{
		$this->outputWriter = $outputWriter;
	}
}