<?php namespace text\sql\statement;

class System {
  public $name;

  public function __construct($name) {
    $this->name= $name;
  }

  public function visit($visitor) {
    return $visitor->system($this);
  }
}