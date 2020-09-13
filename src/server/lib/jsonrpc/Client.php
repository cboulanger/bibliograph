<?php

namespace app\lib\jsonrpc;

use lib\channel\MessageEvent;
use Yii;

class Client {

  /**
   * A message name that triggers a jsonrpc request from the client
   */
  const MESSAGE_EXECUTE_JSONRPC = "jsonrpc.execute";

  /**
   * Send a message to the client which tells it to execute the given JSON-RPC method
   *
   * @param string $method The method name. Can contain periods to separate service
   * and method. For Yii2 purposes, it will treat the part before the last period as
   * the service (controller) and the part after as the method (action) name.
   * @param array $params
   */
  static function execute(string $method, array $params) {
    $parts = explode(".", $method);
    $method = array_pop($parts);
    $service = implode(".", $parts);
    Yii::$app->eventQueue->add(new MessageEvent([
      'name' => static::MESSAGE_EXECUTE_JSONRPC,
      'data' => [$service, $method, $params]
    ]));
  }
}
