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
use lib\filters\auth\JsonRpcPayloadTokenAuth;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\web\Controller;


/**
 * Base class for controllers
 */
class AppController extends Controller
{
  use AuthTrait;
  use MessageTrait;
  use DatasourceTrait;
  use ShelfTrait;
  use AccessControlTrait;

  // Disable CSRF validation for JSON-RPC POST requests
  public $enableCsrfValidation = false;

  /**
   * The category of this class
   */
  const CATEGORY = "app";

  /**
   * A message name that triggers a jsonrpc request from the client
   */
  const MESSAGE_EXECUTE_JSONRPC = "jsonrpc.execute";

  /**
   * @var array Array of names of the actions that are accessible without authentication
   */
  protected $noAuthActions = [];

  /**
   * @inheritdoc
   */
  public function behaviors()
  {
    $authMethods = [HttpBearerAuth::class, QueryParamAuth::class];
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
    return $behaviors;
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
