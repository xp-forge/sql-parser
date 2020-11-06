<?php namespace text\sql\statement;

class Order {
  const ASCENDING = 'asc';
  const DESCENDING = 'desc';

  public $expression, $order;

  public function __construct($expression, $order= null) {
    $this->expression= $expression;
    $this->order= $order;
  }
}