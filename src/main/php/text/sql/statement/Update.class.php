<?php namespace text\sql\statement;

class Update extends Expression {
  public $target, $set, $condition;

  public function __construct($target, $set, $condition= null) {
    $this->target= $target;
    $this->set= $set;
    $this->condition= $condition;
  }

  public function visit($visitor) {
    return $visitor->update($this);
  }
}