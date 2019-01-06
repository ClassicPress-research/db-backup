<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core;


class ConfigurationProvider
{
	public static function testSetExcludeRowsProvider()
	{
		return [
			// $input, $expected
			'Empty array'              => [
				[], [],
			],
			'Invalid table names'      => [
				[
					12       => 'dog',
					''       => ['lol'],
					' '      => ['lol'],
					"\r\n\t" => ['lol'],
					'valid'  => ['foo'],
				],
				[
					'valid' => ['foo'],
				],
			],
			'Empty rows and row lists' => [
				[
					'invalid1' => 'dog',
					'invalid2' => [],
					'invalid3' => ['', ' ', "\r\n\t"],
					'valid'    => ['foo'],
				],
				[
					'valid' => ['foo'],
				],
			],
			'Duplicate tables due to whitespace' => [
				[
					' valid' => ['foo'],
					'valid' => ['bar'],
					'valid ' => ['baz'],
				],
				[
					'valid' => ['foo', 'bar', 'baz'],
				]
			],
			'Unique rows' => [
				[
					'valid ' => ['foo', ' bar', 'bar', 'bar ', 'baz'],
				],
				[
					// The key to 'baz' is 4 because elements 1, 2 and 3 are identical before array_unique. Then
					// array_unique squashes them but does not reorder element with key 4 ('baz').
					'valid' => ['foo', 'bar', 4 => 'baz'],
				]
			],
		];
	}

	public static function testSetExcludeTablesProvider()
	{
		return [
			// $input, $expected
			'Empty array' => [
				[], []
			],
			'Invalid table names' => [
				[42, array('nope'), (object)['foo' => 'bar'], 'valid'],
				['valid']
			],
			'Empty tables' => [
				['', ' ', '    ', "\t", 'valid'],
				['valid']
			],
			'Duplicates' => [
				['valid', 'valid', 'valid2', ' valid2', 'valid2 '],
				// The array key is 2, not 1, because of the way array_unique works.
				// Array keys are ignored when using the array so we don't have to reset the keys.
				['valid', 2 => 'valid2']
			],
		];
	}
}