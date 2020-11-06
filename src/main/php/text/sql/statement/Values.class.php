<?php namespace text\sql\statement;

class Values {
  public $list;

  public function __construct($list) {
    $this->list= $list;
  }

  public function visit($visitor) {
    return $visitor->values($this);
  }
}