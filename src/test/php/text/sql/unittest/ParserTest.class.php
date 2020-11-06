<?php namespace text\sql\unittest;

use text\sql\statement\{Comparison, AllOf, EitherOf, Call, Values};
use text\sql\statement\{CreateTable, AlterTable, DropTable, Column};
use text\sql\statement\{Number, Text, Field, Literal, Table, Alias, All, Variable, System};
use text\sql\statement\{UseDatabase, Select, Insert, Update, Delete};
use text\sql\{Parser, SyntaxError};
use unittest\Assert;

class ParserTest {

  /** @return iterable */
  private function statements() {
    yield ['use test', new UseDatabase('test')];

    // Basic selects
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

    // Insert statements
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

    // Basic update statements
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

    // Delete statements
    yield ['delete from user', new Delete(new Table('user'))];
    yield ['delete from test.user', new Delete(new Table('test.user'))];
    yield ['delete from user where uid = 1', new Delete(
      new Table('user'),
      new Comparison(new Field(null, 'uid'), '=', new Number(1))
    )];

    // Schema modification queries
    yield ['create table user (user_id int, name varchar(255))', new CreateTable('user', [
      new Column('user_id', 'int'),
      new Column('name', 'varchar', 255),
    ])];
    yield ['drop table user', new DropTable('user')];

    // Quoted identifiers
    yield ['use `SELECT`', new UseDatabase('SELECT')];
    yield ['select `where`.name from user `where`', new Select(
      [new Field('where', 'name')],
      [new Table('user', 'where')]
    )];
  }

  /** @return iterable */
  private function show() {
    yield ['show events', ['show' => 'events']];
    yield ['show variables', ['show' => ['variables' => null]]];
    yield ['show variables like "sql_mode"', ['show' => ['variables' => new Text('sql_mode')]]];
  }

  #[Test]
  public function can_create() {
    new Parser();
  }

  #[Test, Values('statements')]
  public function single_statement($sql, $result) {
    Assert::equals([$result], (new Parser())->parse($sql)->tree());
  }

  #[Test]
  public function can_use_keywords_in_uppercase() {
    Assert::equals([new Select([new Number(1)])], (new Parser())->parse('SELECT 1')->tree());
  }

  #[Test, Values(['select 1; select 2', 'select 1; select 2;'])]
  public function two_statements($sql) {
    Assert::equals(
      [new Select([new Number(1)]), new Select([new Number(2)])],
      (new Parser())->parse($sql)->tree()
    );
  }

  #[Test, Values('show')]
  public function extend_parser_with_show_statement($sql, $result) {

    // Incomplete implementation of https://dev.mysql.com/doc/refman/8.0/en/show.html
    $fixture= (new Parser())->extend('show', function($parse, $token) {
      return ['show' => $parse->match([
        'events'    => function($parse, $token) { return 'events'; },
        'variables' => function($parse, $token) {
          if ('like' === $parse->token->symbol->id) {
            $parse->forward();
            return ['variables' => $parse->expression()];
          } else {
            return ['variables' => null];
          }
        }
      ])];
    });

    Assert::equals([$result], $fixture->parse($sql)->tree());
  }

  #[Test, Expect(class: SyntaxError::class, withMessage: 'Expecting a name, have .')]
  public function triple_dot_not_allowed_in_table() {
    (new Parser())->parse('select * from test...user')->tree();
  }

  #[Test, Expect(class: SyntaxError::class, withMessage: 'Expecting values or select, have (eof)')]
  public function insert_without_values_or_select() {
    (new Parser())->parse('insert into user')->tree();
  }

  #[Test, Expect(class: SyntaxError::class, withMessage: 'Expecting set, have name')]
  public function update_without_set() {
    (new Parser())->parse('update user name = "test"')->tree();
  }

  #[Test, Expect(class: SyntaxError::class, withMessage: 'Expecting from, have user')]
  public function delete_without_from() {
    (new Parser())->parse('delete user')->tree();
  }

  #[Test, Expect(class: SyntaxError::class, withMessage: 'Expecting ; or (eof), have select')]
  public function missing_semicolon_between_two_statements() {
    (new Parser())->parse('select 1 select 2')->tree();
  }
}