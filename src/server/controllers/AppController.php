<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
   2007-2017 Christian Boulanger

   License:
   LGPL: http://www.gnu.org/licenses/lgpl.html
   EPL: http://www.eclipse.org/org/documents/epl-v10.php
   See the LICENSE file in the project's top-level directory for details.

   Authors:
   * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use app\controllers\traits\{
  AccessControlTrait, AuthTrait, DatasourceTrait, MessageTrait, ShelfTrait
};
use app\models\Session;
use app\controllers\traits\JsonRpcTrait;
use lib\filters\auth\JsonRpcPayloadTokenAuth;
use lib\filters\auth\SessionIdAuth;
use Yii;
use yii\base\Event;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\web\Controller;
use yii\web\User;


/**
 * Base class for controllers
 * @param bool $allowSessionAuth Whether this controller allows to authenticate
 * via the session id
 */
class AppController extends Controller
{
  use AuthTrait;
  use MessageTrait;
  use DatasourceTrait;
  use ShelfTrait;
  use AccessControlTrait;
  use JsonRpcTrait;

  // Disable CSRF validation for JSON-RPC POST requests
  public $enableCsrfValidation = false;

  /**
   * The category of this class
   */
  const CATEGORY = "app";

  /**
   * Category for debug messages
   */
  const DEBUG = "debug";

  /**
   * A message name that triggers a jsonrpc request from the client
   */
  const MESSAGE_EXECUTE_JSONRPC = "jsonrpc.execute";

  /**
   * @var array Array of names of the actions that are accessible without authentication
   */
  protected $noAuthActions = [];

  /**
   * @var bool
   */
  protected $allowSessionAuth = false;

  /**
   * @inheritdoc
   */
  public function behaviors()
  {
    $oldSessionId = Yii::$app->session->id;
    $authMethods = [HttpBearerAuth::class, QueryParamAuth::class, SessionIdAuth::class];
    // codecdeption tests do not pass the Bearer Authentication Header correctly
    if (defined('JSON_RPC_USE_PAYLOAD_TOKEN_AUTH') AND JSON_RPC_USE_PAYLOAD_TOKEN_AUTH===true) {
      $authMethods[] = JsonRpcPayloadTokenAuth::class;
    }
    $behaviors = parent::behaviors();
    $behaviors['authenticator'] = [
      'class' => CompositeAuth::class,
      'authMethods' => $authMethods,
      'optional' => $this->noAuthActions
    ];
    // this changes the session id in the database to automatically renewed one
    Event::on(User::class, User::EVENT_AFTER_LOGIN, function($event) use ($oldSessionId){
      $user = $event->identity;
      //Yii::debug("User: $user->name ", self::DEBUG);
      $newSessionId = Yii::$app->session->id;
      //Yii::debug("New session id: $newSessionId", self::DEBUG);
      if ($session = Session::findOne(['namedId' => $oldSessionId])) {
        // contiunue session
        $session->namedId = $newSessionId;
        $session->save();
        //Yii::debug("Renamed session $oldSessionId into $newSessionId", self::DEBUG);
      } else if ($session = Session::findOne(['namedId' => $newSessionId])) {
        // reuse existing session
        $session->touch();
      } else {
        // create new session
        $session = new Session(['namedId' => $newSessionId]);
        $session->link('user',$user);
        $session->save();
        //Yii::debug("Starting sesssion {$sessionId}", self::CATEGORY);
      }
    });
    return $behaviors;
  }

  /**
   * @return bool
   */
  function getAllowSessionAuth() {
    return $this->allowSessionAuth;
  }

  //-------------------------------------------------------------
  // Helpers for returning data to the user
  //-------------------------------------------------------------

  /**
   * Return a message that can be send as the result of an action which does
   * not return anything. The message is purely for diagnostic and debug reasons.
   * @param string|null $reason (optional) reason for the abort
   * @return string
   */
  public function successfulActionResult()
  {
    return Yii::$app->requestedRoute . " was successful.";
  }

  /**
   * Return a message that can be send as the result of an action if this action
   * is aborted as response to user feedback. The message is purely for diagnostic
   * and debug reasons.
   * @param string|null $reason (optional) reason for cancelling
   * @return string
   */
  public function cancelledActionResult($reason=null)
  {
    return Yii::$app->requestedRoute . " was cancelled" . ($reason ? ": $reason." : ".");
  }

  /**
   * Return a message that can be send as the result of a failed action if this action
   * is aborted as response to user feedback without throwing an exception.
   * The message is purely for diagnostic and debug reasons.
   * @param string|null $reason (optional) reason for the abort
   * @return string
   */
  public function abortedActionResult($reason=null)
  {
    return "Aborted " . Yii::$app->requestedRoute . ($reason ? ": $reason." : ".");
  }

}
