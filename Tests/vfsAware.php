<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests;


use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

trait vfsAware
{
	/**
	 * Virtual filesystem, used for testing
	 *
	 * @var vfsStreamDirectory
	 */
	protected $root;

	/**
	 * Execute this on the test case's setUp() method
	 */
	protected function setUp_vfsAware()
	{
		$this->root = vfsStream::setup('testing');
	}

}