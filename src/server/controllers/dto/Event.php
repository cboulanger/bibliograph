<?php

namespace app\controllers\dto;

use ReflectionProperty;

class Event extends \JsonRpc2\Dto
{
  /** @var string */
  public $name;

  /** @var string */
  public $data;
}