<?php namespace text\sql\unittest;

use text\sql\statement\{CreateTable, DropTable, AlterTable, Column, AddColumn};

class SchemaTest extends StatementTest {

  /** @return iterable */
  protected function statements() {
    yield ['create table user (user_id int, name varchar(255))', new CreateTable('user', [
      new Column('user_id', 'int'),
      new Column('name', 'varchar', 255),
    ])];
    yield ['drop table user', new DropTable('user')];
    yield ['alter table user add email varchar(255)', new AlterTable('user', new AddColumn(
      new Column('email', 'varchar', 255)
    ))];
  }
}