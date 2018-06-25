<?php

namespace app\controllers\dto;

class ConfigLoadResult extends \JsonRpc2\Dto
{
  /** @var string[] */
  public $keys;

  /** @var int[] */
  public $types;

  /** @var mixed[] */
  public $values;  
}