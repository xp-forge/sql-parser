<?php namespace text\sql\statement;

class AddColumn {
  public $definition;

  public function __construct($definition) {
    $this->definition= $definition;
  }
}