<?php

namespace app\controllers\traits;

use georgique\yii2\jsonrpc\Controller;

trait JsonRpcTrait {

  /**
   * Sends a notificaiton to the client
   * @param string $method
   * @param array $params
   */
  protected function addNotification(string $method, array $params) {
    Controller::addNotification($method, $params);
  }
}
