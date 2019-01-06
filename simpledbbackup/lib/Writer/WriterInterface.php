<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Writer;

use RuntimeException;

interface WriterInterface
{
	/**
	 * Create a file writer
	 *
	 * @param   string  $filePath  Absolute file path to the file to write
	 * @param   bool    $reset     Should I delete any existing file(s)?
	 *
	 * @throws  RuntimeException  When we cannot open the file for writing.
	 */
	public function __construct($filePath, $reset = true);

	/**
	 * Returns the nominal file path (part #0) being used by this writer.
	 *
	 * @return  string
	 */
	public function getFilePath();

	/**
	 * Maximum allowed file size before we start splitting it into parts. This sets the part size in bytes.
	 *
	 * The default is zero which means that no archive splitting will take place UNLESS we cannot write to
	 * the file. That would indicate that the host applies a maximum file size limit.
	 *
	 * @param   int  $bytes
	 *
	 * @return  void
	 */
	public function setMaxFileSize($bytes);

	/**
	 * Get the maximum file size option.
	 *
	 * @return  int
	 */
	public function getMaxFileSize();

	/**
	 * Write a line to the file
	 *
	 * @param   string  $line  The line contents
	 * @param   string  $eol   The end-of-line character, defaults to "\n"
	 *
	 * @return  void
	 *
	 * @throws  RuntimeException  When it's impossible to write to a file no matter what we try to do.
	 */
	public function writeLine($line, $eol = "\n");

	/**
	 * How many parts have been created so far?
	 *
	 * @return  int
	 */
	public function getNumberOfParts();

	/**
	 * Return a list with the absolute file names of the parts created so far.
	 *
	 * @return  string[]
	 */
	public function getListOfParts();

	/**
	 * Remove all parts known to us
	 *
	 * @return  void
	 */
	public function reset();
}