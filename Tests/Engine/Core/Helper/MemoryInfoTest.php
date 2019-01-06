<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core\Helper;

use ClassicPress\SimpleDBBackup\Engine\Core\Helper\MemoryInfo;

class MemoryInfoTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @param   string  $input
	 * @param   int     $expected
	 *
	 * @dataProvider  humanToIntegerBytesProvider
	 */
	public function testHumanToIntegerBytes($input, $expected)
	{
		$dummy = new MemoryInfo();
		$actual = $dummy->humanToIntegerBytes($input);

		self::assertEquals($expected, $actual);
	}

	public static function humanToIntegerBytesProvider()
	{
		return [
			'Simple value, M'          => ['128M', 134217728],
			'Simple value, MB'         => ['128MB', 134217728],
			'Simple value, m'          => ['128m', 134217728],
			'Simple value, mb'         => ['128mb', 134217728],
			'Simple value, Mb'         => ['128Mb', 134217728],
			'Simple value with space'  => ['128 M', 134217728],
			'Decimal value with space' => ['128.1 M', 134322585],
			'Numeric value'            => [123, 123],
		];
	}

	/**
	 * @param $value
	 * @param $precision
	 * @param $expected
	 *
	 * @dataProvider integerBytesToHumanProvider
	 */
	public function testIntegerBytesToHuman($value, $precision, $expected)
	{
		$dummy = new MemoryInfo();
		$actual = $dummy->integerBytesToHuman($value, $precision);

		self::assertEquals($expected, $actual);
	}

	public static function integerBytesToHumanProvider()
	{
		return [
			'1.23 MB, precision 2'    => [1289748, 2, '1.23 MB'],
			'1.23 MB, precision 1'    => [1289748, 1, '1.2 MB'],
			'1.23 MB, precision 0'    => [1289748, 0, '1 MB'],
			'1.23 MB, precision -123' => [1289748, -123, '1 MB'],
			'1.54 KB, precision 2'    => [1576, 2, '1.54 KB'],
			'1.54 KB, precision 1'    => [1576, 1, '1.5 KB'],
			'1.54 KB, precision 0'    => [1576, 0, '2 KB'],
			'1.55 KB, precision 2'    => [1590, 2, '1.55 KB'],
			'1.55 KB, precision 1'    => [1590, 1, '1.6 KB'],
			'1.55 KB, precision 0'    => [1590, 0, '2 KB'],
		];
	}

	public function testGetMemoryLimit()
	{
		$dummy    = new MemoryInfo();
		$actual   = $dummy->getMemoryLimit();
		$expected = max(0, $dummy->humanToIntegerBytes(ini_get('memory_limit')));
		self::assertEquals($expected, $actual);
	}


}
