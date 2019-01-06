<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core;

use ClassicPress\SimpleDBBackup\Writer\WriterInterface;

/**
 * Interface to classes implementing an output SQL writer
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core
 */
interface OutputWriterAwareInterface
{
	/**
	 * Returns the reference to the class' output writer object
	 *
	 * @return  WriterInterface
	 */
	public function getOutputWriter();
}