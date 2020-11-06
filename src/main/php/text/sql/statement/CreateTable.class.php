<?php namespace text\sql\statement;

class CreateTable extends Expression {
  public $name, $columns;

  public function __construct($name, $columns) {
    $this->name= $name;
    $this->columns= $columns;
  }

  public function visit($visitor) {
    return $visitor->createTable($this);
  }
}