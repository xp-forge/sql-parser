<?php namespace text\sql;

use text\sql\parse\{State, Tokens, Symbol, Expecting};
use text\sql\statement\{
  AddColumn,
  Alias,
  All,
  AllOf,
  AlterTable,
  Binary,
  Call,
  Column,
  Comparison,
  CreateTable,
  Delete,
  DropColumn,
  DropTable,
  EitherOf,
  Field,
  Insert,
  Literal,
  Number,
  Order,
  Select,
  System,
  Table,
  Text,
  Update,
  UseDatabase,
  Values,
  Variable
};

class Parser {
  public $symbols= [];

  public function __construct() {
    $this->symbol('as');
    $this->symbol('by');
    $this->symbol('asc');
    $this->symbol('desc');
    $this->symbol('from');
    $this->symbol('group');
    $this->symbol('into');
    $this->symbol('limit');
    $this->symbol('not');
    $this->symbol('offset');
    $this->symbol('order');
    $this->symbol('values');
    $this->symbol('where');

    $this->symbol('use')->nud= function($parse, $token) {
      $name= $parse->token->value;
      $parse->forward();
      return new UseDatabase($name);
    };

    $this->symbol('select')->nud= function($parse, $token) {
      $fields= [];

      // A field can be one of the following:
      // - name, name as alias
      // - t.name, t.name as alias
      // - *, t.*
      // - (any expression)
      field:
      $field= $parse->expression();

      if ('as' === $parse->token->symbol->id) {
        $parse->forward();
        $fields[]= new Alias($field, $parse->token->value);
        $parse->forward();
      } else {
        $fields[]= $field;
      }

      if (',' === $parse->token->value) {
        $parse->forward();
        goto field;
      }

      // A source can be of the following:
      // - table
      // - database.table
      // - database..table (Transact-SQL)
      // - table t
      $sources= [];
      if ('from' === $parse->token->symbol->id) {
        $parse->forward();

        source:
        $name= $parse->table();

        if ('name' === $parse->token->symbol->id) {
          $sources[]= new Table($name, $parse->token->value);
          $parse->forward();
        } else {
          $sources[]= new Table($name);
        }

        if (',' === $parse->token->value) {
          $parse->forward();
          goto source;
        }
      }

      // [WHERE where_condition]
      if ('where' === $parse->token->symbol->id) {
        $parse->forward();
        $condition= $parse->expression();
      } else {
        $condition= null;
      }

      $select= new Select($fields, $sources, $condition);

      // [GROUP BY {col_name | expr | position} [ASC | DESC], ... ]
      if ('group' === $parse->token->symbol->id) {
        $parse->forward();
        $parse->expect('by');

        $by= [];
        group:
        $expr= $parse->expression();

        if ('asc' === $parse->token->symbol->id) {
          $order= Order::ASCENDING;
          $parse->forward();
        } else if ('desc' === $parse->token->symbol->id) {
          $order= Order::DESCENDING;
          $parse->forward();
        } else {
          $order= null;
        }
        $by[]= new Order($expr, $order);

        if (',' === $parse->token->value) {
          $parse->forward();
          goto group;
        }
        $select->group($by);
      }

      // [ORDER BY {col_name | expr | position} [ASC | DESC], ...]
      if ('order' === $parse->token->symbol->id) {
        $parse->forward();
        $parse->expect('by');

        $by= [];
        order:
        $expr= $parse->expression();

        if ('asc' === $parse->token->symbol->id) {
          $order= Order::ASCENDING;
          $parse->forward();
        } else if ('desc' === $parse->token->symbol->id) {
          $order= Order::DESCENDING;
          $parse->forward();
        } else {
          $order= null;
        }
        $by[]= new Order($expr, $order);

        if (',' === $parse->token->value) {
          $parse->forward();
          goto order;
        }
        $select->order($by);
      }

      // [LIMIT {[offset,] row_count | row_count OFFSET offset}]
      if ('limit' === $parse->token->symbol->id) {
        $parse->forward();
        $value= $parse->value();
        if ('offset' === $parse->token->symbol->id) {
          $parse->forward();
          $select->limit($parse->value(), $value);
        } else if (',' === $parse->token->value) {
          $parse->forward();
          $select->limit($value, $parse->value());
        } else {
          $select->limit(1, $value);
        }
      }

      return $select;
    };

    $this->symbol('insert')->nud= function($parse, $token) {
      if ('into' === $parse->token->symbol->id) {
        $parse->forward();
      }

      $target= new Table($parse->table());
      $columns= [];
      if ('(' === $parse->token->value) {
        $parse->forward();
        while (')' !== $parse->token->value) {
          $columns[]= $parse->token->value;
          $parse->forward();
          if (',' === $parse->token->value) {
            $parse->forward();
            continue;
          }
          // TODO: Parse error
        }
        $parse->expect(')');
      }

      if ('values' === $parse->token->symbol->id || 'select' === $parse->token->symbol->id) {
        $source= $parse->expression();
      } else {
        throw new Expecting('values or select', $parse->token);
      }

      return new Insert($target, $columns, $source);
    };

    $this->symbol('update')->nud= function($parse, $token) {
      $target= new Table($parse->table());

      $parse->expect('set');
      $set= [];
      set:
      $column= $parse->token->value;
      $parse->forward();
      $parse->expect('=');
      $set[$column]= $parse->expression();

      if (',' === $parse->token->value) {
        $parse->forward();
        goto set;
      }

      if ('where' === $parse->token->symbol->id) {
        $parse->forward();
        $condition= $parse->expression();
      } else {
        $condition= null;
      }

      return new Update($target, $set, $condition);
    };

    $this->symbol('delete')->nud= function($parse, $token) {
      $parse->expect('from');
      $target= new Table($parse->table());

      if ('where' === $parse->token->symbol->id) {
        $parse->forward();
        $condition= $parse->expression();
      } else {
        $condition= null;
      }

      return new Delete($target, $condition);
    };

    $this->symbol('values')->nud= function($parse, $token) {
      $list= [];

      values:
      $values= [];
      $parse->expect('(');
      while (')' !== $parse->token->value) {
        $values[]= $parse->expression();
        if (',' === $parse->token->value) {
          $parse->forward();
          continue;
        }
        // TODO: Parse error
      }
      $parse->expect(')');
      $list[]= $values;

      if (',' === $parse->token->value) {
        $parse->forward();
        goto values;
      }

      return new Values(...$list);
    };

    $this->symbol('create')->nud= function($parse, $token) {
      return $parse->match([
        'table' => function($parse, $token) {
          $table= $parse->token->value;
          $parse->forward();

          $columns= [];
          $parse->expect('(');
          do {
            $name= $parse->token->value;
            $parse->forward();

            $type= $parse->token->value;
            $parse->forward();

            if ('(' === $parse->token->value) {
              $parse->forward();
              $size= $parse->token->value;
              $parse->forward();
              $parse->expect(')');
            } else {
              $size= null;
            }
            $columns[]= new Column($name, $type, $size);

            // TODO: null / not null & default, primary key, auto_increment

            if (',' === $parse->token->value) {
              $parse->forward();
              continue;
            }
          } while (')' !== $parse->token->value);

          $parse->expect(')');
          return new CreateTable($table, $columns);
        }
      ]);
    };

    $this->symbol('alter')->nud= function($parse, $token) {
      return $parse->match([
        'table' => function($parse, $token) {
          $table= $parse->token->value;
          $parse->forward();

          return new AlterTable($table, $parse->match([
            'add' => function($parse, $token) {
              return $parse->match([
                'column' => function($parse, $token) {
                  $name= $parse->token->value;
                  $parse->forward();

                  $type= $parse->token->value;
                  $parse->forward();

                  if ('(' === $parse->token->value) {
                    $parse->forward();
                    $size= $parse->token->value;
                    $parse->forward();
                    $parse->expect(')');
                  } else {
                    $size= null;
                  }
                  return new AddColumn(new Column($name, $type, $size));
                }
              ]);
            },
            'drop' => function($parse, $token) {
              return $parse->match([
                'column' => function($parse, $token) {
                  $name= $parse->token->value;
                  $parse->forward();
                  return new DropColumn($name);
                }
              ]);
            }
          ]));
        }
      ]);
    };

    $this->symbol('drop')->nud= function($parse, $token) {
      return $parse->match([
        'table' => function($parse, $token) {
          $table= $parse->token->value;
          $parse->forward();
          return new DropTable($table);
        }
      ]);
    };

    // Literals
    $this->symbol('null')->nud= function($parse, $token) {
      return new Literal(null);
    };
    $this->symbol('number')->nud= function($parse, $token) {
      return new Number($token->value);
    };
    $this->symbol('string')->nud= function($parse, $token) {
      return new Text(substr($token->value, 1, -1));
    };
    $this->symbol('*')->nud= function($parse, $token) {
      return new All();
    };

    // Disambiguate @variable and @@system
    $this->symbol('@', 90)->nud= function($parse, $token) {
      if ('@' === $parse->token->value) {
        $parse->forward();
        $name= $parse->token->value;
        $parse->forward();
        return new System($name);
      } else {
        $name= $parse->token->value;
        $parse->forward();
        return new Variable($name);
      }
    };

    // Disambiguate the following:
    // - field
    // - alias.field
    // - alias.*
    // - function()
    // Not done via operators, these cannot be chained (`x.y.z`, `a.*.*` or `f()()`)!
    $this->symbol('name')->nud= function($parse, $token) {
      if ('.' === $parse->token->value) {
        $parse->forward();
        $name= $parse->token->value;
        $parse->forward();
        return '*' === $name ? new All($token->value) : new Field($token->value, $name);
      } else if ('(' === $parse->token->value) {
        $parse->forward();
        $arguments= [];

        while (')' !== $parse->token->value) {
          $arguments[]= $parse->expression();
          if (',' === $parse->token->value) {
            $parse->forward();
            continue;
          }
          // TODO: Parse error
        }

        $parse->expect(')');
        return new Call($token->value, $arguments);
      } else {
        return new Field(null, $token->value);
      }
    };

    // Logical operators
    $this->symbol('and', 30)->led= function($parse, $token, $left) {
      if ($left instanceof AllOf) {
        return $left->including($parse->expression(30));
      } else {
        return new AllOf($left, $parse->expression(30));
      }
    };
    $this->symbol('or', 30)->led= function($parse, $token, $left) {
      if ($left instanceof EitherOf) {
        return $left->including($parse->expression(30));
      } else {
        return new EitherOf($left, $parse->expression(30));
      }
    };

    // Comparison operators
    $this->symbol('=', 40)->led= function($parse, $token, $left) {
      return new Comparison($left, '=', $parse->expression(40));
    };
    $this->symbol('!=', 40)->led= function($parse, $token, $left) {
      return new Comparison($left, '!=', $parse->expression(40));
    };
    $this->symbol('<>', 40)->led= function($parse, $token, $left) {
      return new Comparison($left, '!=', $parse->expression(40));
    };
    $this->symbol('<', 40)->led= function($parse, $token, $left) {
      return new Comparison($left, '<', $parse->expression(40));
    };
    $this->symbol('>', 40)->led= function($parse, $token, $left) {
      return new Comparison($left, '>', $parse->expression(40));
    };
    $this->symbol('>=', 40)->led= function($parse, $token, $left) {
      return new Comparison($left, '>=', $parse->expression(40));
    };
    $this->symbol('<=', 40)->led= function($parse, $token, $left) {
      return new Comparison($left, '<=', $parse->expression(40));
    };
    $this->symbol('like', 40)->led= function($parse, $token, $left) {
      return new Comparison($left, 'like', $parse->expression(40));
    };
    $this->symbol('is', 40)->led= function($parse, $token, $left) {
      if ('not' === $parse->token->symbol->id) {
        $parse->forward();
        $op= 'isnot';
      } else {
        $op= 'is';
      }
      return new Comparison($left, $op, $parse->expression(40));
    };
    $this->symbol('in', 40)->led= function($parse, $token, $left) {
      $parse->expect('(');
      $list= [];

      while (')' !== $parse->token->value) {
        $list[]= $parse->expression(40);
        if (',' === $parse->token->value) {
          $parse->forward();
          continue;
        }
        // TODO: Parse error
      }

      $parse->expect(')');
      return new Comparison($left, 'in', $list);
    };
  
    // Binary operations
    $this->symbol('+', 50)->led= function($parse, $token, $left) {
      return new Binary($left, '+', $parse->expression(40));
    };
    $this->symbol('-', 50)->led= function($parse, $token, $left) {
      return new Binary($left, '-', $parse->expression(40));
    };
  }

  /**
   * Returns symbol for a given ID, creating it if necessary
   *
   * @param  string $id
   * @param  int $lbp
   * @return text.sql.parse.Symbol
   */
  public function symbol($id, $lbp= 0) {
    if (isset($this->symbols[$id])) {
      $symbol= $this->symbols[$id];
      if ($lbp > $symbol->lbp) $symbol->lbp= $lbp;
    } else {
      $symbol= new Symbol();
      $symbol->id= $id;
      $symbol->lbp= $lbp;
      $this->symbols[$id]= $symbol;
    }
    return $symbol;
  }

  /**
   * Extend this parser to parse a given keyword with a function
   *
   * @param  string $keyword
   * @param  function(text.sql.parse.State, text.sql.parse.Token): var
   * @return self
   */
  public function extend($keyword, $function) {
    $this->symbol($keyword)->nud= $function;
    return $this;
  }

  /**
   * Parse a given argument
   *
   * @param  io.streams.InputStream|io.File|io.Path|string|text.sql.parse.Tokens $arg
   * @return text.sql.parse.State
   */
  public function parse($arg) {
    return new State($this, $arg instanceof Tokens ? $arg : new Tokens($arg));
  }
}