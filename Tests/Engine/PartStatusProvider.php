<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine;

use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\ErrorException;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningException;

abstract class PartStatusProvider
{
	public static function test__constructWithDoneProvider()
	{
		return [
			// $param, $expected
			[1, true],
			[0, false],
			[true, true],
			[false, false],
			['whatever', false],
			['1', true],
			['0', false],
		];
	}

	public static function test__constructWithErrorProvider()
	{
		$error = new ErrorException('Foo bar baz');
		$generic = new \Exception('Foo bar baz');

		return [
			// $param, $expected
			'Null results in no object generated'                    => [null, null],
			'Empty string results in no object generated'            => ['', null],
			'Zero results in no object generated'                    => [0, null],
			'Random object in no object generated'                   => [(object) ['foo' => 'bar'], null],
			'Error exception object passed through'                  => [$error, $error],
			'Generic exception object transformed to ErrorException' => [$generic, $error],
			'String transformed to ErrorException'                   => ['Foo bar baz', $error],
		];
	}

	public static function test__constructWithWarningsProvider()
	{
		$warning = new WarningException('Foo bar baz');
		$generic = new \Exception('Foo bar baz');

		return [
			// $param, $expected
			'Null results in no object generated'                    => [null, []],
			'Empty string results in no object generated'            => ['', []],
			'Zero results in no object generated'                    => [0, []],
			'Empty array in no object generated'                     => [[], []],
			'Random object in no object generated'                   => [[(object) ['foo' => 'bar']], []],
			'Warning exception object passed through'                => [[$warning], [$warning]],
			'Generic exception object transformed to ErrorException' => [[$generic], [$warning]],
			'String transformed to ErrorException'                   => [['Foo bar baz'], [$warning]],
		];
	}
}
