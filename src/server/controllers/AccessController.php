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
use app\models\Datasource;
use app\models\Group;
use app\models\Role;
use app\models\Session;
use app\models\User;
use InvalidArgumentException;
use lib\exceptions\Exception;
use lib\exceptions\UserErrorException;
use lib\models\ClipboardContent;
use Yii;
use yii\db\Expression;
use yii\web\UnauthorizedHttpException;

/**
 * The class used for authentication of users.
 * @todo move implementations into AccessManager class and put only action stubs here
 */
class AccessController extends AppController
{

  const CATEGORY = "access";

  const MESSAGE_FORCE_LOGOUT = "forceLogout";

  /**
   * @inheritDoc
   *
   * @var array
   */
  protected $noAuthActions = ["authenticate", "ldap-support","logout","challenge"];

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
        Yii::debug("Challenge: User '$username' needs to be authenticated by LDAP.", self::CATEGORY);
        $auth_method = "plaintext";
      }
    } else {
      // if the user is not in the database (for example, first LDAP auth), use plaintext authentication
      $auth_method = "plaintext";
      Yii::debug("Challenge: User '$username' is not in the database, maybe first LDAP authentication.", __METHOD__);
    }
    Yii::debug("Challenge: Using authentication method '$auth_method'", self::CATEGORY);
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
     * 1) no username / password: session authentication or guest login
     */
    if (empty($first) and empty($password)) {
      // see if we have a session id that we can link to a user
      $user = User::findIdentityBySessionId($this->getSessionId(), Yii::$app->request);
      if ( ! $user ) {
        // login anonymously
        $user = $this->createAnonymous();
        Yii::info("Created anonymous user '{$user->namedId}'.", self::CATEGORY);
      }
    }

    /*
     * 2) token authentication
     */
    elseif (is_string($first) and empty($password)) {
      // login using token
      $user = User::findIdentityByAccessToken($first);
      if (!$user) {
        $this->dispatchClientMessage(self::MESSAGE_FORCE_LOGOUT);
        throw new UserErrorException( "Invalid token");
      }
      Yii::info("Authenticated user '{$user->namedId}' via auth token.", self::CATEGORY);
    }

    /*
     * 3) username / password authentication
     */
    else {
      try {
        $user = $this->authenticateWithPassword($first, $password);
      } catch (UnauthorizedHttpException $e) {
        return new AuthResult([
          "error" => $e->getMessage()
        ]);
      }
    }

    // log in new authenticate user
    $user->online = 1;
    $user->save();
    Yii::$app->user->login($user);

    // create a token
    if (!$user->token) {
      $user->token = null;
    }
    $user->save();

    // cleanup old sessions
    $this->cleanup();
    $this->dispatchClientMessage("qcl.token.change", $user->token); // Hm, do we need this?

    // return information on user
    return new AuthResult([
      'message' => Yii::t('app', "Welcome, {0}!", [$user->name]),
      'token' => $user->token,
      'sessionId' => Yii::$app->session->getId()
    ]);
  }

  /**
   * Authenticate with password and username
   * @param string $username
   * @param string $password
   * @return User|null
   * @throws ModelNotFoundException
   * @throws UnauthorizedHttpException
   * @throws \yii\db\Exception
   */
  protected function authenticateWithPassword(string $username, string $password) {
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
      $user = $this->user($username);
    } catch (InvalidArgumentException $e) {
      if( $useLdap ){
        // this could be a LDAP user
        try {
          $user = Yii::$app->ldapAuth->authenticate($username, $password);
        } catch (ModelNotFoundException $e) {
          Yii::warning($e->getMessage());
        }
      }
      if( ! $user ) {
        Yii::warning("Invalid user '$username' tried to authenticate.");
        throw new UnauthorizedHttpException(Yii::t('app', "Invalid username or password"));
      }
      $authenticated = true;
    }
    // inactive users cannot log in
    if ( ! $user->active ) {
      throw new UnauthorizedHttpException(Yii::t('app', "User is deactivated"));
    }
    if ( ! $authenticated ) {
      // if the user has been authenticated via ldap before
      if( $useLdap and $user->ldap ){
        if ( Yii::$app->ldapAuth->authenticate($username,$password) ){
          $authenticated = true;
        }
        // authentication failed
      } else {
        // check password from database
        $auth_method = Yii::$app->config->getPreference("authentication.method");
        $storedPw = $user->password;
        switch ($auth_method) {
          case "hashed":
            Yii::debug("Client sent hashed password: $password.", self::CATEGORY);
            $randSalt   = Yii::$app->accessManager->getLoginSalt();
            $serverHash = substr( $storedPw, ACCESS_SALT_LENGTH );
            $authenticated = $password == sha1( $randSalt . $serverHash );
            break;
          case "plaintext":
            Yii::debug("Client sent plaintext password." , self::CATEGORY);
            $authenticated = Yii::$app->accessManager->generateHash( $password, $storedPw ) == $storedPw;
            break;
          default:
            throw new InvalidArgumentException("Unknown authentication method $auth_method");
        }
      }
      // password is wrong
      if ( $authenticated === false ){
        Yii::info("User supplied wrong password.", self::CATEGORY);
        throw new UnauthorizedHttpException(Yii::t('app', "Invalid username or password"));
      }
    }
    Yii::info("Authenticated user '{$user->namedId}' via auth username/password.", self::CATEGORY);
    return $user;
  }

  /**
   * Logs out the current user and destroys all session data
   */
  public function actionLogout()
  {
    $user = $this->getActiveUser();
    if (!$user) {
      return "No user is logged in.";
    }
    $this->logout($user);
    $this->dispatchClientMessage("qcl.token.change", null);
    return "User '{$user->name}' logged out";
  }

  /**
   * Log out the given user
   * @param User $user
   */
  public function logout(User $user) {
    Yii::info("Logging out user '{$user->name}'.", self::CATEGORY);
    // set the user offline
    $user->online = 0;
    // delete the token,
    $user->token = null;
    try {
      $user->save();
    } catch (\yii\db\Exception $e) {
      Yii::warning($e->getMessage());
    }
    // delete all session, this shoudl force logout users but probably won't
    Session::deleteAll(['UserId' => $user->id ]);

    Yii::$app->user->logout();
    Yii::$app->session->destroy();


    // cleanup old sessions
    try {
      $this->cleanup();
    } catch (\Throwable $e) {
      Yii::warning($e->getMessage());
    }
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
    Yii::info("The current user is " . $activeUser->username, self::CATEGORY);
    return $activeUser->username;
  }

  /**
   * Returns the data of the current user, including the current permissions.
   * @param string|null $datasource
   *    If string, return global permissions plus permissions given via the datasource
   *    If no argument or null, return global permissions only
   */
  public function actionUserdata($datasource=null)
  {
    $activeUser = $this->getActiveUser();
    $data = $activeUser->getAttributes(['namedId','name','anonymous','ldap']);
    $data['anonymous'] = (bool) $data['anonymous'];
    if ($datasource){
      // transform string name to datasource model instance and check access
      $datasource = $this->datasource($datasource,true);
    }
    $data['permissions'] = $activeUser->getAllPermissionNames(null,$datasource);
    return $data;
  }

  /**
   * Updates the current user's permissions based on the given datasource. This
   * will grant all the permissions given to the role which the user has in the
   * group that both the user and the datasource belong to, or grant the permissions
   * linked to the default role of user databases.
   * @param string $datasource
   * return array
   */
  public function actionUpdatePermissions(string $datasource){
    $user = $this->getActiveUser();
    /** @var Datasource $datasource */
    $datasource = $this->datasource($datasource);
    // global permissions
    $permissions = $user->getPermissionNames();
    // add default role if datasource is linked to user
    if ($datasource->getUsers()->where(['id' => $user->id])->exists()){
      $roles = Yii::$app->config->getPreference('app.access.userdatabase.roles');
      foreach ($roles as $namedId) {
        /** @var Role $role */
        $role = Role::findByNamedId($namedId);
        $permissions = array_merge(
          $permissions,
          $role->getPermissionNames()
        );
      }
      return array_values(array_unique($permissions));
    }
    // add permissions of the groups that are linked both to the user and the datasource
    $datasourceGroups = $datasource->getGroups()->where(['active'=>1])->all();
    $userGroupNames =  $user->getGroupNames();
    /** @var Group $group */
    foreach( $datasourceGroups as $group){
      if( ! in_array($group->namedId, $userGroupNames)) continue;
      //Yii::info("Datasource {$datasource->namedId} is linked to group {$group->namedId}" , self::CATEGORY);
      $permissions = array_merge(
        $permissions,
        $user->getPermissionNames($group)
      );
    }
    return array_values(array_unique($permissions));
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
    Yii::info( "Cleaning up stale session data ....", self::CATEGORY );

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

    // cleanup clipboard
    foreach (ClipboardContent::find()->all() as $item) {
      if( ! User::findOne($item->UserId) ) $item->delete();
    }
  }

  /**
   * Returns information on users which are/recently have been online
   * @throws Exception
   */
  public function actionUsersOnline()
  {
    $this->requirePermission("access.manage");
    return User::find()
      ->select( 'namedId')
      ->where( ['online' => 1])
      ->andWhere(['anonymous'=> 0])
      ->asArray()
      ->all();
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
