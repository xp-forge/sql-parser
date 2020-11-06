<?php namespace text\sql\statement;

class Column {
  public $name, $type, $size;
  public $nullable= true;
  public $identity= false;
  public $default= null;

  public function __construct($name, $type, $size= null) {
    $this->name= $name;
    $this->type= $type;
    $this->size= $size;
  }

  public function nullable($value) { $this->nullable= $value; return $this; }

  public function identity($value) { $this->identity= $value; return $this; }

  public function default($expr) { $this->default= $expr; return $this; }

}