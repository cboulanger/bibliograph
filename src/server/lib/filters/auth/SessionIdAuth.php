<?php

namespace lib\filters\auth;

use app\controllers\AppController;
use app\models\User;
use Yii;
use yii\filters\auth\AuthMethod;

/**
 * A filter that authenticates based on the session id, useful mainly
 * for normal GET/POST requests
 */
class SessionIdAuth extends AuthMethod
{

  /**
   * {@inheritdoc}
   * @throws \yii\db\Exception
   */
  public function authenticate($user, $request, $response)
  {
    /** @var AppController $controller */
    $controller = Yii::$app->controller;
    if ($controller instanceof AppController && !$controller->getAllowSessionAuth()) {
      return null;
    }
    $sessionId = Yii::$app->session->id;
    if ($sessionId) {
      $identity = User::findIdentityBySessionId($sessionId, $request);
      if ($identity !== null) {
        return $identity;
      }
    }
    return null;
  }
}
