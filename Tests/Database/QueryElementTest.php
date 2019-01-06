<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database;

use ClassicPress\SimpleDBBackup\Database\QueryElement;

class QueryElementTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test cases for append and __toString
	 *
	 * Each test case provides
	 * - array    element    the base element for the test, given as hash
	 *                 name => element_name,
	 *                 elements => element array,
	 *                 glue => glue
	 * - array    appendElement    the element to be appended (same format as above)
	 * - array     expected    array of elements that should be the value of the elements attribute after the merge
	 * - string    expected value of __toString() for element after append
	 *
	 * @return  array
	 */
	public function dataTestAppend()
	{
		return array(
			'array-element' => array(
				array(
					'name' => 'SELECT',
					'elements' => array(),
					'glue' => ','
				),
				array(
					'name' => 'FROM',
					'elements' => array('my_table_name'),
					'glue' => ','
				),
				array(
					'name' => 'FROM',
					'elements' => array('my_table_name'),
					'glue' => ','
				),
				"\n" . 'SELECT ' . "\n" . 'FROM my_table_name',
			),
			'non-array-element' => array(
				array(
					'name' => 'SELECT',
					'elements' => array(),
					'glue' => ','
				),
				array(
					'name' => 'FROM',
					'elements' => array('my_table_name'),
					'glue' => ','
				),
				array(
					'name' => 'FROM',
					'elements' => array('my_table_name'),
					'glue' => ','
				),
				"\n" . 'SELECT ' . "\n" . 'FROM my_table_name',
			)
		);
	}

	/**
	 * Test cases for constructor
	 *
	 * Each test case provides
	 * - array    element    the base element for the test, given as hash
	 *                 name => element_name,
	 *                 elements => array or string
	 *                 glue => glue
	 * - array    expected values in same hash format
	 *
	 * @return array
	 */
	public function dataTestConstruct()
	{
		return array(
			'array-element' => array(
				array(
					'name' => 'FROM',
					'elements' => array('field1', 'field2'),
					'glue' => ','
				),
				array(
					'name' => 'FROM',
					'elements' => array('field1', 'field2'),
					'glue' => ','
				)
			),
			'non-array-element' => array(
				array(
					'name' => 'TABLE',
					'elements' => 'my_table_name',
					'glue' => ','
				),
				array(
					'name' => 'TABLE',
					'elements' => array('my_table_name'),
					'glue' => ','
				)
			)
		);
	}

	/**
	 * Test data for test__toString.
	 *
	 * @return  array
	 */
	public function dataTestToString()
	{
		return array(
			array(
				'FROM',
				'table1',
				',',
				"\n" . "FROM table1"
			),
			array(
				'SELECT',
				array('column1', 'column2'),
				',',
				"\n" . "SELECT column1,column2"
			),
			array(
				'()',
				array('column1', 'column2'),
				',',
				"\n" . "(column1,column2)"
			),
			array(
				'CONCAT()',
				array('column1', 'column2'),
				',',
				"\n" . "CONCAT(column1,column2)"
			),
		);
	}

	/**
	 * Test the class constructor.
	 *
	 * @param   array  $element   values for base element
	 * @param   array  $expected  values for expected fields
	 *
	 * @return  void
	 * @dataProvider  dataTestConstruct
	 */
	public function test__Construct($element, $expected)
	{
		$baseElement = new QueryElement($element['name'], $element['elements'], $element['glue']);

		self::assertAttributeEquals(
			$expected['name'], 'name', $baseElement, 'Line ' . __LINE__ . ' name should be set'
		);

		self::assertAttributeEquals(
			$expected['elements'], 'elements', $baseElement, 'Line ' . __LINE__ . ' elements should be set'
		);

		self::assertAttributeEquals(
			$expected['glue'], 'glue', $baseElement, 'Line ' . __LINE__ . ' glue should be set'
		);
	}

	/**
	 * Test the __toString magic method.
	 *
	 * @param   string  $name      The name of the element.
	 * @param   mixed   $elements  String or array.
	 * @param   string  $glue      The glue for elements.
	 * @param   string  $expected  The expected value.
	 *
	 * @return  void
	 * @dataProvider  dataTestToString
	 */
	public function test__toString($name, $elements, $glue, $expected)
	{
		$e = new QueryElement($name, $elements, $glue);

		self::assertThat(
			(string) $e,
			$this->equalTo($expected)
		);
	}

	/**
	 * Test the append method.
	 *
	 * @param   array   $element   base element values
	 * @param   array   $append    append element values
	 * @param   array   $expected  expected element values for elements field after append
	 * @param   string  $string    expected value of toString (not used in this test)
	 *
	 * @return  void
	 * @dataProvider dataTestAppend
	 */

	public function testAppend($element, $append, $expected, $string)
	{
		$baseElement = new QueryElement($element['name'], $element['elements'], $element['glue']);
		$appendElement = new QueryElement($append['name'], $append['elements'], $append['glue']);
		$expectedElement = new QueryElement($expected['name'], $expected['elements'], $expected['glue']);
		$baseElement->append($appendElement);
		self::assertAttributeEquals(array($expectedElement), 'elements', $baseElement);
	}

	/**
	 * Tests the Awf\Database\QueryElement::__clone method properly clones an array.
	 *
	 * @return  void
	 */
	public function test__clone_array()
	{
		$baseElement = new QueryElement($name = null, $elements = null);

		$baseElement->testArray = array();

		$cloneElement = clone($baseElement);

		$baseElement->testArray[] = 'a';

		self::assertFalse($baseElement === $cloneElement);
		self::assertEquals(count($cloneElement->testArray), 0);
	}

	/**
	 * Tests the Awf\Database\QueryElement::__clone method properly clones an object.
	 *
	 * @return  void
	 */
	public function test__clone_object()
	{
		$baseElement = new QueryElement($name = null, $elements = null);

		$baseElement->testObject = new \stdClass;

		$cloneElement = clone($baseElement);

		self::assertFalse($baseElement === $cloneElement);
		self::assertFalse($baseElement->testObject === $cloneElement->testObject);
	}

}
