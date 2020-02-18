<?php

namespace app\controllers\dto;


class ConfigLoadResult extends Base
{
  /** @var string[] */
  public $keys;

  /** @var int[] */
  public $types;

  /** @var mixed[] */
  public $values;
}
