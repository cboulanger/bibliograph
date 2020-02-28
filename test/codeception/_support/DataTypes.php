<?php

// these ar just marker classes that wrap primitive data types

class BaseType {
  protected $data;
  public function __construct($data)
  {
    $this->data = $data;
  }

  public function __toString()
  {
    return $this->data;
  }
}

class JsonExpressionType extends BaseType {}
class JsonPathType extends BaseType {}
class RegExType extends BaseType {}
