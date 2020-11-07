<?php namespace text\sql\parse;

use text\sql\SyntaxError;

class Expecting extends SyntaxError {

  public function __construct($tokens, $actual) {
    if (is_array($tokens)) {
      $last= array_pop($tokens);
      $expecting= empty($tokens) ? $last : 'one of '.implode(', ', $tokens).' or '.$last;
    } else {
      $expecting= $tokens;
    }

    parent::__construct('Expecting '.$expecting.', have '.$actual->name().' on line '.$actual->line);
  }
}