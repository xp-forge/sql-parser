<?php namespace text\sql\statement;

class EitherOf {
  private $conditions;

  public function __construct(...$conditions) {
    $this->conditions= $conditions;
  }

  public function including($condition) {
    $this->conditions[]= $condition;
    return $this;
  }
}