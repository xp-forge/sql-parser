<?php namespace text\sql\parse;

class State {
  private $tokens, $rules;
  public $token;

  /**
   * Creates a new parse state
   *
   * @param  text.sql.Parser $rules
   * @param  text.sql.Tokens $tokens
   */
  public function __construct($rules, $tokens) {
    $this->rules= $rules;
    $this->tokens= $tokens->getIterator();
  }

  /**
   * Forward this parser to the next token
   *
   * @return void
   */
  public function forward() {
    while ($this->tokens->valid()) {
      $key= $this->tokens->key();
      $token= $this->tokens->current();

      // Do not lowercase quoted identifiers, rewriting them as names
      $this->tokens->next();
      if ('identifier' === $key) {
        $symbol= $this->rules->symbol('name');
      } else if ('comment' === $key) {
        continue;
      } else {
        $symbol= $this->rules->symbols[strtolower($token[0])] ?? $this->rules->symbol($key);
      }

      // echo '<< ', $symbol->id, ' (', $token[0], ")\n";
      $this->token= new Token($symbol, ...$token);
      return;
    }

    // echo '<< (EOF)', "\n";
    $this->token= new Token($this->rules->symbol('(eof)'), null, $this->token->line);
  }

  /**
   * Expects a given token, then forwards the parse state
   *
   * @param  string $token
   * @throws text.sql.SyntaxError
   */
  public function expect($token) {
    if ($token !== $this->token->value) {
      throw new Expecting($token, $this->token);
    }
    $this->forward();
  }

  /**
   * Returns next token value
   *
   * @return var
   */
  public function value() {
    $value= $this->token->value;
    $this->forward();
    return $value;
  }

  /**
   * Matches token values
   *
   * @param  [:(function(self, text.sql.parse.Token): var)] $cases
   * @return var
   * @throws text.sql.SyntaxError
   */
  public function match($cases) {
    $t= $this->token;
    if (null === ($f= $cases[$t->value] ?? null)) {
      throw new Expecting(array_keys($cases), $t);
    }

    $this->forward();
    return $f->__invoke($this, $t);
  }

  /**
   * Returns a table reference: `table`, `database.table` or `database..table`.
   *
   * @return string
   */
  public function table() {
    $name= '';
    do {
      if ('name' !== $this->token->symbol->id) {
        throw new Expecting('a name', $this->token);
      }

      $name.= $this->token->value;
      $this->forward();
      if ('.' === $this->token->value || '..' === $this->token->value) {
        $name.= $this->token->value;
        $this->forward();
        continue;
      }
      return $name;
    } while (true);
  }

  /**
   * Returns a single expression
   *
   * @param  int $rbp
   * @return text.sql.statement.Expression
   */
  public function expression($rbp= 0) {
    $t= $this->token;
    $this->forward();
    $left= $t->symbol->nud ? $t->symbol->nud->__invoke($this, $t) : $t;

    while ($rbp < $this->token->symbol->lbp) {
      $t= $this->token;
      $this->forward();
      $left= $t->symbol->led ? $t->symbol->led->__invoke($this, $t, $left) : $t;
    }

    return $left;
  }

  /**
   * Returns a single statement
   *
   * @return text.sql.statement.Expression
   */
  public function statement() {
    if ($this->token->symbol->std) {
      $t= $this->token;
      $this->forward();
      return $t->symbol->std->__invoke($this, $t);
    }

    $expr= $this->expression(0);

    // Check for semicolon separating two statements, or EOF
    if (';' === $this->token->value) {
      $this->forward();
    } else if (null !== $this->token->value) {
      throw new Expecting('; or (eof)', $this->token);
    }
    return $expr;
  }

  /** @return iterable */
  public function stream() {
    $this->forward();
    while (null !== $this->token->value) {
      if (null === ($statement= $this->statement())) break;
      yield $statement;
    }
  }

  /** @return text.sql.statement.Expression[] */
  public function tree() {
    $this->forward();
    $r= [];
    while (null !== $this->token->value) {
      if (null === ($statement= $this->statement())) break;
      $r[]= $statement;
    }
    return $r;
  }
}