<?php namespace text\sql\statement;

class All {
  public $prefix;

  public function __construct($prefix= null) {
    $this->prefix= $prefix;
  }

  public function visit($visitor) {
    return $visitor->all($this);
  }
}