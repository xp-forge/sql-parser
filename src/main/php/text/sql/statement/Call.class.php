<?php namespace text\sql\statement;

class Call {
  public $name, $arguments;

  public function __construct($name, $arguments= []) {
    $this->name= strtolower($name);
    $this->arguments= $arguments;
  }

  public function visit($visitor) {
    return $visitor->call($this);
  }
}