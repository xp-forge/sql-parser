<?php namespace text\sql\unittest;

use text\sql\statement\{Comparison, AllOf, EitherOf};
use text\sql\statement\{Select, Call, Number, Text, Field, Literal, Table, Alias, All, Variable, System, Order};

class SelectTest extends StatementTest {

  /** @return iterable */
  protected function statements() {
    yield ['select 1', new Select([new Number(1)])];
    yield ['select 1, 2', new Select([new Number(1), new Number(2)])];
    yield ['select "Hello"', new Select([new Text('Hello')])];
    yield ['select @version', new Select([new Variable('version')])];
    yield ['select @@version', new Select([new System('version')])];
    yield ['select name from user', new Select([new Field(null, 'name')], [new Table('user')])];
    yield ['select u.name from user u', new Select([new Field('u', 'name')], [new Table('user', 'u')])];
    yield ['select * from user', new Select([new All()], [new Table('user')])];
    yield ['select * from test.user', new Select([new All()], [new Table('test.user')])];
    yield ['select * from test..user', new Select([new All()], [new Table('test..user')])];
    yield ['select * from test.schema.user', new Select([new All()], [new Table('test.schema.user')])];
    yield ['select u.* from user u', new Select([new All('u')], [new Table('user', 'u')])];
    yield ['select name, * from user', new Select([new Field(null, 'name'), new All()], [new Table('user')])];
    yield ['select uid as id from user', new Select([new Alias(new Field(null, 'uid'), 'id')], [new Table('user')])];
    yield ['select count(*) from user', new Select([new Call('count', [new All()])], [new Table('user')])];
    yield ['select count(u.*) from user u', new Select([new Call('count', [new All('u')])], [new Table('user', 'u')])];

    // Limit
    yield ['select null limit 1', (new Select([new Literal(null)]))->limit(1)];
    yield ['select null limit 1, 10', (new Select([new Literal(null)]))->limit(1, 10)];
    yield ['select null limit 10 offset 1', (new Select([new Literal(null)]))->limit(1, 10)];

    // Order by
    yield ['select * from user order by created', (new Select([new All()], [new Table('user')]))->order([
      new Order(new Field(null, 'created'), null)
    ])];
    yield ['select * from user order by rand()', (new Select([new All()], [new Table('user')]))->order([
      new Order(new Call('rand', []), null)
    ])];
    yield ['select * from user order by created asc', (new Select([new All()], [new Table('user')]))->order([
      new Order(new Field(null, 'created'), Order::ASCENDING)
    ])];
    yield ['select * from user order by created desc', (new Select([new All()], [new Table('user')]))->order([
      new Order(new Field(null, 'created'), Order::DESCENDING)
    ])];
    yield ['select * from user order by created, name', (new Select([new All()], [new Table('user')]))->order([
      new Order(new Field(null, 'created'), null),
      new Order(new Field(null, 'name'), null)
    ])];

    // Selects with WHERE clause
    yield ['select * from user where uid = 1', new Select(
      [new All()],
      [new Table('user')],
      new Comparison(new Field(null, 'uid'), '=', new Number(1))
    )];
    yield ['select * from user where created < now()', new Select(
      [new All()],
      [new Table('user')],
      new Comparison(new Field(null, 'created'), '<', new Call('now'))
    )];
    yield ['select * from user where uid = 1 and status != "deleted" and expired is null', new Select(
      [new All()],
      [new Table('user')],
      new AllOf(
        new Comparison(new Field(null, 'uid'), '=', new Number(1)),
        new Comparison(new Field(null, 'status'), '!=', new Text('deleted')),
        new Comparison(new Field(null, 'expired'), 'is', new Literal(null))
      )
    )];
    yield ['select * from user where status = "deleted" or expired is not null', new Select(
      [new All()],
      [new Table('user')],
      new EitherOf(
        new Comparison(new Field(null, 'status'), '=', new Text('deleted')),
        new Comparison(new Field(null, 'expired'), 'isnot', new Literal(null))
      )
    )];
    yield ['select u.*, p.* from user u, person p where u.user_id = p.user_id', new Select(
      [new All('u'), new All('p')],
      [new Table('user', 'u'), new Table('person', 'p')],
      new Comparison(new Field('u', 'user_id'), '=', new Field('p', 'user_id'))
    )];
  }
}