<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database\Metadata;

use ClassicPress\SimpleDBBackup\Database\Metadata\Table;

class TableTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @param array $input
	 * @param       $name
	 * @param       $collation
	 * @param       $engine
	 * @param       $avgRowLen
	 *
	 * @dataProvider fromDatabaseResultProvider()
	 */
	public function testFromDatabaseResult(array $input, $name, $collation, $engine, $avgRowLen)
	{
		$actual = Table::fromDatabaseResult($input);

		self::assertInstanceOf(Table::class, $actual);
		self::assertEquals($name, $actual->getName());
		self::assertEquals($collation, $actual->getCollation());
		self::assertEquals($engine, $actual->getEngine());
		self::assertEquals($avgRowLen, $actual->getAverageRowLength());
	}

	public static function fromDatabaseResultProvider()
	{
		return [
			'Simple utf8mb4 table, INFORMATION_SCHEMA.TABLES' => [
				json_decode('{
				    "TABLE_CATALOG": "def",
				    "TABLE_SCHEMA": "wordpress",
				    "TABLE_NAME": "wp_ak_params",
				    "TABLE_TYPE": "BASE TABLE",
				    "ENGINE": "InnoDB",
				    "VERSION": 10,
				    "ROW_FORMAT": "Dynamic",
				    "TABLE_ROWS": 1,
				    "AVG_ROW_LENGTH": 16384,
				    "DATA_LENGTH": 16384,
				    "MAX_DATA_LENGTH": 0,
				    "INDEX_LENGTH": 0,
				    "DATA_FREE": 0,
				    "AUTO_INCREMENT": null,
				    "CREATE_TIME": "2018-09-07 11:56:40",
				    "UPDATE_TIME": null,
				    "CHECK_TIME": null,
				    "TABLE_COLLATION": "utf8mb4_unicode_ci",
				    "CHECKSUM": null,
				    "CREATE_OPTIONS": "",
				    "TABLE_COMMENT": ""
				  }', true),
				'wp_ak_params', 'utf8mb4_unicode_ci', 'InnoDB', 16384,
			],
			'Simple utf8mb4 table, SHOW TABLE STATUS'         => [
				json_decode('{
				    "Name": "wp_ak_params",
				    "Engine": "InnoDB",
				    "Version": 10,
				    "Row_format": "Dynamic",
				    "Rows": 1,
				    "Avg_row_length": 16384,
				    "Data_length": 16384,
				    "Max_data_length": 0,
				    "Index_length": 0,
				    "Data_free": 0,
				    "Auto_increment": null,
				    "Create_time": "2018-09-07 11:56:40",
				    "Update_time": null,
				    "Check_time": null,
				    "Collation": "utf8mb4_unicode_ci",
				    "Checksum": null,
				    "Create_options": "",
				    "Comment": ""
				  }', true),
				'wp_ak_params', 'utf8mb4_unicode_ci', 'InnoDB', 16384,
			],
			'Unicode 520, INFORMATION_SCHEMA.TABLES' => [
				json_decode('{
				    "TABLE_CATALOG": "def",
				    "TABLE_SCHEMA": "wordpress",
				    "TABLE_NAME": "wp_options",
				    "TABLE_TYPE": "BASE TABLE",
				    "ENGINE": "InnoDB",
				    "VERSION": 10,
				    "ROW_FORMAT": "Dynamic",
				    "TABLE_ROWS": 357,
				    "AVG_ROW_LENGTH": 3166,
				    "DATA_LENGTH": 1130496,
				    "MAX_DATA_LENGTH": 0,
				    "INDEX_LENGTH": 16384,
				    "DATA_FREE": 4194304,
				    "AUTO_INCREMENT": 1441,
				    "CREATE_TIME": "2018-09-03 17:44:56",
				    "UPDATE_TIME": null,
				    "CHECK_TIME": null,
				    "TABLE_COLLATION": "utf8mb4_unicode_520_ci",
				    "CHECKSUM": null,
				    "CREATE_OPTIONS": "",
				    "TABLE_COMMENT": ""
				  }', true),
				'wp_options', 'utf8mb4_unicode_520_ci', 'InnoDB', 3166,
			],
			'Unicode 520, SHOW_TABLE_STATUS' => [
				json_decode('{
				    "Name": "wp_options",
				    "Engine": "InnoDB",
				    "Version": 10,
				    "Row_format": "Dynamic",
				    "Rows": 357,
				    "Avg_row_length": 3166,
				    "Data_length": 1130496,
				    "Max_data_length": 0,
				    "Index_length": 16384,
				    "Data_free": 4194304,
				    "Auto_increment": 1441,
				    "Create_time": "2018-09-03 17:44:56",
				    "Update_time": null,
				    "Check_time": null,
				    "Collation": "utf8mb4_unicode_520_ci",
				    "Checksum": null,
				    "Create_options": "",
				    "Comment": ""
				  }', true),
				'wp_options', 'utf8mb4_unicode_520_ci', 'InnoDB', 3166,
			]
		];
	}
}

