<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database\Metadata;

use ClassicPress\SimpleDBBackup\Database\Metadata\Database;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @param $input
	 * @param $name
	 * @param $charset
	 * @param $collation
	 *
	 * @dataProvider fromDatabaseResultProvider
	 */
	public function testFromDatabaseResult($input, $name, $charset, $collation)
	{
		$actual = Database::fromDatabaseResult($input);

		self::assertInstanceOf(Database::class, $actual);
		self::assertEquals($name, $actual->getName());
		self::assertEquals($charset, $actual->getCharacterSet());
		self::assertEquals($collation, $actual->getCollation());
	}

	public static function fromDatabaseResultProvider()
	{
		return [
			'Minimum, happy path'        => [
				[
					'SCHEMA_NAME'                => 'foobar',
					'DEFAULT_CHARACTER_SET_NAME' => 'utf8',
					'DEFAULT_COLLATION_NAME'     => 'utf8_general_ci',
				],
				'foobar', 'utf8', 'utf8_general_ci',
			],
			'Full, utf8mb4 (real world)' => [
				[
					'CATALOG_NAME'               => 'def',
					'SCHEMA_NAME'                => 'abcom',
					'DEFAULT_CHARACTER_SET_NAME' => 'utf8mb4',
					'DEFAULT_COLLATION_NAME'     => 'utf8mb4_unicode_ci',
					'SQL_PATH'                   => null,
				],
				'abcom', 'utf8mb4', 'utf8mb4_unicode_ci'
			],
			'Full, utf8mb4 with unicode_520 collation (real world)' => [
				[
					'CATALOG_NAME'               => 'def',
					'SCHEMA_NAME'                => 'multisite',
					'DEFAULT_CHARACTER_SET_NAME' => 'utf8mb4',
					'DEFAULT_COLLATION_NAME'     => 'utf8mb4_unicode_520_ci',
					'SQL_PATH'                   => null,
				],
				'multisite', 'utf8mb4', 'utf8mb4_unicode_520_ci'
			],
		];
	}
}
