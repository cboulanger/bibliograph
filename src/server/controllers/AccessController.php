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

use \JsonRpc2\extensions\AuthException;

use app\controllers\AppController;

use app\models\User;
use app\models\Role;
use app\models\Permission;
use app\models\Group;
use app\models\Session;

/**
 * The class used for authentication of users. Adds LDAP authentication
 */
class AccessController extends AppController
{
  use traits\ShimTrait;
  use traits\RbacTrait;
  use traits\AuthTrait;

  //-------------------------------------------------------------
  // Actions / JSONRPC API
  //-------------------------------------------------------------  
  
  /**
   * Registers a new user.
   *
   * @param string $username
   * @param string $password
   * @param array $data
   *    Optional user data
   * @return \app\models\User
   *    The newly created user model instance
   * @throws \InvalidArgumentException if user does not exist
   */
  public function actionRegister($username, $password, $data = array())
  {
    $data = [
    'namedId'   => $username,
    'password'  => $this->generateHash( $password ),
    'name'      => $data['name'] || $username
    ];
    $user = new User($data);
    $user->save();
    return $user;
  }

  /**
   * Identifies the current user, either by a token, a username/password, or as a
   * anonymous guest.
   *
   * @param string|null $first
   *    Either a token (then the second param must be null), a username (then the seconde
   *    param must be the password, or null, then the user logs in anonymously
   * @param string|null $password
   * @return \app\controllers\dto\AuthResult
   */
  public function actionAuthenticate($first = null, $password = null)
  {    
    $user = null;
    $session = null;
    Yii::$app->session->open();

    if (empty($first) and empty($password)) {
      // see if we have a session id that we can link to a user
      $session = Session::findOne( [ 'namedId' => $this->getSessionId() ] );
      if( $session ){
        Yii::trace('PHP session exists in database...');
        // find a user that belongs to this session
        $user = User::findOne( [ 'id' => $session->UserId ] );
        if ( $user ) {          
          Yii::trace('Session belongs to user ' . $user->namedId);
        } else {
          // shouldn't ever happen
          Yii::warning('Session has non-existing user!');
          $session->delete();
        }
      } 
      if ( ! $user ) {
        // login anonymously
        $user = $this->createAnonymous();
        Yii::info("Created anonymous user '{$user->namedId}'.");  
      }
    } 
    elseif (is_string($first) and empty($password)) {
      // login using token
      $user = User::findIdentityByAccessToken($first);
      if (is_null($user)) {
        throw new AuthException( "Invalid token", AuthException::INVALID_AUTH);
      }
      Yii::info("Authenticated user '{$user->namedId}' via auth token.");
    } 
    else {
      // login using username/password

      // @todo: update to yii functions:
      // $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
      // if (Yii::$app->getSecurity()->validatePassword($password, $hash)) {
      //     // all good, logging user in
      // } else {
      //     // wrong password
      // }

      try {
        $user = $this->user($first);
      } catch (\InvalidArgumentException $e) {
        Yii::warning("Invalid user '$first' tried to authenticate.");
        throw new AuthException( $this->tr("Invalid username or password."), AuthException::INVALID_AUTH);
      }
  
      $auth_method = Yii::$app->utils->getPreference("authentication.method");
      $authenticated = false;
      $storedPw = $user->password;
  
      switch ($auth_method) {
        case "hashed":
          Yii::trace("Client sent hashed password: $password.");
          $randSalt   = $this->getLoginSalt();
          $serverHash = substr( $storedPw, ACCESS_SALT_LENGTH );
          $authenticated = $password == sha1( $randSalt . $serverHash );
          break;
        case "plaintext":
          Yii::trace("Client sent plaintext password." );
          $authenticated = $this->generateHash( $password, $storedPw ) == $storedPw;
          break;
        default:
          throw new InvalidArgumentException("Unknown authentication method $auth_method");
      }
      Yii::info("Authenticated user '{$user->namedId}' via auth username/password.");
    }

    // inactive users cannot log in
    if ( ! $user->active) {
      throw new AuthException( $this->tr("User is deactivated."), AuthException::INVALID_AUTH);
    }    

    // @todo  create dialog that asks user to fill out their user information
    // if ( strlen($password) == 7 and ! $this->ldapAuth )
    // {
    //   new ui_dialog_Alert(
    //   $this->tr("You need to set a new password."),
    //   "bibliograph.actool", "editElement", array( "user", $first )
    //   );
    // }

    // log in identified user
    $user->online = true;
    $user->save(); 
    Yii::$app->user->login($user);
    if( ! $session ) {
      // if we don't already have a (PHP) session, try to find a saved one
      $session = $this->continueUserSession($user);
    }
    if ( $session ) {
      // let's continue this one
      $sessionId = $session->id;
      $session->touch();
      Yii::info("Continued sesssion {$sessionId}"); 
      session_id( $session->namedId );
    } else {
      // we didn't find one, so let's start a new one
      $sessionId = $this->getSessionId();
      $session = new Session(['namedId' => $sessionId]);
      $session->link('user',$user);
      $session->save();
      $sessionId = $this->getSessionId();
      Yii::trace("Started sesssion {$sessionId}");
    }
       
    // if necessary, add a token
    if (! $user->token) {
      $user->save();
    }

    // return information on user
    return [
      'message' => Yii::t('app', "Welcome, {0}!", [$user->name]),
      'token' => $user->token,
      'sessionId' => Yii::$app->session->getId()
    ];
  }

