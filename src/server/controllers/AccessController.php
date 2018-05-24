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

use Adldap\Models\ModelNotFoundException;
use app\controllers\dto\AuthResult;
use app\models\User;
use app\models\Session;
use lib\exceptions\UserErrorException;
use InvalidArgumentException;
use \JsonRpc2\Exception;
use yii\db\Expression;
use Yii;

/**
 * The class used for authentication of users.
 * @todo move implementations into AccessManager class and put only action stubs here
 */
class AccessController extends AppController
{

  const CATEGORY = "access";

  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["authenticate","ldap-support"];

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
   * @throws InvalidArgumentException
   */
//  public function actionRegister($username, $password, $data = array())
//  {
//    $data = [
//    'namedId'   => $username,
//    'password'  => Yii::$app->accessManager->generateHash( $password ),
//    'name'      => $data['name'] || $username
//    ];
//    $user = new User($data);
//    $user->save();
//    return $user;
//  }

  /**
   * Given a username, return a string consisting of a random hash and the salt 
   * used to hash the password of that user, concatenated by "|"
   */
  public function actionChallenge($username)
  {
    $auth_method = Yii::$app->config->getPreference("authentication.method");
    $user = User::findOne(['namedId'=> $username]);
    if( $user ){
      if( Yii::$app->config->getIniValue("ldap.enabled") and $user->isLdapUser() ) 
      {
        Yii::debug("Challenge: User '$username' needs to be authenticated by LDAP.");
        $auth_method = "plaintext";
      }      
    } else {
      // if the user is not in the database (for example, first LDAP auth), use plaintext authentication
      $auth_method = "plaintext";
      Yii::debug("Challenge: User '$username' is not in the database, maybe first LDAP authentication.");
    }
    Yii::debug("Challenge: Using authentication method '$auth_method'");
    switch ( $auth_method )
    {
      case "plaintext":
        return array( "method" => "plaintext" );
      case "hashed":
        return array( 
          "method" => "hashed",
          "nounce" => Yii::$app->accessManager->createNonce($username)
        );
      default:
        throw new InvalidArgumentException("Unknown authentication method $auth_method");
    }
  }

  /**
   * Action to check if LDAP authentication is supported.
   * @see \lib\components\LdapAuth::checkConnection()
   */
  public function actionLdapSupport()
  {
    return Yii::$app->ldapAuth->checkConnection();
  }


  /**
   * Exposes {@see AccessController::authenticate} as a controller action
   * @param string|null $first
   *    Either a token (then the second param must be null), a username (then the seconde
   *    param must be the password, or null, then the user logs in anonymously
   * @param string|null $password
   * @return \app\controllers\dto\AuthResult
   */
  public function actionAuthenticate( $first=null, $password = null)
  {
    try{
      return $this->authenticate($first, $password);
    } catch( \Throwable $e){
      // Do not log stack trace because it could contain a password
      Yii::error($e->getMessage());
      // Convert all errors into user errors. This might result in cryptic error messages
      throw new UserErrorException($e->getMessage());
    }
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
   * @throws \yii\db\Exception
   * @throws \Throwable
   */
  public function authenticate($first = null, $password = null)
  {
    /** @var User $user */
    $user = null;
    $session = null;
    Yii::$app->session->open();

    /*
     * no username / password
     */
    if (empty($first) and empty($password)) {
      // see if we have a session id that we can link to a user
      $session = Session::findOne( [ 'namedId' => $this->getSessionId() ] );
      if( $session ){
        Yii::debug('PHP session exists in database...');
        // find a user that belongs to this session
        $user = User::findOne( [ 'id' => $session->UserId ] );
        if ( $user ) {          
          Yii::debug('Session belongs to user ' . $user->namedId);
        } else {
          // shouldn't ever happen
          Yii::warning('Session has non-existing user!');
          try {
            $session->delete();
          } catch (\Throwable $e) {
            Yii::warning($e->getMessage());
          }
        }
      } 
      if ( ! $user ) {
        // login anonymously
        $user = $this->createAnonymous();
        Yii::info("Created anonymous user '{$user->namedId}'.");  
      }
    } 

    /*
     * token authentication
     */    
    elseif (is_string($first) and empty($password)) {
      // login using token
      $user = User::findIdentityByAccessToken($first);
      if (is_null($user)) {
        throw new UserErrorException( "Invalid token");
      }
      Yii::info("Authenticated user '{$user->namedId}' via auth token.");
    } 

    /*
     * username / password authentication
     */    
    else {

      // @todo: update to yii functions:
      // $hash = Yii::$app->getSecurity()->generatePasswordHash($password);
      // if (Yii::$app->getSecurity()->validatePassword($password, $hash)) {
      //     // all good, logging user in
      // } else {
      //     // wrong password
      // }

      // identify user
      $authenticated = false;
      $useLdap = Yii::$app->config->getIniValue("ldap.enabled");
      try {
        $user = $this->user($first);
      } catch (InvalidArgumentException $e) {
        if( $useLdap ){
          // this could be a LDAP user
          try {
            $user = Yii::$app->ldapAuth->authenticate($first, $password);
          } catch (ModelNotFoundException $e) {
            Yii::warning($e->getMessage());
          }
        }
        if( ! $user ) {
          Yii::warning("Invalid user '$first' tried to authenticate.");
          return new AuthResult([
            'error' => Yii::t('app', "Invalid username or password")
          ]);
        }
        $authenticated = true;
      }

      // inactive users cannot log in
      if ( ! $user->active ) {
        return new AuthResult([
          'error' => Yii::t('app', "User is deactivated"),
        ]);  
      } 

      if ( ! $authenticated ) {

        // if the user has been authenticated via ldap before
        if( $useLdap and $user->ldap ){
          if ( Yii::$app->ldapAuth->authenticate($first,$password) ){
            $authenticated = true;
          }
          // authentication failed
        } else {
          // check password from database
          $auth_method = Yii::$app->config->getPreference("authentication.method");
          $authenticated = false;
          $storedPw = $user->password;
          switch ($auth_method) {
            case "hashed":
              Yii::debug("Client sent hashed password: $password.");
              $randSalt   = Yii::$app->accessManager->getLoginSalt();
              $serverHash = substr( $storedPw, ACCESS_SALT_LENGTH );
              $authenticated = $password == sha1( $randSalt . $serverHash );
              break;
            case "plaintext":
              Yii::debug("Client sent plaintext password." );
              $authenticated = Yii::$app->accessManager->generateHash( $password, $storedPw ) == $storedPw;
              break;
            default:
              throw new InvalidArgumentException("Unknown authentication method $auth_method");
          }
        }

        // password is wrong
        if ( $authenticated === false ){
          Yii::info("User supplied wrong password.");
          return new AuthResult([
            'error' => Yii::t('app', "Invalid username or password"),
          ]);  
        }
      }
      Yii::info("Authenticated user '{$user->namedId}' via auth username/password.");
    }

    // user is authenticated, log in 
    $user->online = 1;
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
      Yii::debug("Started sesssion {$sessionId}");
    }

    // renew the token
    $user->token = null;
    $user->save();

    // cleanup old sessions
    $this->cleanup();

    // return information on user
    return new AuthResult([
      'message' => Yii::t('app', "Welcome, {0}!", [$user->name]),
      'token' => $user->token,
      'sessionId' => Yii::$app->session->getId()
    ]);
  }

