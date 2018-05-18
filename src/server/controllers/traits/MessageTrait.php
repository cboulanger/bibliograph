<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 18.05.18
 * Time: 13:16
 */

namespace app\controllers\traits;

use Yii;

trait MessageTrait
{

  /**
   * Broadcasts a message to all connected clients.
   * NOTE this doesn't work at the moment, the message is only sent to the
   * current user's client.
   * @todo Reimplement
   * @param string $eventName
   * @param mixed $data
   * @return void
   */
  public function broadcastClientMessage($eventName, $data=null){
    $this->dispatchClientMessage($eventName, $data);
  }

  /**
   * Sends a message to the current user's application
   * @param [type] $eventName
   * @param [type] $data
   * @return void
   */
  public function  dispatchClientMessage($eventName, $data=null){
    Yii::$app->eventQueue->add( new \yii\base\Event([
      "name" => $eventName,
      "data" => $data
    ]));
  }
}