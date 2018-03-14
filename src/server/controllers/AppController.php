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

use lib\dialog\Error;
use lib\exceptions\UserErrorException;
use Yii;

use app\models\{
  User, Role, Session, Permission, Datasource
};


/**
 * Service class providing methods to get or set configuration
 * values
 */
class AppController extends \JsonRpc2\Controller
{

  /**
   * Array of action names that can be accessed without authentication
   *
   * @var array
   */
  protected $noAuthActions = [];

  //-------------------------------------------------------------
  // Overridden methods
  //-------------------------------------------------------------


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
    $session = $this->continueUserSession( $user );
    if ($session) {
      $session->touch();
    }
    $sessionId = $this->getSessionId();
    Yii::info("Authorized user '{$user->namedId}' via auth auth token (Session {$sessionId}.","auth");
    return true;
  }

  // public function behaviors()
  // {
  //   return [
  //     'authenticator' => [
  //       'class' => \yii\filters\auth\CompositeAuth::className(),
  //       'authMethods' => [
  //         [
  //           'class' => \yii\filters\auth\HttpBearerAuth::className(),
  //           'except' => ['authenticate']
  //         ]
  //       ],
  //     ]
  //   ];
  // }
  
  /**
   * Overridden to catch User exception
   * @inheritDoc
   */
  protected function _runAction($method)
  {
    try{
      return parent::_runAction($method);
    } catch ( UserErrorException $e ){
      Yii::info("User Error: " . $e->getMessage());
      Error::create($e->getMessage());
      return null;
    }
  }

  //-------------------------------------------------------------
  // Added methods
  //-------------------------------------------------------------
  

  /**
   * Shorthand getter for active user object
   * @return \app\models\User|\yii\web\IdentityInterface
   */
  public function getActiveUser()
  {
    return Yii::$app->user->identity;
  }

  /**
   * Creates a new anonymous guest user
   * @throws \LogicException
   * @return \app\models\User
   * @throws \yii\db\Exception
   */
  public function createAnonymous()
  {
    $anonRole = Role::findByNamedId('anonymous');
    if (is_null($anonRole)) {
      throw new \LogicException("No 'anonymous' role defined.");
    }
    $user = new User(['namedId' => \microtime() ]); // random temporary username
    $user->save();
    $user->namedId = "guest" . $user->getPrimaryKey();
    $user->name = "Guest";
    $user->anonymous = $user->active = true;
    $user->save();
    $user->link("roles", $anonRole);
    return $user;
  }

 /**
   * Returns true if a permission with the given named id exists and false if
   * not.
   * @param string $namedId The named id of the permission
   * @return bool
   */
  public function permissionExists($namedId)
  {
    return (bool) Permission::findOne(['namedId' => $namedId]);
  }

  /**
   * Creates a permission with the given named id if it doesn't
   * already exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @param string $description Optional description of the permission.
   *    Only used when first argument is a string.
   * @return void
   * @throws \yii\db\Exception
   */
  public function addPermission($namedId, $description = null)
  {
    if (is_array($namedId)) {
      foreach ($namedId as $id) {
        $this->addPermission( $id );
      }
      return;
    }
    $permission = new Permission([ 'namedId' => $namedId, 'description' => $description ]);
    $permission->save();
  }

  /**
   * Removes a permission with the given named id. Silently fails if the
   * permission doesn't exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @return void
   */
  public function removePermission($namedId)
  {
    if (is_array($namedId)) {
      foreach ($namedId as $id) {
        $this->removePermission( $id );
      }
      return;
    }
    Permission::deleteAll(['namedId' => $namedId]);
  }

  /**
   * Checks if active user has the given permission and aborts if
   * permission is not granted.
   *
   * @param string $permission
   * @throws \JsonRpc2\Exception
   */
  public function requirePermission($permission)
  {
    if (! $this->getActiveUser()->hasPermission( $permission )) {
      Yii::warning( sprintf(
        "Active user %s does not have required permission %s",
        $this->getActiveUser()->namedId, $permission
      ) );
      throw new \JsonRpc2\Exception("Not allowed.", \JsonRpc2\Exception::INVALID_REQUEST);
    }
  }

  /**
   * Shorthand method to enforce if active user has a role
   * @param string $role
   * @throws \JsonRpc2\Exception
   */
  public function requireRole($role)
  {
    if (! $this->getActiveUser()->hasRole( $role )) {
      Yii::warning( sprintf(
      "Active user %s does hat required role %s",
        $this->getActiveUser()->namedId, $role
      ) );
      throw new \JsonRpc2\Exception("Not allowed.", \JsonRpc2\Exception::INVALID_REQUEST);
    }
  }  

  /**
   * Returns the [[app\models\User]] instance of the user with the given
   * username.
   *
   * @param string $username
   * @throws \InvalidArgumentException if user does not exist
   * @return \app\models\User
   */
  public function user($username)
  {
    $user = User::findOne(['namedId'=>$username]);
    if (is_null($user)) {
      throw new \InvalidArgumentException( Yii::t('app',"User '$username' does not exist.") );
    }
    return $user;
  }

  /**
   * Tries to continue an existing session
   *
   * @param User $user
   * @return Session|null
   *    The session object to be reused, or null if none exists.
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
   * Returns the datasource instance which has the given named id.
   * By default, checks the current user's access to the datasource.
   * @param string $datasource
   *    The named id of the datasource
   * @param bool $checkAccess 
   *    Optional. Whether to check the current user's access to the datasource
   *    Defaults to true
   * @return \app\models\Datasource
   * @throws UserErrorException
   */
  public function datasource($datasource, $checkAccess=true)
  {
    $myDatasources = $this->getActiveUser()->getAccessibleDatasourceNames();
    try {
      $instance = Datasource :: getInstanceFor( $datasource );
    } catch( \InvalidArgumentException $e ){
      throw new UserErrorException(
        Yii::t('app', "Datasource '{datasource}' does not exist",[
          'datasource' => $datasource
        ])
      );
    }
    if( $checkAccess and ! in_array($datasource, $myDatasources) ){
      throw new UserErrorException(
        Yii::t('app', "You do not have access to datasource '{datasource}'",[
          'datasource' => $datasource
        ])
      );
    }  
    return $instance;  
  }

  /**
   * Returns the class name of the given model type of the controller as determined by the datasource
   * @param string $datasource
   * @param string $modelType
   * @return string
   * @throws UserErrorException
   */
  public function getModelClass( $datasource, $modelType )
  {
    return $this->datasource($datasource)->getClassFor( $modelType );
  }

  /**
   * Returns the class name of the main model type of the controller as determined by the datasource
   * @param string $datasource
   * @return string
   * @throws UserErrorException
   * @todo rename to getControlledModelClass
   */
  public function getControlledModel( $datasource )
  {
    return $this->getModelClass( $datasource, static :: $modelType );
  }

  /**
   * Returns a query for the record with the given id
   *
   * @param string $datasource
   * @param int $id
   * @return \yii\db\ActiveRecord
   * @throws UserErrorException
   */
  public function getRecordById($datasource, $id)
  {
    $model = $this->getControlledModel($datasource) :: findOne($id);
    if( is_null( $model) ){
      throw new \InvalidArgumentException("Model of type " . static::$modelType . " and id #$id does not exist in datasource '$datasource'.");
    }
    return $model;
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
  public function successfulActionResult($reason=null)
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

  //-------------------------------------------------------------
  // methods to pass data between service methods
  //-------------------------------------------------------------


  /**
   * Temporarily stores the supplied arguments on the server for retrieval
   * by another service method. This storage is only guaranteed to last during
   * the current session and is then discarded. The method can take a variable
   * number of arguments
   * @return string
   *    The shelf id needed to retrieve the data later
   */
  public function shelve()
  {
    $shelfId = Yii::$app->security->generateRandomString();
    Yii::$app->session->set($shelfId,func_get_args());
    //$_SESSION[$shelfId] = func_get_args();
    return $shelfId;
  }

  /**
   * Retrieve the data stored by the shelve() method.
   * @param $shelfId
   *    The id of the shelved data
   * @param bool $keepCopy
   *    If true, the data will be preserved and can be retrieved again.
   *    If false or omitted, the data will be deleted.
   * @return array
   *    Returns an array of the elements passed to the shelve() method, which can be
   *    extracted with the list() method.
   */
  public function unshelve($shelfId, $keepCopy=false )
  {
    $args =  Yii::$app->session->get($shelfId);
    //$args = $_SESSION[$shelfId];
    if ( !$keepCopy ) {
      //unset( $_SESSION[$shelfId] );
      Yii::$app->session->remove( $shelfId );
    }
    return $args;
  }

  /**
   * Returns true if something is stored und the shelf id
   * @param string $shelfId
   * @return bool
   */
  public function hasInShelf( $shelfId ){
    if( empty($shelfId) ) return false;
    return Yii::$app->session->has( $shelfId );
  }

  //-------------------------------------------------------------
  // send and broadcast messages
  // @todo reimplement and move into component
  //-------------------------------------------------------------

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
