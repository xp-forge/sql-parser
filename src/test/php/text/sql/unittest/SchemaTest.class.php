<?php namespace text\sql\unittest;

use text\sql\statement\{CreateTable, DropTable, Column};

class SchemaTest extends StatementTest {

  /** @return iterable */
  protected function statements() {
    yield ['create table user (user_id int, name varchar(255))', new CreateTable('user', [
      new Column('user_id', 'int'),
      new Column('name', 'varchar', 255),
    ])];
    yield ['drop table user', new DropTable('user')];
  }
}