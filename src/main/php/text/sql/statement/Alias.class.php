<?php namespace text\sql\statement;

class Alias {
  public $field, $name;

  public function __construct($field, $name) {
    $this->field= $field;
    $this->name= $name;
  }

  public function visit($visitor) {
    return $visitor->alias($this);
  }
}