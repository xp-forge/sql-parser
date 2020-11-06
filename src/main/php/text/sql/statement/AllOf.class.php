<?php namespace text\sql\statement;

class AllOf {
  public $conditions;

  public function __construct(...$conditions) {
    $this->conditions= $conditions;
  }

  public function including($condition) {
    $this->conditions[]= $condition;
    return $this;
  }

  public function visit($visitor) {
    return $visitor->allOf($this);
  }
}