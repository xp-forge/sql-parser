<?php namespace text\sql\unittest;

use text\sql\statement\{Update, Number, Text, Field, Literal, Table, Comparison, Call, AllOf};

class UpdateTest extends StatementTest {

  /** @return iterable */
  protected function statements() {
    yield ['update user set name = "test"', new Update(
      new Table('user'),
      ['name' => new Text('test')]
    )];
    yield ['update test.user set name = "test"', new Update(
      new Table('test.user'),
      ['name' => new Text('test')]
    )];
    yield ['update user set name = "test", created = now()', new Update(
      new Table('user'),
      ['name' => new Text('test'), 'created' => new Call('now')]
    )];

    // Update statements with WHERE clause
    yield ['update user set name = "test" where uid = 1', new Update(
      new Table('user'),
      ['name' => new Text('test')],
      new Comparison(new Field(null, 'uid'), '=', new Number(1))
    )];
    yield ['update user set name = "test" where uid = 1 and status != "deleted"', new Update(
      new Table('user'),
      ['name' => new Text('test')],
      new AllOf(
        new Comparison(new Field(null, 'uid'), '=', new Number(1)),
        new Comparison(new Field(null, 'status'), '!=', new Text('deleted'))
      ),
    )];
  }
}