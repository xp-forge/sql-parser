<?php namespace text\sql\statement;

class Select extends Expression {
  public $values, $sources, $condition;
  public $limit= null;
  public $order= null;
  public $group= null;

  public function __construct($values, $sources= [], $condition= null) {
    $this->values= $values;
    $this->sources= $sources;
    $this->condition= $condition;
  }

  public function limit($offset, $count= null) {
    $this->limit= [$offset, $count];
    return $this;
  }

  public function order($by) {
    $this->order= $by;
    return $this;
  }

  public function group($by) {
    $this->group= $by;
    return $this;
  }

  public function visit($visitor) {
    return $visitor->select($this);
  }
}