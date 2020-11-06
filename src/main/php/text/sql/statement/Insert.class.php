<?php namespace text\sql\statement;

class Insert extends Expression {
  public $target, $columns, $source;

  public function __construct($target, $columns, $source) {
    $this->target= $target;
    $this->columns= $columns;
    $this->source= $source;
  }

  public function visit($visitor) {
    return $visitor->insert($this);
  }
}