  /**
   * Logs out the current user and destroys all session data
   *
   * @return void
   */
  public function actionLogout()
  {
    $user = $this->getActiveUser(); 
    Yii::info("Logging out user '{$user->name}'.");
    $user->online = false;
    $user->save();
    Session::deleteAll(['UserId' => $user->id ]);
    Yii::$app->session->destroy();
    return "OK";
  }

  /**
   * Returns the username of the current user. Mainly for testing purposes.
   * @return string
   */
  public function actionUsername()
  {
    $activeUser = $this->getActiveUser();
    if (is_null($activeUser)) {
      throw new AuthException("'username' action should not be accessible without an active user.",
      AuthException::MISSING_AUTH);
    }
    Yii::info("The current user is " . $activeUser->username);
    return $activeUser->username;
  }


  /**
   * Returns the times this action has been called. Only for testing session storage.
   */
  public function actionCount()
  {
    $session = Yii::$app->session;
    $count = $session->get("counter");
    $count = $count ? $count + 1 : 1;
    $session->set( "counter", $count );
    return $count;
  }

   /**
   * Manually cleanup access data
   * @throws LogicException
   */
  public function cleanup()
  {
    Yii::info( "Cleaning up stale session data ...." );

    // cleanup sessions
    foreach( Session::findAll() as $session ){
      $user = $session->getUser()->one();
      if ( ! $user or ! $user->online ){
        $session->delete();
      }
      // @todo remove expired sessions
      // @todo add expire column
      // $modified = $sessionModel->getModified();
      // $now      = $sessionModel->getQueryBehavior()->getAdapter()->getTime();
      // $ageInSeconds = strtotime($now)-strtotime($modified);      
    }

    // cleanup users
    foreach( User::findAll() as $user){
      if( $user->getSessions()->count() === 0){
        // if no sessions, ...
        if ($user->anonymous ) {
          // .. delete user if guest
          $user->delete();
        } else {
          // ... set real users to offline 
          $user->online = false;
          $user->save(); 
        }
      } 
    }
  }

  /**
   * Returns number of seconds since resetLastAction() has been called
   * for the current user
   * @return int seconds
   */
  public function getSecondsSinceLastAction()
  {
    not_implemented();
    $now = new data_db_Timestamp();
    $lastAction = $this->get("lastAction");
    if ($lastAction) {
      $d = $now->diff($lastAction);
      return (int) ($d->s + (60 * $d->i) + (3600 * $d->h) + 3600 * 24 * $d->d);
    }
    return 0;
  }
}
