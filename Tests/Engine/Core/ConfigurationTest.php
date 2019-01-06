<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Engine\Core;

use ClassicPress\SimpleDBBackup\Engine\Core\Configuration;
use ClassicPress\SimpleDBBackup\Logger\LoggerInterface;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{

	public function testSetFromParameters()
	{
		$input = [
			'outputSQLFile'      => '/does/not/matter/output.sql',
			'logFile'            => '/does/not/matter/foo.log',
			'minLogLevel'        => LoggerInterface::SEVERITY_INFO,
			'minExecutionTime'   => 3.1415,
			'maxExecutionTime'   => 7,
			'runtimeBiasPercent' => 42,
			'maxBatchSize'       => 123,
			'maxQuerySize'       => 131584,
			'description'        => 'Foo bar baz bat',
		];

		$config = new Configuration($input);

		self::assertEquals($input['outputSQLFile'], $config->getOutputSQLFile());
		self::assertEquals($input['logFile'], $config->getLogFile());
		self::assertEquals($input['minLogLevel'], $config->getMinLogLevel());
		self::assertEquals($input['minExecutionTime'], $config->getMinExecutionTime());
		self::assertEquals($input['maxExecutionTime'], $config->getMaxExecutionTime());
		self::assertEquals($input['runtimeBiasPercent'], $config->getRuntimeBiasPercent());
		self::assertEquals($input['maxBatchSize'], $config->getMaxBatchSize());
		self::assertEquals($input['maxQuerySize'], $config->getMaxQuerySize());
		self::assertEquals($input['description'], $config->getDescription());
	}

	public function testToArray()
	{
		$input = [
			'outputSQLFile'      => '/does/not/matter/output.sql',
			'logFile'            => '/does/not/matter/foo.log',
			'minLogLevel'        => LoggerInterface::SEVERITY_INFO,
			'minExecutionTime'   => 3.1415,
			'maxExecutionTime'   => 7,
			'runtimeBiasPercent' => 42,
			'maxBatchSize'       => 123,
			'maxQuerySize'       => 131584,
			'description'        => 'Foo bar baz bat',
		];

		$config = new Configuration($input);
		$actual = $config->toArray();

		self::assertEquals($input, $actual);
	}
}
