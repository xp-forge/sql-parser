<?php namespace text\sql\statement;

use lang\Value;
use util\Objects;

class Literal implements Value {
  public $value;

  public function __construct($value) { $this->value= $value; }

  public function visit($visitor) {
    return $visitor->literal($this);
  }

  public function toString() { return nameof($this).'('.Objects::stringOf($this->value).')'; }
  
  public function hashCode() { return Objects::hashOf($this->value); }
  
  public function compareTo($value) { return $value instanceof self ? $this->value <=> $value->value : 1; }
}