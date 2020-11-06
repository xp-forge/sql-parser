<?php namespace text\sql\statement;

class AlterTable extends Expression {
  public $name, $operation;

  public function __construct($name, $operation) {
    $this->name= $name;
    $this->operation= $operation;
  }

  public function visit($visitor) {
    return $visitor->alterTable($this);
  }
}