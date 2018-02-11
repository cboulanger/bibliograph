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

use \JsonRpc2\Exception;

use app\controllers\AppController;
use app\controllers\dto\AuthResult;

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
 
  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["authenticate"];


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
   * Given a username, return a string consisting of a random hash and the salt 
   * used to hash the password of that user, concatenated by "|"
   */
  public function actionChallenge($username)
  {
    $auth_method = Yii::$app->config->getPreference("authentication.method");
    $user = User::findOne(['namedId'=> $username]);
    if( $user ){
      if( Yii::$app->utils->getIniValue("ldap.enabled") and $user->isLdapUser() ) 
      {
        Yii:trace("Challenge: User '$username' needs to be authenticated by LDAP.");
        $auth_method = "plaintext";
      }      
    } else {
      // if the user is not in the database (for example, first LDAP auth), use plaintext authentication
      $auth_method = "plaintext";
      Yii::trace("Challenge: User '$username' is not in the database, maybe first LDAP authentication.");
    }

    Yii::trace("Challenge: Using authentication method '$auth_method'");
    
    switch ( $auth_method )
    {
      case "plaintext":
        return array( "method" => "plaintext" );
        
      case "hashed":
        return array( 
          "method" => "hashed",
          "nounce" => $acl->createNounce($username)
        );
      
      default:
        throw new InvalidArgumentException("Unknown authentication method $auth_method");
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
   */
  public function actionAuthenticate($first = null, $password = null)
  {    
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

    /*
     * token authentication
     */    
    elseif (is_string($first) and empty($password)) {
      // login using token
      $user = User::findIdentityByAccessToken($first);
      if (is_null($user)) {
        throw new AuthException( "Invalid token", AuthException::INVALID_AUTH);
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
      try {
        $user = $this->user($first);
      } catch (\InvalidArgumentException $e) {
        Yii::warning("Invalid user '$first' tried to authenticate.");
        return new AuthResult([
          'error' => Yii::t('app', "Invalid username or password")
        ]);
      }

      // inactive users cannot log in
      if ( ! $user->active) {
        return new AuthResult([
          'error' => Yii::t('app', "User is deactivated"),
        ]);  
      }        
  
      // check password
      $auth_method = Yii::$app->config->getPreference("authentication.method");
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
      
      // password is wrong
      if ( $authenticated === false ){
        Yii::info("Wrong password.");
        return new AuthResult([
          'error' => Yii::t('app', "Invalid username or password"),
        ]);  
      }

      Yii::info("Authenticated user '{$user->namedId}' via auth username/password.");
    }

    /*
     * user is authenticated
     */

    // log in identified user
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
      Yii::trace("Started sesssion {$sessionId}");
    }
       
    // if necessary, add a token
    if (! $user->token) {
      $user->save();
    }

    // return information on user
    return new AuthResult([
      'message' => Yii::t('app', "Welcome, {0}!", [$user->name]),
      'token' => $user->token,
      'sessionId' => Yii::$app->session->getId()
    ]);
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

  public function renewPassword()
  {
    // @todo  create dialog that asks user to fill out their user information
    // if ( strlen($password) == 7 and ! $this->ldapAuth )
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
   */
  public function actionUsername()
  {
    $activeUser = $this->getActiveUser();
    if (is_null($activeUser)) {
      throw new Exception('Missing authentication', AuthException::INVALID_REQUEST);
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
    $data['permissions'] = array_values($activeUser->getPermissionNames());
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
   * Authenticate using a remote LDAP server.
   * @param $username
   * @param $password
   * @return int User Id
   * @throws qcl_access_AuthenticationException if Authentication fails
   */
  protected function authenticateByLdap( $username, $password )
  {
    $app = $this->getApplication();
    $host = $app->getIniValue( "ldap.host" );
    $port = (int) $app->getIniValue( "ldap.port" );
    $user_base_dn = $app->getIniValue( "ldap.user_base_dn" );
    $user_id_attr = $app->getIniValue( "ldap.user_id_attr" );

    qcl_assert_valid_string( $host );
    qcl_assert_valid_string( $user_base_dn, "Invalid config value ldap.user_base_dn " );
    qcl_assert_valid_string( $user_id_attr, "Invalid config value ldap.user_id_attr " );

    /*
     * create new LDAP server object
     */
    
    $ldap = new qcl_access_LdapServer( $host, $port );

    /*
     * authenticate against ldap server
     */
    $userdn = "$user_id_attr=$username,$user_base_dn";
    $this->log("Authenticating $userdn against $host:$port.", QCL_LOG_LDAP);
    $ldap->authenticate( $userdn, $password );

    /*
     * if LDAP authentication succeeds, assume we have a valid
     * user. if this user does not exist, create it with "user" role
     * and assign it to the groups specified by the ldap source
     */
    $userModel = $app->getAccessController()->getUserModel();
    try
    {
      $userModel->load( $username );
      $userId = $userModel->id();
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      $userId = $this->createUserFromLdap( $ldap, $username );
      $this->newUser = $username;
    }

    /*
     * update group membership
     */
    $this->updateGroupMembershipFromLdap( $ldap, $username );
    return $userId;
  }

  /**
   * Creates a new user from an authenticated LDAP connection.
   * Receives as parameter a qcl_access_LdapServer object that
   * has already been successfully bound, and the username. The
   * default behavior is to use the attributes "cn", "sn","givenName"
   * to determine the user's full name and the "mail" attribute to
   * determine the user's email address.
   * Returns the newly created local user id.
   *
   * @param qcl_access_LdapServer $ldap
   * @param string $username
   * @return int User id
   * @throws qcl_access_LdapException in case no information can be
   * retrieved.
   */
  protected function createUserFromLdap( qcl_access_LdapServer $ldap, $username )
  {
    $app = $this->getApplication();
    $userModel = $app->getAccessController()->getUserModel();

    $user_base_dn = $app->getIniValue("ldap.user_base_dn");
    $user_id_attr = $app->getIniValue( "ldap.user_id_attr" );
    $mail_domain  = $app->getIniValue( "ldap.mail_domain" );

    $attributes = array( "cn", "sn","givenName","mail" );
    $filter = "($user_id_attr=$username)";

    $this->log("Retrieving user data from LDAP base dn '$user_base_dn' with filter '$filter'", QCL_LOG_LDAP);
    $ldap->search( $user_base_dn, $filter, $attributes);
    if ( $ldap->countEntries() == 0 )
    {
      throw new qcl_access_LdapException("Failed to retrieve user information from LDAP.");
    }
    $entries = $ldap->getEntries();

    /*
     * Full name of user
     */
    if( isset( $entries[0]['cn'][0] ) )
    {
      $name = $entries[0]['cn'][0];
    }
    elseif ( isset( $entries[0]['sn'][0] ) and isset( $entries[0]['givenName'][0] ) )
    {
      $name = $entries[0]['givenName'][0] . " " . $entries[0]['sn'][0];
    }
    elseif ( isset( $entries[0]['sn'][0] ) )
    {
      $name = $entries[0]['sn'][0];
    }
    else
    {
      $name = $username;
    }

    /*
     * Email address
     */
    if ( isset( $entries[0]['mail'][0] ) )
    {
      $email = $entries[0]['mail'][0];
      if ( $mail_domain )
      {
        $email .= "@" . $mail_domain;
      }
    }
    else
    {
      $email = "";
    }

    /*
     * create new user without any role
     */
    $userModel->create( $username, array(
      'name'      => $name,
      'email'     => $email,
      'ldap'      => true,
      'confirmed' => true // an LDAP user needs no confirmation
    ) );

    return $userModel->id();
  }

  /**
   * Updates the group memberships of the user from the ldap database
   * @param $ldap
   * @param $username
   * @return void
   */
  protected function updateGroupMembershipFromLdap( qcl_access_LdapServer $ldap, $username )
  {
    $app = $this->getApplication();
    if ( $app->getIniValue("ldap.use_groups") === false )
    {
      // don't use groups
      return;
    }

    $group_base_dn   = $app->getIniValue( "ldap.group_base_dn" );
    $member_id_attr  = $app->getIniValue( "ldap.member_id_attr" );
    $group_name_attr = $app->getIniValue( "ldap.group_name_attr" );

    qcl_assert_valid_string( $group_base_dn,   "Invalid config value ldap.group_base_dn " );
    qcl_assert_valid_string( $member_id_attr,  "Invalid config value ldap.member_id_attr " );
    qcl_assert_valid_string( $group_name_attr, "Invalid config value ldap.group_name_attr " );

    $attributes = array( "cn", $group_name_attr );
    $filter = "($member_id_attr=$username)";

    $this->log("Retrieving group data from LDAP base dn '$group_base_dn' with filter '$filter'", QCL_LOG_LDAP );
    $ldap->search( $group_base_dn, $filter, $attributes);
    if ( $ldap->countEntries() == 0 )
    {
      $this->log("User $username belongs to no groups", QCL_LOG_LDAP );
    }

    /*
     * load user model
     */
    $userModel = $app->getAccessController()->getUserModel();
    $userModel->load( $username );

    /*
     * parse entries and update groups if neccessary
     */
    $entries = $ldap->getEntries();
    $count = $entries['count'];
    $groupModel= $app->getAccessController()->getGroupModel();
    $groups = new ArrayList( $userModel->groups() );

    for( $i=0; $i<$count; $i++ )
    {
      $namedId = $entries[$i]['cn'][0];
      try
      {
        $groupModel->load( $namedId );
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        $name    = $entries[$i][$group_name_attr][0];
        $this->log("Creating group '$namedId' ('$name') from LDAP", QCL_LOG_LDAP );
        $groupModel->create( $namedId, array(
          'name'  => $name,
          'ldap'  => true
        ) );
      }

      /*
       * make user a group member
       */
      if ( ! $userModel->islinkedModel( $groupModel ) )
      {
        $this->log("Adding user '$username' to group '$namedId'", QCL_LOG_LDAP );
        $groupModel->linkModel( $userModel );
      }

      /*
       * if group provides a default role
       */
      $defaultRole = $groupModel->getDefaultRole();
      if ( $defaultRole )
      {
        $roleModel = $this->getApplication()->getAccessController()->getRoleModel();
        $roleModel->load( $defaultRole );
        if( ! $userModel->islinkedModel( $roleModel, $groupModel ) )
        {
          $this->log("Granting user '$username' the default role '$defaultRole' in group '$namedId'", QCL_LOG_LDAP );
          $userModel->linkModel( $roleModel, $groupModel );
        }
      }

      /*
       * tick off (remove) group name from the list
       */
      $groups->remove( $groups->indexOf( $namedId ) );
    }

    /*
     * remove all remaining user from all groups that are not listed in LDAP
     */
    foreach( $groups->toArray() as $groupToRemove )
    {
      $groupModel->load( $groupToRemove );
      if ( $groupModel->getLdap() === true )
      {
        $this->log("Removing user '$username' from group '$groupToRemove'", QCL_LOG_LDAP );
        $groupModel->unlinkModel( $userModel );
      }
    }

    $this->log( "User '$username' is member of the following groups: " . implode(",", $userModel->groups(true) ), QCL_LOG_LDAP );
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

  //-------------------------------------------------------------
  // Helper methods
  //-------------------------------------------------------------    

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
      throw new Exception( Yii::t('app',"Invalid user name or password.") );
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
