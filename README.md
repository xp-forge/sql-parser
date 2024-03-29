SQL Parser
==========

[![Build status on GitHub](https://github.com/xp-forge/sql-parser/workflows/Tests/badge.svg)](https://github.com/xp-forge/sql-parser/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/sql-parser/version.png)](https://packagist.org/packages/xp-forge/sql-parser)

This library parses SQL statements.

Examples
--------

```php
use text\sql\{Parser, SyntaxError};

$p= new Parser();
try {
  $statement= $p->parse('select * from user where user_id = 1');
} catch (SyntaxError $e) {
  // Handle
}

// new Select(
//   [new All()],
//   [new Table('user')],
//   new Comparison(new Field(null, 'uid'), '=', new Number(1))
// )
```

Support
-------
This library is not yet complete. Currently, the following are supported:

* USE database selection
* SELECT, INSERT, UPDATE and DELETE statements
* CREATE / DROP TABLE schema modification
* ALTER TABLE ADD / DROP COLUMN table modification

Other statements may be added via `extend()`:

```php
use text\sql\Parser;

$p= new Parser();

// Incomplete implementation of https://dev.mysql.com/doc/refman/8.0/en/show.html
$p->extend('show', function($parse, $token) {
  return ['show' => $parse->match([
    'events'    => function($parse, $token) { return 'events'; },
    'variables' => function($parse, $token) {
      if ('like' === $parse->token->symbol->id) {
        $parse->forward();
        return ['variables' => $parse->expression()];
      } else {
        return ['variables' => null];
      }
    }
  ])];
});

$statement= $p->parse('show variables like "sql_mode"');

// ['show' => ['variables' => new Text('sql_mode')]]
```