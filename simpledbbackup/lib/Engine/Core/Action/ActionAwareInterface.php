<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Engine\Core\Action;


use ClassicPress\SimpleDBBackup\Database\Driver;
use ClassicPress\SimpleDBBackup\Engine\Core\Response\SQL;
use ClassicPress\SimpleDBBackup\Writer\WriterInterface;

interface ActionAwareInterface
{
	/**
	 * Save the action queries to the WriterInterface object.
	 *
	 * @param   SQL              $response      The SQL response to process
	 * @param   WriterInterface  $outputWriter  The writer to use for outputting the queries
	 *
	 * @return  int  Number of action queries processed
	 */
	public function applyActionQueries(SQL $response, WriterInterface $outputWriter);
}