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

use Yii;

use app\models\User;
use app\models\Session;
use \JsonRpc2\Exception;

/**
 * Service class providing methods to get or set configuration
 * values
 */
class AppController extends \JsonRpc2\Controller
{
  use traits\ShimTrait;
  use traits\RbacTrait;
  use traits\AuthTrait;

  //-------------------------------------------------------------
  // Authentication
  //-------------------------------------------------------------

  
  public function xxxbehaviors()
  {
    return [
      'authenticator' => [
        'class' => \yii\filters\auth\CompositeAuth::className(),
        'authMethods' => [
          [
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
            'except' => ['authenticate']
          ]
        ],
      ]
    ];
  }

  /**
   * Returns the [[app\models\User]] instance of the user with the given
   * username.
   *
   * @param string $username
   * @throws InvalidArgumentException if user does not exist
   * @return \app\models\User
   */
  public function user($username)
  {
    $user = User::findOne(['namedId'=>$username]);
    if (is_null($user)) {
      throw new \InvalidArgumentException( $this->tr("User '$username' does not exist.") );
    }
    return $user;
  }

  /**
   * Tries to continue an existing session
   *
   * @param \app\models\User $user
   * @return \app\model\Session|null The session object to be reused, or null
   * if none exists.
   */
  protected function continueUserSession($user)
  {
    $session = Session::findOne(['UserId' => $user->id]);
    if ($session) {
      // manually set session id to recover the session data
      session_id( $session->namedId );
    }
    Yii::$app->session->open();
    return $session;
  }

  /**
   * Filter method to protect action methods from unauthorized access.
   * Uses the JSONRPC 2.0 auth extension or the 'auth' query string parameter
   * as fallback.
   *
   * @param \yii\base\Action $action
   * @return boolan True if action can proceed, false if not
   */
  public function beforeAction($action)
  {
    if (!parent::beforeAction($action)) {
      return false;
    }

    // actions without authentication
    if (in_array($action->id, ["authenticate","setup"])) {
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
    if (!$token or ! $user = User::findIdentityByAccessToken($token)) {
      Yii::info("No or invalid authorization token '$token'. Access denied.");
      return false;
      // @todo this doesn't work:
      // throw new Exception('Missing authentication', AuthException::INVALID_REQUEST);
    }

    // log in user
    $user->online = true;
    $user->save();
    Yii::$app->user->setIdentity($user);
    $session = $this->continueUserSession( $user );
    if ($session) {
      $session->touch();
    }
    $sessionId = $this->getSessionId();
    Yii::info("Authorized user '{$user->namedId}' via auth auth token (Session {$sessionId}.");
    return true;
  }

  /**
   * Shorthand getter for  the current session id.
   * @return string
   */
  public function getSessionId()
  {
    return Yii::$app->session->getId();
  }

  //-------------------------------------------------------------
  // Datasources and models
  //-------------------------------------------------------------

  /**
   * Checks if user has access to the given datasource. If not,
   * throws JsonRpcException.
   * @param string $datasource
   * @return void
   * @throws Exception
   */
  public function checkDatasourceAccess($datasource)
  {
    static $myDatasources = null;
    if ( is_null( $myDatasources ) ){
      $myDatasources = $this->getActiveUser()->getDatasourceNames();
    }
    if( ! in_array($datasource, $myDatasources) ){
      // @todo: temporary disabled
      //throw new \InvalidArgumentException("Invalid or unauthorized datasource '$datasource'");
    }
  }

  /**
   * Returns the class name of the main model type of the controller as determined by the datasource
   * @todo check datasource access
   * @param string $datasource
   * @return string
   */
  static public function controlledModel( $datasource )
  {
    return  
      Datasource
        :: getInstanceFor( $datasource )
        :: getClassFor( static::$modelType );
  }

  /**
   * Returns the model with the given id 
   *
   * @param string $datasource
   * @param int $id
   * @return void
   */
  static public function getModelbyId($datasource, $id)
  {
    $model = static :: controlledModel($datasource) :: findOne($id);
    if( is_null( $model) ){
      throw new \InvalidArgumentException("Model of type " . static::$modelType . " and id #$id does not exist in datasource '$datasource'.");
    }
    return $model;
  }  
}
