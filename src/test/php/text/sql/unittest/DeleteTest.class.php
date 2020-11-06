<?php namespace text\sql\unittest;

use text\sql\statement\{Delete, Number, Text, Field, Literal, Table, Comparison};

class DeleteTest extends StatementTest {

  /** @return iterable */
  protected function statements() {
    yield ['delete from user', new Delete(new Table('user'))];
    yield ['delete from test.user', new Delete(new Table('test.user'))];

    // Including WHERE
    yield ['delete from user where uid = 1', new Delete(
      new Table('user'),
      new Comparison(new Field(null, 'uid'), '=', new Number(1))
    )];
  }
}