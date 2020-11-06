<?php namespace text\sql\statement;

class UseDatabase extends Expression {
  public $name;

  public function __construct($name) {
    $this->name= $name;
  }

  public function visit($visitor) {
    return $visitor->use($this);
  }
}