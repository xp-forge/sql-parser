<?php namespace text\sql\statement;

class DropColumn {
  public $name;

  public function __construct($name) {
    $this->name= $name;
  }
}