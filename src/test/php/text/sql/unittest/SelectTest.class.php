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
    yield ['select * from user', new Select([new All()], [new Table('user')])];
    yield ['select * from test.user', new Select([new All()], [new Table('test.user')])];
    yield ['select * from test..user', new Select([new All()], [new Table('test..user')])];
    yield ['select * from sys.obj.user', new Select([new All()], [new Table('sys.obj.user')])];
    yield ['select name from user', new Select([new Field(null, 'name')], [new Table('user')])];
    yield ['select u.name from user u', new Select([new Field('u', 'name')], [new Table('user', 'u')])];
    yield ['select user.name from user', new Select([new Field('user', 'name')], [new Table('user')])];
    yield ['select test.user.name from test.user', new Select([new Field('test.user', 'name')], [new Table('test.user')])];
    yield ['select sys..user.name from sys..user', new Select([new Field('sys..user', 'name')], [new Table('sys..user')])];
    yield ['select sys.obj.user.name from sys.obj.user', new Select([new Field('sys.obj.user', 'name')], [new Table('sys.obj.user')])];
    yield ['select u.* from user u', new Select([new All('u')], [new Table('user', 'u')])];
    yield ['select user.* from user', new Select([new All('user')], [new Table('user')])];
    yield ['select test.user.* from test.user', new Select([new All('test.user')], [new Table('test.user')])];
    yield ['select sys..user.* from sys..user', new Select([new All('sys..user')], [new Table('sys..user')])];
    yield ['select sys.obj.user.* from sys.obj.user', new Select([new All('sys.obj.user')], [new Table('sys.obj.user')])];
    yield ['select name, * from user', new Select([new Field(null, 'name'), new All()], [new Table('user')])];
    yield ['select uid as id from user', new Select([new Alias(new Field(null, 'uid'), 'id')], [new Table('user')])];

    // Limit
    yield ['select null limit 10', (new Select([new Literal(null)]))->limit(1, 10)];
    yield ['select null limit 1, 10', (new Select([new Literal(null)]))->limit(1, 10)];
    yield ['select null limit 10 offset 1', (new Select([new Literal(null)]))->limit(1, 10)];

    // Order by
    $select= (new Select([new All()], [new Table('user')]));
    yield ['select * from user order by created', $select->order([
      new Order(new Field(null, 'created'), null)
    ])];
    yield ['select * from user order by rand()', $select->order([
      new Order(new Call('rand', []), null)
    ])];
    yield ['select * from user order by created asc', $select->order([
      new Order(new Field(null, 'created'), Order::ASCENDING)
    ])];
    yield ['select * from user order by created desc', $select->order([
      new Order(new Field(null, 'created'), Order::DESCENDING)
    ])];
    yield ['select * from user order by created, name', $select->order([
      new Order(new Field(null, 'created'), null),
      new Order(new Field(null, 'name'), null)
    ])];

    // Group by
    $select= (new Select([new Field(null, 'status'), new Call('count', [new All()])], [new Table('user')]));
    yield ['select status, count(*) from user group by status', $select->group([
      new Order(new Field(null, 'status'), null)
    ])];
    yield ['select status, count(*) from user group by status asc', $select->group([
      new Order(new Field(null, 'status'), Order::ASCENDING)
    ])];
    yield ['select status, count(*) from user group by status desc', $select->group([
      new Order(new Field(null, 'status'), Order::DESCENDING)
    ])];

    // Selects with WHERE clause
    yield ['select * from user where uid = 1', new Select(
      [new All()],
      [new Table('user')],
      new Comparison(new Field(null, 'uid'), '=', new Number(1))
    )];
    yield ['select * from user where uid in (1, 2)', new Select(
      [new All()],
      [new Table('user')],
      new Comparison(new Field(null, 'uid'), 'in', [new Number(1), new Number(2)])
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