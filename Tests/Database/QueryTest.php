<?php
/**
 * @package   SimpleDBBackup
 * @copyright Copyright (c)2018-2019 Nicholas K. Dionysopoulos / ClassicPress
 * @license   http://www.gnu.org/copyleft/gpl.html GNU/GPL v3 or later
 */

namespace ClassicPress\SimpleDBBackup\Tests\Database;

class QueryTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * A mock of the Driver object for testing purposes.
	 *
	 * @var    \ClassicPress\SimpleDBBackup\Database\Driver
	 */
	protected $dbo;

	/**
	 * The instance of the object to test.
	 *
	 * @var    \ClassicPress\SimpleDBBackup\Database\Query
	 */
	private $instance;

	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		require_once DBBACKUP_TEST_ROOT . '/Stubs/Database/Driver/Fake.php';
	}

	/**
	 * Sets up the fixture.
	 *
	 * This method is called before a test is executed.
	 *
	 * @return  void
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->dbo = new \ClassicPress\SimpleDBBackup\Database\Driver\Fake([
			'prefix' => 'test_',
		]);

		$this->instance = $this->dbo->getQuery(true);
	}

	/**
	 * Data for the testNullDate test.
	 *
	 * @return  array
	 */
	public function providerTestNullDate()
	{
		return [
			// quoted, expected
			[true, "'_1BC_'"],
			[false, "1BC"],
		];
	}

	/**
	 * Data for the testNullDate test.
	 *
	 * @return  array
	 */
	public function providerTestQuote()
	{
		return [
			// Text, escaped, expected
			['text', false, "'text'"],
			['text', true, "'_text_'"],
			[['text1', 'text2'], false, ["'text1'", "'text2'"]],
			[['text1', 'text2'], true, ["'_text1_'", "'_text2_'"]],
		];
	}

	/**
	 * Test for the \ClassicPress\SimpleDBBackup\Database\Query::__call method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::__call
	 */
	public function test__call()
	{
		self::assertThat(
			$this->instance->e('foo'),
			$this->equalTo($this->instance->escape('foo')),
			'Tests the e alias of escape.'
		);

		self::assertThat(
			$this->instance->q('foo'),
			$this->equalTo($this->instance->quote('foo')),
			'Tests the q alias of quote.'
		);

		self::assertThat(
			$this->instance->qn('foo'),
			$this->equalTo($this->instance->quoteName('foo')),
			'Tests the qn alias of quoteName.'
		);

		self::assertThat(
			$this->instance->foo(),
			$this->isNull(),
			'Tests for an unknown method.'
		);
	}

	/**
	 * Test for the \ClassicPress\SimpleDBBackup\Database\Query::__get method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::__get
	 */
	public function test__get()
	{
		$this->instance->select('*');
		self::assertEquals('select', $this->getObjectAttribute($this->instance, 'type'));
	}

	/**
	 * Test for FROM clause with subquery.
	 *
	 * @return  void
	 */
	public function test__toStringFrom_subquery()
	{
		$subq = $this->dbo->getQuery(true);
		$subq->select('col2')->from('table')->where('a=1');

		$this->instance->select('col')->from($subq, 'alias');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo(
				"\n" . "SELECT col" . "\n" .
				"FROM ( " . "\n" . "SELECT col2" . "\n" . "FROM table" . "\n" . "WHERE a=1 ) AS `alias`"
			)
		);
	}

	/**
	 * Test for INSERT INTO clause with subquery.
	 *
	 * @return  void
	 */
	public function test__toStringInsert_subquery()
	{
		$subq = $this->dbo->getQuery(true);
		$subq->select('col2')->where('a=1');

		$this->instance->insert('table')->columns('col')->values($subq);

		self::assertThat(
			(string) $this->instance,
			$this->equalTo("\n" . "INSERT INTO table" . "\n" . "(col)" . "\n" . "(" . "\n" . "SELECT col2" . "\n" . "WHERE a=1)")
		);

		$this->instance->clear();
		$this->instance->insert('table')->columns('col')->values('3');
		self::assertThat(
			(string) $this->instance,
			$this->equalTo("\n" . "INSERT INTO table" . "\n" . "(col) VALUES " . "\n" . "(3)")
		);
	}

	/**
	 * Test for year extraction from date.
	 *
	 * @return  void
	 */
	public function test__toStringYear()
	{
		$this->instance->select($this->instance->year($this->instance->quoteName('col')))->from('table');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo("\n" . "SELECT YEAR(`col`)" . "\n" . "FROM table")
		);
	}

	/**
	 * Test for month extraction from date.
	 *
	 * @return  void
	 */
	public function test__toStringMonth()
	{
		$this->instance->select($this->instance->month($this->instance->quoteName('col')))->from('table');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo("\n" . "SELECT MONTH(`col`)" . "\n" . "FROM table")
		);
	}

	/**
	 * Test for day extraction from date.
	 *
	 * @return  void
	 */
	public function test__toStringDay()
	{
		$this->instance->select($this->instance->day($this->instance->quoteName('col')))->from('table');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo("\n" . "SELECT DAY(`col`)" . "\n" . "FROM table")
		);
	}

	/**
	 * Test for hour extraction from date.
	 *
	 * @return  void
	 */
	public function test__toStringHour()
	{
		$this->instance->select($this->instance->hour($this->instance->quoteName('col')))->from('table');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo("\n" . "SELECT HOUR(`col`)" . "\n" . "FROM table")
		);
	}

	/**
	 * Test for minute extraction from date.
	 *
	 * @return  void
	 */
	public function test__toStringMinute()
	{
		$this->instance->select($this->instance->minute($this->instance->quoteName('col')))->from('table');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo("\n" . "SELECT MINUTE(`col`)" . "\n" . "FROM table")
		);
	}

	/**
	 * Test for seconds extraction from date.
	 *
	 * @return  void
	 */
	public function test__toStringSecond()
	{
		$this->instance->select($this->instance->second($this->instance->quoteName('col')))->from('table');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo("\n" . "SELECT SECOND(`col`)" . "\n" . "FROM table")
		);
	}

	/**
	 * Test for the \ClassicPress\SimpleDBBackup\Database\Query::__string method for a 'select' case.
	 *
	 * @return  void
	 */
	public function test__toStringSelect()
	{
		$this->instance->select('a.id')
			->from('a')
			->innerJoin('b ON b.id = a.id')
			->where('b.id = 1')
			->group('a.id')
			->having('COUNT(a.id) > 3')
			->order('a.id');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo(
				"\n" . "SELECT a.id" .
				"\n" . "FROM a" .
				"\n" . "INNER JOIN b ON b.id = a.id" .
				"\n" . "WHERE b.id = 1" .
				"\n" . "GROUP BY a.id" .
				"\n" . "HAVING COUNT(a.id) > 3" .
				"\n" . "ORDER BY a.id"
			),
			'Tests for correct rendering.'
		);
	}

	/**
	 * Test for the \ClassicPress\SimpleDBBackup\Database\Query::__string method for a 'update' case.
	 *
	 * @return  void
	 */
	public function test__toStringUpdate()
	{
		$this->instance->update('#__foo AS a')
			->join('INNER', 'b ON b.id = a.id')
			->set('a.id = 2')
			->where('b.id = 1');

		self::assertThat(
			(string) $this->instance,
			$this->equalTo(
				"\n" . "UPDATE #__foo AS a" .
				"\n" . "INNER JOIN b ON b.id = a.id" .
				"\n" . "SET a.id = 2" .
				"\n" . "WHERE b.id = 1"
			),
			'Tests for correct rendering.'
		);
	}

	/**
	 * Tests the union element of __toString.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::__toString
	 */
	public function test__toStringUnion()
	{
		$this->instance->select('*')
			->union('SELECT id FROM a');

		$eol = "\n";
		self::assertEquals("SELECT *{$eol}UNION (SELECT id FROM a)", trim($this->instance));
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::call method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::call
	 */
	public function testCall()
	{
		self::assertSame($this->instance, $this->instance->call('foo'), 'Checks chaining');
		$this->instance->call('bar');
		self::assertEquals('CALL foo,bar', trim($this->getObjectAttribute($this->instance, 'call')), 'Checks method by rendering.');
	}

	/**
	 * Tests the call property in  method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::__toString
	 */
	public function testCall__toString()
	{
		self::assertEquals('CALL foo', trim($this->instance->call('foo')), 'Checks method by rendering.');
	}

	/**
	 * Test for the castAsChar method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::castAsChar
	 */
	public function testCastAsChar()
	{
		self::assertThat(
			$this->instance->castAsChar('123'),
			$this->equalTo('123'),
			'The default castAsChar behaviour is to return the input.'
		);
	}

	/**
	 * Test for the charLength method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::charLength
	 */
	public function testCharLength()
	{
		self::assertThat(
			$this->instance->charLength('a.title'),
			$this->equalTo('CHAR_LENGTH(a.title)')
		);

		self::assertThat(
			$this->instance->charLength('a.title', '!=', '0'),
			$this->equalTo('CHAR_LENGTH(a.title) != 0')
		);

		self::assertThat(
			$this->instance->charLength('a.title', 'IS', 'NOT NULL'),
			$this->equalTo('CHAR_LENGTH(a.title) IS NOT NULL')
		);
	}

	/**
	 * Test for the clear method (clearing all types and clauses).
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::clear
	 */
	public function testClear_all()
	{
		$properties = [
			'select',
			'delete',
			'update',
			'insert',
			'from',
			'join',
			'set',
			'where',
			'group',
			'having',
			'order',
			'columns',
			'values',
			'union',
			'exec',
			'call',
		];

		// First pass - set the values.
		foreach ($properties as $property)
		{
			$this->setObjectAttribute($this->instance, $property, $property);
		}

		// Clear the whole query.
		$this->instance->clear();

		// Check that all properties have been cleared
		foreach ($properties as $property)
		{
			self::assertThat(
				$this->getObjectAttribute($this->instance, $property),
				$this->equalTo(null)
			);
		}

		// And check that the type has been cleared.
		self::assertThat(
			$this->getObjectAttribute($this->instance, 'type'),
			$this->equalTo(null)
		);
	}

	/**
	 * Test for the clear method (clearing each clause).
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::clear
	 */
	public function testClear_clause()
	{
		$clauses = [
			'from',
			'join',
			'set',
			'where',
			'group',
			'having',
			'order',
			'columns',
			'values',
			'union',
			'exec',
			'call',
		];

		// Test each clause.
		foreach ($clauses as $clause)
		{
			$q = $this->dbo->getQuery(true);

			// Set the clauses
			foreach ($clauses as $clause2)
			{
				$this->setObjectAttribute($q, $clause2, $clause2);
			}

			// Clear the clause.
			$q->clear($clause);

			// Check that clause was cleared.
			self::assertThat(
				$this->getObjectAttribute($q, $clause),
				$this->equalTo(null)
			);

			// Check the state of the other clauses.
			foreach ($clauses as $clause2)
			{
				if ($clause != $clause2)
				{
					self::assertThat(
						$this->getObjectAttribute($q, $clause2),
						$this->equalTo($clause2),
						"Clearing $clause resulted in $clause2 having a value of " . $this->getObjectAttribute($q, $clause2) . '.'
					);
				}
			}
		}
	}

	/**
	 * Test for the clear method (clearing each query type).
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::clear
	 */
	public function testClear_type()
	{
		$types = [
			'select',
			'delete',
			'update',
			'insert',
			'union',
		];

		$clauses = [
			'from',
			'join',
			'set',
			'where',
			'group',
			'having',
			'order',
			'columns',
			'values',
		];

		// Set the clauses.
		foreach ($clauses as $clause)
		{
			$this->setObjectAttribute($this->instance, $clause, $clause);
		}

		// Check that all properties have been cleared
		foreach ($types as $type)
		{
			// Set the type.
			$this->setObjectAttribute($this->instance, $type, $type);

			// Clear the type.
			$this->instance->clear($type);

			// Check the type has been cleared.
			self::assertThat(
				$this->getObjectAttribute($this->instance, 'type'),
				$this->equalTo(null)
			);

			self::assertThat(
				$this->getObjectAttribute($this->instance, $type),
				$this->equalTo(null)
			);

			// Now check the claues have not been affected.
			foreach ($clauses as $clause)
			{
				self::assertThat(
					$this->getObjectAttribute($this->instance, $clause),
					$this->equalTo($clause)
				);
			}
		}
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::columns method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::columns
	 */
	public function testColumns()
	{
		self::assertThat(
			$this->instance->columns('foo'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'columns')),
			$this->equalTo('(foo)'),
			'Tests rendered value.'
		);

		// Add another column.
		$this->instance->columns('bar');

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'columns')),
			$this->equalTo('(foo,bar)'),
			'Tests rendered value after second use.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::concatenate method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::concatenate
	 */
	public function testConcatenate()
	{
		self::assertThat(
			$this->instance->concatenate(['foo', 'bar']),
			$this->equalTo('CONCATENATE(foo || bar)'),
			'Tests without separator.'
		);

		self::assertThat(
			$this->instance->concatenate(['foo', 'bar'], ' and '),
			$this->equalTo("CONCATENATE(foo || '_ and _' || bar)"),
			'Tests with separator.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::currentTimestamp method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::currentTimestamp
	 */
	public function testCurrentTimestamp()
	{
		self::assertThat(
			$this->instance->currentTimestamp(),
			$this->equalTo('CURRENT_TIMESTAMP()')
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::dateFormat method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::dateFormat
	 */
	public function testDateFormat()
	{
		self::assertThat(
			$this->instance->dateFormat(),
			$this->equalTo('Y-m-d H:i:s')
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::dateFormat method for an expected exception.
	 *
	 * @return  void
	 *
	 * @covers             \ClassicPress\SimpleDBBackup\Database\Query::dateFormat
	 * @expectedException  \RuntimeException
	 */
	public function testDateFormatException()
	{
		// Override the internal database for testing.
		$this->setObjectAttribute($this->instance, 'db', new \stdClass);

		$this->instance->dateFormat();
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::delete method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::delete
	 */
	public function testDelete()
	{
		self::assertThat(
			$this->instance->delete('#__foo'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			$this->getObjectAttribute($this->instance, 'type'),
			$this->equalTo('delete'),
			'Tests the type property is set correctly.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'delete')),
			$this->equalTo('DELETE'),
			'Tests the delete element is set correctly.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'from')),
			$this->equalTo('FROM #__foo'),
			'Tests the from element is set correctly.'
		);
	}

	/**
	 * Tests the delete property in \ClassicPress\SimpleDBBackup\Database\Query::__toString method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::__toString
	 */
	public function testDelete__toString()
	{
		$this->instance->delete('#__foo')
			->innerJoin('join')
			->where('bar=1');

		self::assertEquals(
			implode("\n", ['DELETE ', 'FROM #__foo', 'INNER JOIN join', 'WHERE bar=1']),
			trim($this->instance)
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::dump method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::dump
	 */
	public function testDump()
	{
		$this->instance->select('*')
			->from('#__foo');

		self::assertThat(
			$this->instance->dump(),
			$this->equalTo(
				'<pre class="Query">' .
				"\n" . "SELECT *" . "\n" . "FROM test_foo" .
				'</pre>'
			),
			'Tests that the dump method replaces the prefix correctly.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::escape method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::escape
	 */
	public function testEscape()
	{
		self::assertThat(
			$this->instance->escape('foo'),
			$this->equalTo('_foo_')
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::escape method for an expected exception.
	 *
	 * @return  void
	 *
	 * @covers             \ClassicPress\SimpleDBBackup\Database\Query::escape
	 * @expectedException  \RuntimeException
	 */
	public function testEscapeException()
	{
		// Override the internal database for testing.
		$this->setObjectAttribute($this->instance, 'db', new \stdClass);

		$this->instance->escape('foo');
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::exec method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::exec
	 */
	public function testExec()
	{
		self::assertSame($this->instance, $this->instance->exec('a.*'), 'Checks chaining');
		$this->instance->exec('b.*');
		self::assertEquals('EXEC a.*,b.*', trim($this->getObjectAttribute($this->instance, 'exec')), 'Checks method by rendering.');
	}

	/**
	 * Tests the exec property in \ClassicPress\SimpleDBBackup\Database\Query::__toString method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::__toString
	 */
	public function testExec__toString()
	{
		self::assertEquals('EXEC a.*', trim($this->instance->exec('a.*')));
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::from method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::from
	 */
	public function testFrom()
	{
		self::assertThat(
			$this->instance->from('#__foo'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'from')),
			$this->equalTo('FROM #__foo'),
			'Tests rendered value.'
		);

		// Add another column.
		$this->instance->from('#__bar');

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'from')),
			$this->equalTo('FROM #__foo,#__bar'),
			'Tests rendered value after second use.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::group method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::group
	 */
	public function testGroup()
	{
		self::assertThat(
			$this->instance->group('foo'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'group')),
			$this->equalTo('GROUP BY foo'),
			'Tests rendered value.'
		);

		// Add another column.
		$this->instance->group('bar');

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'group')),
			$this->equalTo('GROUP BY foo,bar'),
			'Tests rendered value after second use.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::having method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::having
	 */
	public function testHaving()
	{
		self::assertThat(
			$this->instance->having('COUNT(foo) > 1'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'having')),
			$this->equalTo('HAVING COUNT(foo) > 1'),
			'Tests rendered value.'
		);

		// Add another column.
		$this->instance->having('COUNT(bar) > 2');

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'having')),
			$this->equalTo('HAVING COUNT(foo) > 1 AND COUNT(bar) > 2'),
			'Tests rendered value after second use.'
		);

		// Reset the field to test the glue.
		$this->setObjectAttribute($this->instance, 'having', null);
		$this->instance->having('COUNT(foo) > 1', 'OR');
		$this->instance->having('COUNT(bar) > 2');

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'having')),
			$this->equalTo('HAVING COUNT(foo) > 1 OR COUNT(bar) > 2'),
			'Tests rendered value with OR glue.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::innerJoin method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::innerJoin
	 */
	public function testInnerJoin()
	{
		$q1        = $this->dbo->getQuery(true);
		$q2        = $this->dbo->getQuery(true);
		$condition = 'foo ON foo.id = bar.id';

		self::assertThat(
			$q1->innerJoin($condition),
			$this->identicalTo($q1),
			'Tests chaining.'
		);

		$q2->join('INNER', $condition);

		self::assertThat(
			$this->getObjectAttribute($q1, 'join'),
			$this->equalTo($this->getObjectAttribute($q2, 'join')),
			'Tests that innerJoin is an alias for join.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::insert method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::insert
	 */
	public function testInsert()
	{
		self::assertThat(
			$this->instance->insert('#__foo'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			$this->getObjectAttribute($this->instance, 'type'),
			$this->equalTo('insert'),
			'Tests the type property is set correctly.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'insert')),
			$this->equalTo('INSERT INTO #__foo'),
			'Tests the delete element is set correctly.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::join method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::join
	 */
	public function testJoin()
	{
		self::assertThat(
			$this->instance->join('INNER', 'foo ON foo.id = bar.id'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		$join = $this->getObjectAttribute($this->instance, 'join');

		self::assertThat(
			trim($join[0]),
			$this->equalTo('INNER JOIN foo ON foo.id = bar.id'),
			'Tests that first join renders correctly.'
		);

		$this->instance->join('OUTER', 'goo ON goo.id = car.id');

		$join = $this->getObjectAttribute($this->instance, 'join');

		self::assertThat(
			trim($join[1]),
			$this->equalTo('OUTER JOIN goo ON goo.id = car.id'),
			'Tests that second join renders correctly.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::leftJoin method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::leftJoin
	 */
	public function testLeftJoin()
	{
		$q1        = $this->dbo->getQuery(true);
		$q2        = $this->dbo->getQuery(true);
		$condition = 'foo ON foo.id = bar.id';

		self::assertThat(
			$q1->leftJoin($condition),
			$this->identicalTo($q1),
			'Tests chaining.'
		);

		$q2->join('LEFT', $condition);

		self::assertThat(
			$this->getObjectAttribute($q1, 'join'),
			$this->equalTo($this->getObjectAttribute($q2, 'join')),
			'Tests that leftJoin is an alias for join.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::length method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::length
	 */
	public function testLength()
	{
		self::assertThat(
			trim($this->instance->length('foo')),
			$this->equalTo('LENGTH(foo)'),
			'Tests method renders correctly.'
		);
	}

	/**
	 * Tests the quoteName method.
	 *
	 * @param   boolean $quoted   The value of the quoted argument.
	 * @param   string  $expected The expected result.
	 *
	 * @return  void
	 *
	 * @covers        \ClassicPress\SimpleDBBackup\Database\Query::nullDate
	 * @dataProvider  providerTestNullDate
	 */
	public function testNullDate($quoted, $expected)
	{
		self::assertThat(
			$this->instance->nullDate($quoted),
			$this->equalTo($expected),
			'The nullDate method should be a proxy for the Driver::getNullDate method.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::nullDate method for an expected exception.
	 *
	 * @return  void
	 *
	 * @covers             \ClassicPress\SimpleDBBackup\Database\Query::nullDate
	 * @expectedException  \RuntimeException
	 */
	public function testNullDateException()
	{
		// Override the internal database for testing.
		$this->setObjectAttribute($this->instance, 'db', new \stdClass);

		$this->instance->nullDate();
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::order method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::order
	 */
	public function testOrder()
	{
		self::assertThat(
			$this->instance->order('foo'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'order')),
			$this->equalTo('ORDER BY foo'),
			'Tests rendered value.'
		);

		// Add another column.
		$this->instance->order('bar');

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'order')),
			$this->equalTo('ORDER BY foo,bar'),
			'Tests rendered value after second use.'
		);

		$this->instance->order(
			[
				'goo', 'car',
			]
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'order')),
			$this->equalTo('ORDER BY foo,bar,goo,car'),
			'Tests array input.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::outerJoin method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::outerJoin
	 */
	public function testOuterJoin()
	{
		$q1        = $this->dbo->getQuery(true);
		$q2        = $this->dbo->getQuery(true);
		$condition = 'foo ON foo.id = bar.id';

		self::assertThat(
			$q1->outerJoin($condition),
			$this->identicalTo($q1),
			'Tests chaining.'
		);

		$q2->join('OUTER', $condition);

		self::assertThat(
			$this->getObjectAttribute($q1, 'join'),
			$this->equalTo($this->getObjectAttribute($q2, 'join')),
			'Tests that outerJoin is an alias for join.'
		);
	}

	/**
	 * Tests the quote method.
	 *
	 * @param   boolean $text     The value to be quoted.
	 * @param   boolean $escape   True to escape the string, false to leave it unchanged.
	 * @param   string  $expected The expected result.
	 *
	 * @return  void
	 *
	 * @covers        \ClassicPress\SimpleDBBackup\Database\Query::quote
	 * @dataProvider  providerTestQuote
	 */
	public function testQuote($text, $escape, $expected)
	{
		self::assertEquals($expected, $this->instance->quote($text, $escape));
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::nullDate method for an expected exception.
	 *
	 * @return  void
	 *
	 * @covers             \ClassicPress\SimpleDBBackup\Database\Query::quote
	 * @expectedException  \RuntimeException
	 */
	public function testQuoteException()
	{
		// Override the internal database for testing.
		$this->setObjectAttribute($this->instance, 'db', new \stdClass);

		$this->instance->quote('foo');
	}

	/**
	 * Tests the quoteName method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::quoteName
	 */
	public function testQuoteName()
	{
		self::assertThat(
			$this->instance->quoteName("test"),
			$this->equalTo("`test`"),
			'The quoteName method should be a proxy for the JDatabase::escape method.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::quoteName method for an expected exception.
	 *
	 * @return  void
	 *
	 * @covers             \ClassicPress\SimpleDBBackup\Database\Query::quoteName
	 * @expectedException  \RuntimeException
	 */
	public function testQuoteNameException()
	{
		// Override the internal database for testing.
		$this->setObjectAttribute($this->instance, 'db', new \stdClass);

		$this->instance->quoteName('foo');
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::rightJoin method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::rightJoin
	 */
	public function testRightJoin()
	{
		$q1        = $this->dbo->getQuery(true);
		$q2        = $this->dbo->getQuery(true);
		$condition = 'foo ON foo.id = bar.id';

		self::assertThat(
			$q1->rightJoin($condition),
			$this->identicalTo($q1),
			'Tests chaining.'
		);

		$q2->join('RIGHT', $condition);

		self::assertThat(
			$this->getObjectAttribute($q1, 'join'),
			$this->equalTo($this->getObjectAttribute($q2, 'join')),
			'Tests that rightJoin is an alias for join.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::select method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::select
	 */
	public function testSelect()
	{
		self::assertThat(
			$this->instance->select('foo'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			$this->getObjectAttribute($this->instance, 'type'),
			$this->equalTo('select'),
			'Tests the type property is set correctly.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'select')),
			$this->equalTo('SELECT foo'),
			'Tests the select element is set correctly.'
		);

		$this->instance->select('bar');

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'select')),
			$this->equalTo('SELECT foo,bar'),
			'Tests the second use appends correctly.'
		);

		$this->instance->select(
			[
				'goo', 'car',
			]
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'select')),
			$this->equalTo('SELECT foo,bar,goo,car'),
			'Tests the second use appends correctly.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::set method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::set
	 */
	public function testSet()
	{
		self::assertThat(
			$this->instance->set('foo = 1'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'set')),
			$this->identicalTo('SET foo = 1'),
			'Tests set with a string.'
		);

		$this->instance->set('bar = 2');
		self::assertEquals("SET foo = 1" . "\n" . "\t, bar = 2", trim($this->getObjectAttribute($this->instance, 'set')), 'Tests appending with set().');

		// Clear the set.
		$this->setObjectAttribute($this->instance, 'set', null);
		$this->instance->set(
			[
				'foo = 1',
				'bar = 2',
			]
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'set')),
			$this->identicalTo("SET foo = 1" . "\n" . "\t, bar = 2"),
			'Tests set with an array.'
		);

		// Clear the set.
		$this->setObjectAttribute($this->instance, 'set', null);
		$this->instance->set(
			[
				'foo = 1',
				'bar = 2',
			],
			';'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'set')),
			$this->identicalTo("SET foo = 1" . "\n" . "\t; bar = 2"),
			'Tests set with an array and glue.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::setQuery method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::setQuery
	 */
	public function testSetQuery()
	{
		self::assertSame($this->instance, $this->instance->setQuery('Some SQL'), 'Check chaining.');
		self::assertAttributeEquals('Some SQL', 'sql', $this->instance, 'Checks the property was set correctly.');
		self::assertEquals('Some SQL', (string) $this->instance, 'Checks the rendering of the raw SQL.');
	}

	/**
	 * Tests rendering coupled with the \ClassicPress\SimpleDBBackup\Database\Query::setQuery method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::__toString
	 */
	public function testSetQuery__toString()
	{
		self::assertEquals('Some SQL', trim($this->instance->setQuery('Some SQL')));
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::update method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::update
	 */
	public function testUpdate()
	{
		self::assertThat(
			$this->instance->update('#__foo'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			$this->getObjectAttribute($this->instance, 'type'),
			$this->equalTo('update'),
			'Tests the type property is set correctly.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'update')),
			$this->equalTo('UPDATE #__foo'),
			'Tests the update element is set correctly.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::values method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::values
	 */
	public function testValues()
	{
		self::assertThat(
			$this->instance->values('1,2,3'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'values')),
			$this->equalTo('(1,2,3)'),
			'Tests rendered value.'
		);

		// Add another column.
		$this->instance->values(
			[
				'4,5,6',
				'7,8,9',
			]
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'values')),
			$this->equalTo('(1,2,3),(4,5,6),(7,8,9)'),
			'Tests rendered value after second use and array input.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::where method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::where
	 */
	public function testWhere()
	{
		self::assertThat(
			$this->instance->where('foo = 1'),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'where')),
			$this->equalTo('WHERE foo = 1'),
			'Tests rendered value.'
		);

		// Add another column.
		$this->instance->where(
			[
				'bar = 2',
				'goo = 3',
			]
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'where')),
			$this->equalTo('WHERE foo = 1 AND bar = 2 AND goo = 3'),
			'Tests rendered value after second use and array input.'
		);

		// Clear the where
		$this->setObjectAttribute($this->instance, 'where', null);
		$this->instance->where(
			[
				'bar = 2',
				'goo = 3',
			],
			'OR'
		);

		self::assertThat(
			trim($this->getObjectAttribute($this->instance, 'where')),
			$this->equalTo('WHERE bar = 2 OR goo = 3'),
			'Tests rendered value with glue.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::__clone method properly clones an array.
	 *
	 * @return  void
	 *
	 */
	public function test__clone_array()
	{
		$baseElement = $this->dbo->getQuery(true);

		$baseElement->testArray = [];

		$cloneElement = clone($baseElement);

		$baseElement->testArray[] = 'test';

		self::assertSame(
			$this->getObjectAttribute($baseElement, 'db'),
			$this->getObjectAttribute($cloneElement, 'db'),
			'The cloned $db variable should be identical after cloning.'
		);

		self::assertFalse($baseElement === $cloneElement);
		self::assertTrue(count($cloneElement->testArray) == 0);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::__clone method properly clones an object.
	 *
	 * @return  void
	 *
	 */
	public function test__clone_object()
	{
		$baseElement = $this->dbo->getQuery(true);

		$baseElement->testObject = new \stdClass;

		$cloneElement = clone($baseElement);

		self::assertSame(
			$this->getObjectAttribute($baseElement, 'db'),
			$this->getObjectAttribute($cloneElement, 'db'),
			'The cloned $db variable should be identical after cloning.'
		);

		self::assertFalse($baseElement === $cloneElement);
		self::assertFalse($baseElement->testObject === $cloneElement->testObject);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::union method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::union
	 */
	public function testUnionChain()
	{
		self::assertThat(
			$this->instance->union($this->instance),
			$this->identicalTo($this->instance),
			'Tests chaining.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::union method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::union
	 */
	public function testUnionClear()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->setObjectAttribute($this->instance, 'order', null);
		$this->instance->order('bar');
		$this->instance->union('SELECT name FROM foo');
		self::assertThat(
			$this->getObjectAttribute($this->instance, 'order'),
			$this->equalTo(null),
			'Tests that ORDER BY is cleared with union.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::union method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::union
	 */
	public function testUnionUnion()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->instance->union('SELECT name FROM foo');
		$teststring = (string) $this->getObjectAttribute($this->instance, 'union');
		self::assertThat(
			$teststring,
			$this->equalTo("\n" . "UNION (SELECT name FROM foo)"),
			'Tests rendered query with union.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::union method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::union
	 */
	public function testUnionDistinctString()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->instance->union('SELECT name FROM foo', 'distinct');
		$teststring = (string) $this->getObjectAttribute($this->instance, 'union');
		self::assertThat(
			$teststring,
			$this->equalTo("\n" . "UNION DISTINCT (SELECT name FROM foo)"),
			'Tests rendered query with union distinct as a string.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::union method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::union
	 */
	public function testUnionDistinctTrue()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->instance->union('SELECT name FROM foo', true);
		$teststring = (string) $this->getObjectAttribute($this->instance, 'union');
		self::assertThat(
			$teststring,
			$this->equalTo("\n" . "UNION DISTINCT (SELECT name FROM foo)"),
			'Tests rendered query with union distinct true.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::union method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::union
	 */
	public function testUnionDistinctFalse()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->instance->union('SELECT name FROM foo', false);
		$teststring = (string) $this->getObjectAttribute($this->instance, 'union');
		self::assertThat(
			$teststring,
			$this->equalTo("\n" . "UNION (SELECT name FROM foo)"),
			'Tests rendered query with union distinct false.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::union method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::union
	 */
	public function testUnionArray()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->instance->union(['SELECT name FROM foo', 'SELECT name FROM bar']);
		$teststring = (string) $this->getObjectAttribute($this->instance, 'union');
		self::assertThat(
			$teststring,
			$this->equalTo("\n" . "UNION (SELECT name FROM foo)" . "\n" . "UNION (SELECT name FROM bar)"),
			'Tests rendered query with two unions as an array.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::union method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::union
	 */
	public function testUnionTwo()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->instance->union('SELECT name FROM foo');
		$this->instance->union('SELECT name FROM bar');
		$teststring = (string) $this->getObjectAttribute($this->instance, 'union');
		self::assertThat(
			$teststring,
			$this->equalTo("\n" . "UNION (SELECT name FROM foo)" . "\n" . "UNION (SELECT name FROM bar)"),
			'Tests rendered query with two unions sequentially.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::unionDistinct method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::unionDistinct
	 */
	public function testUnionDistinct()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->instance->unionDistinct('SELECT name FROM foo');
		$teststring = (string) $this->getObjectAttribute($this->instance, 'union');
		self::assertThat(
			trim($teststring),
			$this->equalTo("UNION DISTINCT (SELECT name FROM foo)"),
			'Tests rendered query with unionDistinct.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::unionDistinct method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::unionDistinct
	 */
	public function testUnionDistinctArray()
	{
		$this->setObjectAttribute($this->instance, 'union', null);
		$this->instance->unionDistinct(['SELECT name FROM foo', 'SELECT name FROM bar']);
		$teststring = (string) $this->getObjectAttribute($this->instance, 'union');
		self::assertThat(
			$teststring,
			$this->equalTo("\n" . "UNION DISTINCT (SELECT name FROM foo)" . "\n" . "UNION DISTINCT (SELECT name FROM bar)"),
			'Tests rendered query with two unions distinct.'
		);
	}

	/**
	 * Tests the \ClassicPress\SimpleDBBackup\Database\Query::format method.
	 *
	 * @return  void
	 *
	 * @covers  \ClassicPress\SimpleDBBackup\Database\Query::format
	 */
	public function testFormat()
	{
		$result   = $this->instance->format('SELECT %n FROM %n WHERE %n = %a', 'foo', '#__bar', 'id', 10);
		$expected = 'SELECT ' . $this->instance->qn('foo') . ' FROM ' . $this->instance->qn('#__bar') .
			' WHERE ' . $this->instance->qn('id') . ' = 10';
		self::assertThat(
			$result,
			$this->equalTo($expected),
			'Line: ' . __LINE__ . '.'
		);

		$result   = $this->instance->format('SELECT %n FROM %n WHERE %n = %t OR %3$n = %Z', 'id', '#__foo', 'date');
		$expected = 'SELECT ' . $this->instance->qn('id') . ' FROM ' . $this->instance->qn('#__foo') .
			' WHERE ' . $this->instance->qn('date') . ' = ' . $this->instance->currentTimestamp() .
			' OR ' . $this->instance->qn('date') . ' = ' . $this->instance->nullDate(true);
		self::assertThat(
			$result,
			$this->equalTo($expected),
			'Line: ' . __LINE__ . '.'
		);
	}

	private function setObjectAttribute($object, $attributeName, $value)
	{
		$refObject   = new \ReflectionObject($object);
		$refProperty = $refObject->getProperty($attributeName);

		if ($refProperty->isPublic())
		{
			$refProperty->setValue($object, $value);

			return;
		}

		$refProperty->setAccessible(true);
		$refProperty->setValue($object, $value);
		$refProperty->setAccessible(false);
	}

}
