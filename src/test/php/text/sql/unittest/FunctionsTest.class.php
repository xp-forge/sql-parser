<?php namespace text\sql\unittest;

use text\sql\statement\{Select, Call, Number, Table, All};

class FunctionsTest extends StatementTest {

  /** @return iterable */
  protected function statements() {
    yield ['select now()', new Select([new Call('now', [])])];
    yield ['select now ()', new Select([new Call('now', [])])];
    yield ['select mod(29, 2)', new Select([new Call('mod', [new Number(29), new Number(2)])])];
    yield ['select count(*) from user', new Select([new Call('count', [new All()])], [new Table('user')])];
    yield ['select count(u.*) from user u', new Select([new Call('count', [new All('u')])], [new Table('user', 'u')])];
  }
}