<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Writer;

use ClassicPress\SimpleDBBackup\Writer\FileWriter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\visitor\vfsStreamStructureVisitor;

class FileWriterTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Virtual filesystem, used for testing
	 *
	 * @var vfsStreamDirectory
	 */
	private $root;

	protected function setUp()
	{
		parent::setUp();

		$this->root = vfsStream::setup('testing');
	}

	/**
	 * Test __construct. Use a brand new file which does not exist.
	 *
	 * @return void
	 */
	public function testConstructWithNewFile()
	{
		$filePath = $this->root->url() . '/foobar.txt';
		$dummy    = new FileWriter($filePath, false);

		self::assertEquals($filePath, $this->getObjectAttribute($dummy, 'filePath'));
		self::assertEquals(0, $this->getObjectAttribute($dummy, 'numParts'));
	}

	/**
	 * Test __construct. Try writing to a folder which is not writeable.
	 *
	 * @return void
	 */
	public function testConstructWithUnwriteableFolder()
	{
		vfsStream::newDirectory('baz', 0600)
			->chown(vfsStream::OWNER_USER_2)
			->at($this->root);

		$filePath = $this->root->url() . '/baz/foobar.txt';

		$this->expectException("RuntimeException");
		$this->expectExceptionMessage("Cannot open file “{$filePath}” for writing.");

		$dummy    = new FileWriter($filePath, false);
	}

	/**
	 * Test __construct. Use an existing file which is not writeable.
	 *
	 * @return void
	 */
	public function testConstructWithUnwriteableFile()
	{
		vfsStream::newFile('foobar.txt', 0600)
			->chown(vfsStream::OWNER_USER_2)
			->at($this->root);

		$filePath = $this->root->url() . '/foobar.txt';

		$this->expectException("RuntimeException");
		$this->expectExceptionMessage("Cannot open file “{$filePath}” for writing.");

		$dummy    = new FileWriter($filePath, false);
	}

	/**
	 * Test __construct. Use an existing multipart file set, using $reset = true (delete existing files)
	 *
	 * @return void
	 */
	public function testConstructWithReset()
	{
		$extensions = ['txt', 't01', 't02', 't99'];

		foreach($extensions as $extension)
		{
			vfsStream::newFile('foobar.' . $extension, 0644)
				->withContent('Ignored')
				->at($this->root);
		}

		$filePath = $this->root->url() . '/foobar.txt';

		$dummy    = new FileWriter($filePath, true);

		self::assertTrue($this->root->hasChild('foobar.txt'), 'Part #0 must exist.');
		self::assertEquals(0, $this->root->getChild('foobar.txt')->size(), 'Part #0 must be truncated on reset');
		self::assertFalse($this->root->hasChild('foobar.t01'), 'Part #1 must be deleted on reset.');
		self::assertFalse($this->root->hasChild('foobar.t02'), 'Part #2 must be deleted on reset.');
		self::assertTrue($this->root->hasChild('foobar.t99'), 'Non sequential part must not be deleted on reset.');
		self::assertNotEquals(0, $this->root->getChild('foobar.t99')->size(), 'Non sequential part must not be truncated on reset');
	}

	/**
	 * Test __construct. Use an existing multipart file set, using $reset = false (DO NOT delete existing files)
	 *
	 * @return void
	 */
	public function testConstructWithExistingParts()
	{
		$extensions = ['txt', 't01', 't02', 't99'];

		foreach($extensions as $extension)
		{
			vfsStream::newFile('foobar.' . $extension, 0644)
				->withContent('Ignored')
				->at($this->root);
		}

		$filePath = $this->root->url() . '/foobar.txt';

		$dummy    = new FileWriter($filePath, false);

		self::assertEquals(2, $this->getObjectAttribute($dummy, 'numParts'));

		self::assertTrue($this->root->hasChild('foobar.txt'), 'Part #0 must exist.');
		self::assertNotEquals(0, $this->root->getChild('foobar.txt')->size(), 'Part #0 must not be truncated');

		self::assertTrue($this->root->hasChild('foobar.t01'), 'Part #1 must exist.');
		self::assertNotEquals(0, $this->root->getChild('foobar.t01')->size(), 'Part #1 must not be truncated');

		self::assertTrue($this->root->hasChild('foobar.t02'), 'Part #2 must exist.');
		self::assertNotEquals(0, $this->root->getChild('foobar.t02')->size(), 'Part #2 must not be truncated');

		self::assertTrue($this->root->hasChild('foobar.t99'), 'Non-sequential part must exist.');
		self::assertNotEquals(0, $this->root->getChild('foobar.t99')->size(), 'Non-sequential part must not be truncated');
	}

	public function testGetListOfParts()
	{
		$extensions = ['txt', 't01', 't02', 't99'];

		foreach($extensions as $extension)
		{
			vfsStream::newFile('foobar.' . $extension, 0644)
				->withContent('Ignored')
				->at($this->root);
		}

		$filePath = $this->root->url() . '/foobar.txt';

		$dummy    = new FileWriter($filePath, false);

		self::assertEquals(2, $this->getObjectAttribute($dummy, 'numParts'));

		$partsList = $dummy->getListOfParts();
		$expected = [
			$this->root->url() . '/foobar.txt',
			$this->root->url() . '/foobar.t01',
			$this->root->url() . '/foobar.t02',
		];

		self::assertEquals($expected, $partsList);
	}

	public function testGetNumberOfParts()
	{
		$extensions = ['txt', 't01', 't02', 't99'];

		foreach($extensions as $extension)
		{
			vfsStream::newFile('foobar.' . $extension, 0644)
				->withContent('Ignored')
				->at($this->root);
		}

		$filePath = $this->root->url() . '/foobar.txt';

		$dummy    = new FileWriter($filePath, false);

		self::assertEquals(2, $this->getObjectAttribute($dummy, 'numParts'));

		self::assertEquals(3, $dummy->getNumberOfParts());

	}

	public function testSetMaxFileSize()
	{
		$filePath = $this->root->url() . '/foobar.txt';
		$dummy    = new FileWriter($filePath, false);

		$dummy->setMaxFileSize(-1);
		self::assertEquals(0, $this->getObjectAttribute($dummy,'maxFileSize'), 'Negative part sizes must be squashed to zero');

		$dummy->setMaxFileSize(123);
		self::assertEquals(123, $this->getObjectAttribute($dummy,'maxFileSize'), 'Positive part sizes must be accepted');

		$dummy->setMaxFileSize(0);
		self::assertEquals(0, $this->getObjectAttribute($dummy,'maxFileSize'), 'Zero part size must be accepted');
	}

	/**
	 * Write a line without expecting trouble
	 *
	 * @return void
	 */
	public function testWriteLineNoProblem()
	{
		$filePath = $this->root->url() . '/foobar.txt';
		$dummy    = new FileWriter($filePath, false);

		$line = 'In PHP we trust';
		$dummy->writeLine($line, "\n");

		unset($dummy);

		self::assertEquals($line . "\n", file_get_contents($filePath));
	}

	/**
	 * Write a line to a .php file without expecting trouble
	 *
	 * @return void
	 */
	public function testWriteLineWithPHPFileNoProblem()
	{
		$filePath = $this->root->url() . '/foobar.php';
		$dummy    = new FileWriter($filePath, false);

		$preamble = $this->getObjectAttribute($dummy, 'phpPreamble');

		$line = 'In PHP we trust';
		$dummy->writeLine($line, "\n");

		unset($dummy);

		self::assertEquals($preamble . "\n" . $line . "\n", file_get_contents($filePath));
	}

	/**
	 * Write two lines, expect a new part file to be generated (line size smaller than part size)
	 *
	 * @return void
	 */
	public function testWriteLineChunked()
	{
		$filePath = $this->root->url() . '/foobar.txt';
		$filePart2 = $this->root->url() . '/foobar.t01';
		$dummy    = new FileWriter($filePath, false);
		$dummy->setMaxFileSize(25);

		$line = 'In PHP we trust';
		$dummy->writeLine($line, "\n");
		$dummy->writeLine($line, "\n");

		unset($dummy);

		self::assertTrue($this->root->hasChild('foobar.t01'), 'Part #1 must exist.');
		self::assertEquals($line . "\n", file_get_contents($filePath), 'Part #0 must have the first line.');
		self::assertEquals($line . "\n", file_get_contents($filePart2), 'Part #1 must have the second line');

	}

	/**
	 * Write two lines to a .php file, expect a new part file to be generated (line size smaller than part size)
	 *
	 * @return void
	 */
	public function testWriteLineWithPHPFileChunked()
	{
		$filePath = $this->root->url() . '/foobar.php';
		$filePart2 = $this->root->url() . '/foobar.01.php';
		$dummy    = new FileWriter($filePath, false);
		$dummy->setMaxFileSize(113);

		$preamble = $this->getObjectAttribute($dummy, 'phpPreamble');

		$line = 'In PHP we trust';
		$dummy->writeLine($line, "\n");
		$dummy->writeLine($line, "\n");

		unset($dummy);

		self::assertTrue($this->root->hasChild('foobar.01.php'), 'Part #1 must exist.');
		self::assertEquals($preamble . "\n" . $line . "\n", file_get_contents($filePath), 'Part #0 must have the first line.');
		self::assertEquals($preamble . "\n" . $line . "\n", file_get_contents($filePart2), 'Part #1 must have the second line');

	}

	/**
	 * Write two lines, expect a new part file to be generated (line size bigger than part size)
	 *
	 * @return void
	 */
	public function testWriteLineChunkedVeryLong()
	{
		$filePath = $this->root->url() . '/foobar.txt';
		$filePart2 = $this->root->url() . '/foobar.t01';
		$dummy    = new FileWriter($filePath, false);
		$dummy->setMaxFileSize(25);

		$line = 'In PHP we trust and we get to write lines longer than the part size';
		$dummy->writeLine($line, "\n");
		$dummy->writeLine($line, "\n");

		unset($dummy);

		self::assertTrue($this->root->hasChild('foobar.t01'), 'Part #1 must exist.');
		self::assertEquals($line . "\n", file_get_contents($filePath), 'Part #0 must have the first line.');
		self::assertEquals($line . "\n", file_get_contents($filePart2), 'Part #1 must have the second line');
	}


	/**
	 * Write a line, expect an error (filesystem ran out of free space)
	 *
	 * @return void
	 */
	public function testWriteLineRunningOutOfSpace()
	{
		$filePath = $this->root->url() . '/foobar.txt';
		$dummy    = new FileWriter($filePath, false);

		vfsStream::setQuota(10);

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage('It looks like you run out of disk space. I tried writing 16 bytes, only 10 were written.');

		$line = 'In PHP we trust';
		$dummy->writeLine($line, "\n");
	}
}
