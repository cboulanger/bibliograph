<?php

namespace lib\components;

use georgique\yii2\jsonrpc\Controller;

/**
 * Component that provides a queue of events that will be transported
 * to the browser. The actual transport will be handled by the
 * application (either simply piggy-backing on the response or
 * by implementing a "real" idependent event transport).
 */
class EventQueue extends \yii\base\Component
{
  const RPC_METHOD_NAME = "dispatchServerEvent";

  /**
   * Add an event to the queue.
   *
   * @param \yii\base\Event $event
   * @return void
   */
  public function add( \yii\base\Event $event )
  {
    //\Yii::debug("Event:" . $event->name, __METHOD__);
    Controller::addNotification(static::RPC_METHOD_NAME, [[
      'name' => $event->name,
      'data'  => $event->data
    ]]);
  }
}
