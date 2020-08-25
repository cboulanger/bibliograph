<?php

namespace lib\filters\auth;

use app\models\User;
use Yii;
use yii\filters\auth\AuthMethod;

/**
 * A filter that authenticates based on the session id
 */
class SessionIdAuth extends AuthMethod
{

  /**
   * {@inheritdoc}
   * @throws \yii\db\Exception
   */
  public function authenticate($user, $request, $response)
  {
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
