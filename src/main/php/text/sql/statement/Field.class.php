<?php namespace text\sql\statement;

class Field {
  public $prefix, $name;

  public function __construct($prefix, $name) {
    $this->prefix= $prefix;
    $this->name= $name;
  }

  public function visit($visitor) {
    return $visitor->field($this);
  }
}