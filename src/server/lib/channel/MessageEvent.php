<?php

namespace lib\channel;

use Yii;
use app\models\Message;
use app\models\Session;

/**
 * A message event that is triggered on the application instance when a new
 * message is posted to a channel
 */
class MessageEvent extends yii\base\Event
{

}