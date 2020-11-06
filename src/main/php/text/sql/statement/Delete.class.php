<?php namespace text\sql\statement;

class Delete extends Expression {
  public $target, $condition;

  public function __construct($target, $condition= null) {
    $this->target= $target;
    $this->condition= $condition;
  }

  public function visit($visitor) {
    return $visitor->delete($this);
  }
}