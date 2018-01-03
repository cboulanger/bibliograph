<?php

namespace app\controllers\sse;

use odannyc\Yii2SSE\SSEBase;

class MessageEventHandler extends SSEBase
{
  public function check()
  {
    return true;
  }

  public function update()
  {
    return "Something Cool";
  }
}