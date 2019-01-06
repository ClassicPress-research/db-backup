<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Writer;

/**
 * A WriterInterface implementation which does absolutely nothing
 *
 * @package ClassicPress\SimpleDBBackup\Writer
 */
class NullWriter implements WriterInterface
{
	public function __construct($filePath, $reset = true)
	{
	}

	public function getFilePath()
	{
		return '';
	}

	public function setMaxFileSize($bytes)
	{
	}

	public function getMaxFileSize()
	{
		return 0;
	}

	public function writeLine($line, $eol = "\n")
	{
	}

	public function getNumberOfParts()
	{
		return 0;
	}

	public function getListOfParts()
	{
		return [];
	}

	public function reset()
	{
	}

}