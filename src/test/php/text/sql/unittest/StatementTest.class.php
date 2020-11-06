<?php namespace text\sql\unittest;

use text\sql\Parser;
use unittest\Assert;

abstract class StatementTest {

  /** @return iterable */
  protected abstract function statements();

  #[Test, Values('statements')]
  public function test($sql, $result) {
    Assert::equals([$result], (new Parser())->parse($sql)->tree());
  }
}