  /**
   * Logs out the current user and destroys all session data
   */
  public function actionLogout()
  {
    $user = $this->getActiveUser(); 
    Yii::info("Logging out user '{$user->name}'.");
    $user->online = 0;
    try {
      $user->save();
    } catch (\yii\db\Exception $e) {
      Yii::warning($e->getMessage());
    }
    Session::deleteAll(['UserId' => $user->id ]);
    Yii::$app->user->logout();
    Yii::$app->session->destroy();
    // cleanup old sessions
    try {
      $this->cleanup();
    } catch (\Throwable $e) {
      Yii::warning($e->getMessage());
    }
    return "User logged out";
  }

  /**
   *
   */
  public function actionRenewPassword()
  {
    throw new \BadMethodCallException("Not implemented");
    // @todo  create dialog that asks user to fill out their user information
    // if ( strlen($password) == 7 and ! $user->ldapAuth )
    // {
    //   new ui_dialog_Alert(
    //   Yii::t('app',"You need to set a new password."),
    //   "bibliograph.actool", "editElement", array( "user", $first )
    //   );
    // }
  }

  /**
   * Returns the username of the current user. Mainly for testing purposes.
   * @return string
   * @throws Exception
   */
  public function actionUsername()
  {
    $activeUser = $this->getActiveUser();
    if (is_null($activeUser)) {
      throw new Exception('Missing authentication', Exception::INVALID_REQUEST);
    }
    Yii::info("The current user is " . $activeUser->username);
    return $activeUser->username;
  }

  /**
   * Returns the data of the current user, including permissions.
   */
  public function actionUserdata()
  {
    $activeUser = $this->getActiveUser();
    $data = $activeUser->getAttributes(['namedId','name','anonymous','ldap']);
    $data['anonymous'] = (bool) $data['anonymous'];
    $data['permissions'] = $activeUser->getAllPermissionNames();
    return $data;
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
    * @todo call this method in logout
    * @throws \Exception
    * @throws \Throwable
   */
  public function cleanup()
  {
    Yii::info( "Cleaning up stale session data ...." );

    // cleanup sessions
    foreach( Session::find()->all() as $session ){
      $user = $session->getUser()->one();
      if ( ! $user or ! $user->online ){
        $session->delete();
      }
    }
    $expiredSessions = Session::find()
      ->where(new Expression("`modified` + INTERVAL 1 HOUR < NOW()"))
      ->all();
    foreach ( $expiredSessions as $session ) $session->delete();

    // cleanup users
    /** @var User $user */
    foreach( User::find()->all() as $user){
      if( ! $user->getSessions()->exists() ){
        // if no sessions, ...
        if ($user->anonymous ) {
          // .. delete user if guest
          $user->delete();
        } else {
          // ... set real users to offline 
          $user->online = 0;
          $user->save(); 
        }
      } 
    }
  }

  //-------------------------------------------------------------
  // Helper methods
  //-------------------------------------------------------------    

  /**
   * Checks if the given username has to be authenticated from an LDAP server
   * @param string $username
   * @throws InvalidArgumentException if user does not exist
   * @return bool
   */
  public function isLdapUser($username)
  {
    return $this->user($username)->ldap;
  }
}
