<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\ErrorHandling;


use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAware;
use ClassicPress\SimpleDBBackup\Engine\ErrorHandling\WarningsAwareInterface;

/**
 * Dummy object to test the WarningsAware trait.
 *
 * We need an abstract class because PHPUnit's getObjectForTrait() does not let us indicate that the generated mock
 * object implements a specific interface. Therefore we need to create an abstract class and use getMockForAbstractClass
 * to achieve our intended result.
 *
 * @see https://stackoverflow.com/questions/12891606/mock-interface-and-trait-simultaneously
 *
 * @package ClassicPress\SimpleDBBackup\Tests\Stubs\Engine\ErrorHandling
 */
abstract class WarningsAwareTraitDummy implements WarningsAwareInterface
{
	use WarningsAware;
}