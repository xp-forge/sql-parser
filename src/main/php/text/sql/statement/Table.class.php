<?php namespace text\sql\statement;

class Table {
  public $name, $prefix;

  public function __construct($name, $prefix= null) {
    $this->name= $name;
    $this->prefix= $prefix;
  }
}