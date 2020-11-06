<?php namespace text\sql\statement;

class Variable {
  public $name;

  public function __construct($name) {
    $this->name= $name;
  }

  public function visit($visitor) {
    return $visitor->variable($this);
  }
}