<?php
/**
 * Created by PhpStorm.
 * User: cboulanger
 * Date: 15.03.18
 * Time: 08:18
 */

namespace app\controllers\traits;

use app\models\Session;
use Yii;
use app\models\User;


/**
 * Trait AuthTrait
 * @package app\controllers\traits
 * @todo Rework "noAuthActions" property
 */
trait AuthTrait
{

  /**
   * @return array
   */
  protected function getNoAuthActions()
  {
    return $this->noAuthActions ?? [];
  }

  /**
   * Filter method to protect action methods from unauthorized access.
   * Uses the JSONRPC 2.0 auth extension or the 'auth' query string parameter
   * as fallback.
   *
   * @param \yii\base\Action $action
   * @return bool True if action can proceed, false if not
   * @throws \yii\web\BadRequestHttpException
   * @throws \yii\db\Exception
   */
  public function beforeAction($action)
  {
    if (!parent::beforeAction($action)) {
      return false;
    }

    // actions without authentication
    if (in_array($action->id, $this->noAuthActions)) {
      return true;
    }

    // on-the-fly authentication with access token
    $token = null;
    $headers = Yii::$app->request->headers;
    $tryHeaders = ["Authorization","X-Authorization"];
    foreach ($tryHeaders as $header) {
      if ($headers->has($header)) {
        $token = trim( str_replace("Bearer", "", $headers->get($header) ) );
      }
    }
    $user = User::findIdentityByAccessToken($token);
    if (!$token or ! $user ) {
      Yii::info("No or invalid authorization token '$token'. Access denied.");
      return false;
      // @todo this doesn't work:
      // throw new Exception('Missing authentication', AuthException::INVALID_REQUEST);
    }

    // log in user
    $user->online = true;
    $user->save();
    Yii::$app->user->setIdentity($user);
    /** @var Session $session */
    $session = $this->continueUserSession( $user );
    if ($session) {
      $session->touch();
    }
    $sessionId = $this->getSessionId();
    Yii::info("Authorized user '{$user->namedId}' via auth auth token (Session {$sessionId}.","auth");
    return true;
  }
}