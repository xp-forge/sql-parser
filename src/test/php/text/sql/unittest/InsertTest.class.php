<?php namespace text\sql\unittest;

use text\sql\statement\{Insert, Number, Text, Literal, Table, Values, Select};

class InsertTest extends StatementTest {

  /** @return iterable */
  protected function statements() {
    yield ['insert user values (1, "test")', new Insert(
      new Table('user'),
      [],
      new Values([new Number(1), new Text('test')])
    )];
    yield ['insert into user values (1, "test")', new Insert(
      new Table('user'),
      [],
      new Values([new Number(1), new Text('test')])
    )];
    yield ['insert into test.user values (1, "test")', new Insert(
      new Table('test.user'),
      [],
      new Values([new Number(1), new Text('test')])
    )];
    yield ['insert into user (uid, name) values (1, "test")', new Insert(
      new Table('user'),
      ['uid', 'name'],
      new Values([new Number(1), new Text('test')])
    )];
    yield ['insert into user select 1, "test"', new Insert(
      new Table('user'),
      [],
      new Select([new Number(1), new Text('test')])
    )];
  }
}