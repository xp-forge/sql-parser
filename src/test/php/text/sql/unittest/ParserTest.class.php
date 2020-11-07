<?php namespace text\sql\unittest;

use text\sql\Parser;
use text\sql\parse\SyntaxError;
use text\sql\statement\{UseDatabase, Select, Number, Text, Field, Literal, Table};
use unittest\Assert;

class ParserTest {

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

  #[Test, Values(['select 1', 'select 1;'])]
  public function single_statement($sql) {
    Assert::equals(
      [new Select([new Number(1)])],
      (new Parser())->parse($sql)->tree()
    );
  }

  #[Test, Values(['select 1; select 2', 'select 1; select 2;'])]
  public function two_statements($sql) {
    Assert::equals(
      [new Select([new Number(1)]), new Select([new Number(2)])],
      (new Parser())->parse($sql)->tree()
    );
  }

  #[Test]
  public function can_use_keywords_in_uppercase() {
    Assert::equals([new Select([new Number(1)])], (new Parser())->parse('SELECT 1')->tree());
  }

  #[Test]
  public function can_quote_keywords() {
    Assert::equals([new UseDatabase('SELECT')], (new Parser())->parse('use `SELECT`')->tree());
  }

  #[Test]
  public function quoting_keywords_disambiguates_where_statement() {
    Assert::equals(
      [new Select([new Field('where', 'name')], [new Table('user', 'where')])],
      (new Parser())->parse('select `where`.name from user `where`')->tree()
    );
  }

  #[Test]
  public function comment() {
    Assert::equals(
      [new UseDatabase('test')],
      (new Parser())->parse('use test -- the west')->tree()
    );
  }

  #[Test]
  public function comment_between_lines() {
    $sql= trim('
      select name -- The username
      from user
    ');
    Assert::equals(
      [new Select([new Field(null, 'name')], [new Table('user')])],
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

  #[Test, Expect(class: SyntaxError::class, withMessage: 'Expecting one of add or drop, have select')]
  public function unmatched_case() {
    (new Parser())->parse('alter table user select')->tree();
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