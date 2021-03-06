<?php namespace text\sql\statement;

class Comparison {
  public $left, $operator, $right;

  public function __construct($left, $operator, $right) {
    $this->left= $left;
    $this->operator= $operator;
    $this->right= $right;
  }

  public function visit($visitor) {
    return $visitor->comparison($this);
  }
}