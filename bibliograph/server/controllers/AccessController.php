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

use lib\dialog\Alert;
use lib\dialog\Prompt;
use lib\dialog\Confirm;

/**
 * The class used for authentication of users. Adds LDAP authentication
 */
class AccessController extends AppController
{

  //-------------------------------------------------------------
  // Internal API
  //-------------------------------------------------------------  

  /**
   * Creates a new anonymous guest user
   * @throws LogicException
   * @return int \app\models\User
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
   * Checks if the given username has to be authenticated from an LDAP server#
   * @param string $username
   * @throws \InvalidArgumentException if user does not exist
   * @return bool
   */
  public function isLdapUser($username)
  {
    return $this->user($username)->ldap;
  }

  /**
   * Calling this method with a single argument (the plain text password)
   * will cause a random string to be generated and used for the salt.
   * The resulting string consists of the salt followed by the SHA-1 hash
   * - this is to be stored away in your database. When you're checking a
   * user's login, the situation is slightly different in that you already
   * know the salt you'd like to use. The string stored in your database
   * can be passed to generateHash() as the second argument when generating
   * the hash of a user-supplied password for comparison.
   *
   * See http://phpsec.org/articles/2005/password-hashing.html
   * @param $plainText
   * @param $salt
   * @return string
   */
  public function generateHash($plainText, $salt = null)
  {
    if ($salt === null) {
      $salt = substr( md5(uniqid(rand(), true) ), 0, ACCESS_SALT_LENGTH);
    } else {
      $salt = substr($salt, 0, ACCESS_SALT_LENGTH );
    }
    return $salt . sha1( $salt . $plainText);
  }

  /**
   * Create a one-time token for authentication. It consists of a random part and the
   * salt stored with the password hashed with this salt, concatenated by "|".
   * @param string $username
   * @return string The nounce
   * @throws access_AuthenticationException
   * @todo replace by a (potentially safer) yii equivalent
   */
  public function createNounce($username)
  {
    try {
      $user = $this->user($username);
    } catch (\InvalidArgumentException $e) {
      throw new Exception( $this->tr("Invalid user name or password.") );
    }
  
    $randSalt   = md5(uniqid(rand(), true) );
    $storedSalt = substr( $user->password, 0, ACCESS_SALT_LENGTH );
    $nounce = $randSalt . "|" . $storedSalt;
  
    // store random salt  and return nounce
    $this->setLoginSalt( $randSalt );
    return $nounce;
  }

  /**
   * Stores a login salt in the session
   *
   * @param string $salt
   * @return void
   */
  private function setLoginSalt($salt)
  {
    Yii::$app->session->set('LOGIN_SALT', $salt);
  }
  
  /**
   * Retrieves the login salt from the session
   *
   * @return string
   */
  private function getLoginSalt()
  {
    Yii::$app->session->get('LOGIN_SALT');
  }

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
  
    // @todo check if authentication is allowed at all
    // $configModel =  $this->getApplication()->getConfigModel();
    // if ( $password and $configModel->getKey("bibliograph.access.mode") == "readonly" )
    // {
    //   if ( ! $this->getActiveUser()->hasRole( ROLE_ADMIN ) )
    //   {
    //   $msg = _("Application is in read-only state. Only the administrator can log in." );
    //   $explanation = $configModel->getKey("bibliograph.access.no-access-message");
    //   if ( trim($explanation) )
    //   {
    //     $msg .= " " . $explanation;
    //   }
    //   throw new Exception( $msg );
    //   }
    // }

    if (empty($first) and empty($password)) {
      // login anonymously
      $user = $this->createAnonymous();
      Yii::info("Created anonymous user '{$user->namedId}'.");
    } 
    elseif (is_string($first) and empty($password)) {
      // login using token
      $user = User::findIdentityByAccessToken($first);
      if (is_null($user)) {
        throw new AuthException( "Invalid token", AuthException::INVALID_AUTH);
      }
      Yii::info("Authenticated user '{$user->namedId}' via auth auth token.");
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
    $continueSession = $this->continueUserSession($user);
    $sessionId = $this->getSessionId();
    if ( $continueSession ) {
      Yii::trace("Continued sesssion {$sessionId}"); 
    } else {
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

    // @todo cleanup messages
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




  //----------------------------------------------------------------
  // convenience methods  access control
  //----------------------------------------------------------------

  /**
   * Returns true if a permission with the given named id exists and false if
   * not.
   * @param string $namedId The named id of the permission
   * @return bool
   */
  public function hasPermission($namedId)
  {
    return $this->getAccessController()->getPermissionModel()->namedIdExists($namedId);
  }

  /**
   * Creates a permission with the given named id if it doesn't
   * already exist.
   * @param array|string $namedId The named id(s) of the permission(s)
   * @param string $description Optional description of the permission.
   *    Only used when first argument is a string.
   * @return void
   */
  public function addPermission($namedId, $description = null)
  {
    if (is_array($namedId)) {
      foreach ($namedId as $id) {
        $this->addPermission( $id );
      }
      return;
    }
    $this->getAccessController()->getPermissionModel()
    ->createIfNotExists($namedId, array( "description" => $description ));
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
    try {
      $this->getAccessController()->getPermissionModel()->load($namedId)->delete();
    } catch (data_model_RecordNotFoundException $e) {
    }
  }
  
  /**
   * Assign the given role the given permissions
   * @param string $roleId The named id of the role
   * @param string|array $permissions The named id(s) of the permissions
   * @throws LogicException
   */
  public function giveRolePermission($roleId, $permissions)
  {
    try {
      $roleModel = $this->getAccessController()->getRoleModel()->load( $roleId );
    } catch (data_model_RecordNotFoundException $e) {
      throw new LogicException("Unknown role '$roleId'");
    }
    $permissionModel = $this->getAccessController()->getPermissionModel();
    foreach ((array) $permissions as $permissionId) {
      try {
        $roleModel->linkModel( $permissionModel->load( $permissionId ) );
      } catch (data_model_RecordExistsException $e) {
      } catch (data_model_RecordNotFoundException $e) {
        throw new LogicException("Unknown permission '$permissionId'");
      }
    }
  }

  /**
   * Deletes a user
   */
  public function deleteUser()
  {
    $this->dispatchMessage("user.deleted", $this->id());
    parent::delete();
  }
}
