<?php

namespace app\controllers\dto;

class Base extends \yii\base\BaseObject
{
  public function __toString(){
    return json_encode($this);
  }
}
