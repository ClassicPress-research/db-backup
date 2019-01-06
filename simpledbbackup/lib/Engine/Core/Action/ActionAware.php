<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Action;


use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Database\Query;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\LoggerAwareInterface;
use ClassicPress\SimpleDBBackup\Logger\NullLogger;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;

/**
 * A trait which allows objects to process the responses of database and table action
 *
 * @package ClassicPress\SimpleDBBackup\Engine\Core\Action
 */
trait ActionAware
{
	/**
	 * Apply the action queries to the database and / or writing them to the WriterInterface object.
	 *
	 * @param   SQL              $response      The SQL response to process
	 * @param   WriterInterface  $outputWriter  The writer to use for outputting the queries
	 *
	 * @return  int  Number of action queries processed
	 */
	public function applyActionQueries(SQL $response, WriterInterface $outputWriter)
	{
		$numActions = 0;

		if (!$response->hasActionQueries())
		{
			return $numActions;
		}

		$logger = new NullLogger();

		if ($this instanceof LoggerAwareInterface)
		{
			$logger = $this->getLogger();
		}

		array_map(function ($query) use ($outputWriter, &$numActions, $logger) {
			$numActions++;

			$outputWriter->writeLine(rtrim($query, ';') . ';');
		}, $response->getActionQueries());

		return $numActions;
	}
}