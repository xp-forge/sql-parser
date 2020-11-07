<?php namespace text\sql\parse;

class Token {
  public $symbol, $value, $line;

  public function __construct($symbol, $value, $line) {
    $this->symbol= $symbol;
    $this->value= $value;
    $this->line= $line;
  }

  /** @return string */
  public function name() { return $this->value ?? $this->symbol->id; }
}