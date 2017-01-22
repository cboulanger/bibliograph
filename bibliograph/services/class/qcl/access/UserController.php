<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import("qcl_access_AbstractController");
qcl_import("qcl_util_registry_Session");
qcl_import("qcl_ui_dialog_Alert");

/**
 * Access controller that handles authentication, access control
 * and configuration
 */
class qcl_access_UserController
  extends qcl_access_AbstractController
{

  /**
   * The id of the currently active user, determined from request or
   * from session variable.
   * @var int
   */
  private $activeUserId = null;

  /**
   * The active user object
   * @var qcl_access_model_User
   */
  private $activeUser = null;

	/**
   * Class-based access control list. 
   * Determines what role has access to what kind of model data.
   * @var array
   */
  private $modelAcl = array(

    /*
     * ruleset for all models in the "access" datasource
     */
    array(
      /*
       * this ruleset
       */
      'datasource'  => "access",
      'modelType'   => "*",

      /*
       * which roles have generally access to this model?
       * Here: all.
       */
      'roles'       => "*",

      /*
       * now we set up some rules
       */
      'rules'         => array(

        /*
         * only admin can read or change through the generic
         * functions
         */
        array(
          'roles'       => array( QCL_ROLE_ADMIN ),
          'access'      => "*",
          'properties'  => array( "allow" => "*" )
        )
      )
    )
  );

  //-------------------------------------------------------------
  // initialization
  //-------------------------------------------------------------

  /**
   * Constructor
   */
  function __construct()
  {
    $this->addModelAcl( $this->modelAcl );

    /*
     * instantiate the user model
     */
    qcl_access_model_User::getInstance();
  }
  
  //-------------------------------------------------------------
  // getters and setters
  //-------------------------------------------------------------    
  
  /**
   * Returns active user object
   * @return qcl_access_model_User
   */
  public function getActiveUser()
  {
    return $this->activeUser;
  }

  /**
   * Sets the active user.
   * @param qcl_access_model_User $userObject A user object or null to logout.
   * @throws InvalidArgumentException
   * @return void
   */
  public function setActiveUser( $userObject )
  {
    if ( $userObject === null )
    {
      $this->activeUser = null;
    }
    elseif ( $userObject instanceof qcl_access_model_User )
    {
      $activeUserClass = $userObject->className();
      $this->activeUser = new $activeUserClass;
      $this->activeUser->load( $userObject->namedId() );
    }
    else
    {
      throw new InvalidArgumentException("Invalid user object");
    }
  }  

  //-------------------------------------------------------------
  // model getters
  //-------------------------------------------------------------

  /**
   * Returns the datasource that provides access to the different
   * access models
   * @return qcl_access_DatasourceModel
   */
  public function getAccessDatasource()
  {
    static $accessDatasource = null;
    if( $accessDatasource === null )
    {
      $accessDatasource = $this->getDatasourceModel("access");
    }
    return $accessDatasource;
  }

  /**
   * Gets the user data model
   * @param string|int $id Load record if given. Deprecated.
   * @return qcl_access_model_User
   * @todo Do not pass id as argument
   * @todo Remove argument
   */
  public function getUserModel( $id=null )
  {
    $userModel = $this->getAccessDatasource()->getUserModel();
    if ( $id ) throw new InvalidArgumentException("passing id to " . __METHOD__ . " is deprecated." );
    return $userModel;
  }

  /**
   * Gets the permission data model
   * @param string|int $id Load record if given.Deprecated.
   * @return qcl_access_model_Permission
   * @todo Do not pass id as argument
   * @todo Remove argument
   */
  public function getPermissionModel( $id = null)
  {
    $permModel = $this->getAccessDatasource()->getPermissionModel();
    if ( $id ) throw new InvalidArgumentException("passing id to " . __METHOD__ . " is deprecated." );
    return $permModel;
  }

  /**
   * Gets the role data model
   * @param string|int $id Load record if given.Deprecated.
   * @return qcl_access_model_Role
   * todo Do not pass id as argument
   * todo Remove argument
   */
  public function getRoleModel( $id=null )
  {
    $roleModel = $this->getAccessDatasource()->getRoleModel();
    if ( $id ) throw new InvalidArgumentException("passing id to " . __METHOD__ . " is deprecated." );
    return $roleModel;
  }

  /**
   * Gets the group data model
   * @return qcl_access_model_Group
   */
  public function getGroupModel()
  {
    return $this->getAccessDatasource()->getGroupModel();
  }

  /**
   * Returns the configuration data model
   * @return qcl_config_ConfigModel
   */
  public function getConfigModel()
  {
    return $this->getAccessDatasource()->getConfigModel();
  }
  
  //-------------------------------------------------------------
  // access control on the session level
  //-------------------------------------------------------------

  /**
   * Whether guest access to the service classes is allowed
   * @return boolean
   */
  public function isAnonymousAccessAllowed()
  {
    return $this->getApplication()->isAnonymousAccessAllowed();
  }

  /**
   * Check the accessibility of service object and service
   * method. Aborts request when access is denied, unless when the method name is
   * "authenticate" or access is explicitly granted
   * @param qcl_core_Object $serviceObject
   * @param string $method
   * @throws LogicException
   * @throws Exception
   * @throws qcl_access_AccessDeniedException
   * @return void
   */
  public function checkAccessibility( $serviceObject, $method )
  {
    if ( ! $serviceObject instanceof qcl_server_Service )
    {
      throw new LogicException("Service object must be subclass of qcl_server_Service");
    }

    $this->log( sprintf(
      "Checking access to service object '%s'", $serviceObject->className()
    ), QCL_LOG_AUTHENTICATION );

    /*
     * Check if service waives authentication for a given method
     */
    if( $serviceObject->skipAuthentication($method) )
    {
      $this->log("No authentication neccessary...", QCL_LOG_AUTHENTICATION );
      return;
    }

    try
    {
      $this->createUserSession();
    }
    catch( qcl_access_AccessDeniedException $e)
    {
      if ( $this->isAnonymousAccessAllowed() or $method=="authenticate" )
      {
        $this->warn( $e->getMessage() );
        $this->log("No valid session, granting anonymous access", QCL_LOG_AUTHENTICATION );
        $this->grantAnonymousAccess();
      }
      else
      {
        throw $e;
      }
    }
  }
  
  
  //-------------------------------------------------------------
  // session id
  //-------------------------------------------------------------

  /**
   * Returns the current PHP session id.
   * @return string session id
   */
  public function getSessionId()
  {
    return session_id();
  }

  /**
   * Sets the PHP session id, which deletes the PHP session data.
   * @param string $sessionId
   * @throws qcl_access_InvalidSessionException
   * @return void
   */
  public function setSessionId( $sessionId )
  {
    if ( ! $this->isValidSessionId( $sessionId ) )
    {
      throw new qcl_access_InvalidSessionException("Invalid session id #$sessionId.");
    }
    $old = $this->getSessionId();
    if ( $sessionId != $old )
    {
      $this->log("Starting new session id #$sessionId",QCL_LOG_AUTHENTICATION);
      session_id( $sessionId );
      session_start();
    }
  }

  /**
   * Checks if session id is legal
   * @param $sessionId
   * @return bool
   */
  public function isValidSessionId( $sessionId )
  {
    return $sessionId and is_string( $sessionId ) and strlen( $sessionId ) == 32;
  }

  /**
   * Destroys a session by its id
   * @param $sessionId
   * @return void
   */
  public function destroySession( $sessionId )
  {
    $this->log("Destroying old Session $sessionId",QCL_LOG_AUTHENTICATION);
    session_destroy();
  }

  /**
   * Creates a new session id and sets it.
   * @return string The session id
   */
  public function createSessionId()
  {
    /*
     * create random session id
     */
    $sessionId = md5( microtime() );
    $this->log("Creating new session id ...",QCL_LOG_AUTHENTICATION);
    $this->setSessionId( $sessionId );
    return $sessionId;
  }


  //-------------------------------------------------------------
  // authentication
  //-------------------------------------------------------------


  /**
   * Get the active user id from the session id.
   * @param int $sessionId
   * @return int
   * @throws qcl_access_InvalidSessionException
   */
  public function getUserIdFromSession( $sessionId )
  {
    throw new qcl_access_InvalidSessionException("Controller does not support sessions");
  }
  
  /**
   * Gets the session id from the 'sessionId' key in the server data
   * part of the json-rpc request
   * @return string|null The session id, if it can be retrieved, otherwise null.
   * @todo move into request object
   */
  public function getSessionIdFromRequest()
  {
    $sessionId = qcl_server_Request::getInstance()->getServerData("sessionId");
    $this->log("Got session id from request: #$sessionId", QCL_LOG_AUTHENTICATION );
    return $sessionId;
  }  

  /**
   * Checks if the requesting client is an authenticated user.
   * @throws qcl_access_AccessDeniedException
   * @throws JsonRpcException
   * @return bool True if request can continue, false if it should be aborted with
   * qcl_access_AccessDeniedException.
   * @return bool userId
   */
  public function createUserSession()
  {

    /*
     * on-the-fly authentication
     */
    $sessionId = $this->getSessionIdFromRequest();

    if ( $sessionId )
    {

      /*
       * invalid session id, log out
       */
      if ( $sessionId != $this->getSessionId() )
      {
        $this->warn("Invalid session id ($sessionId). Forcing logout...");
        $this->forceLogout($this->tr("Invalid session." . " " . $this->tr("Please log in again.")));
      }

      /*
       * we have a valid session id, get the active user from the
       * session
       */
      else
      {
        $userId = qcl_util_registry_Session::get("activeUserId");
        $activeUser = $this->getUserModel();
        $activeUser->load($userId);
        $this->setActiveUser( $activeUser );

        /*
         * If we have an authenticated user, check for timeout
         */
        if ( ! $this->checkTimeout($userId) )
        {
          /*
           * force log out because of timeout
           */
          $this->forceLogout($this->tr("Session has expired." . " " . $this->tr("Please log in again.")));
        }
      }
    }
    else
    {
      throw new qcl_access_AccessDeniedException("Invalid session.");
    }

    /*
     * success!!
     */
    return $userId;
  }

  /**
   * Authenticate a user with a password. 
   * 
   * Depending on the configuration, the password is sent in plaintext (requires 
   * secure connection) or hashed with a random and a stored salt.
   * See http://openwall.info/wiki/people/solar/algorithms/challenge-response-authentication
   *
   * @param string $username or null
   * @param string $password (hashed) password
   * @return int The id of the user if authentication succeeds
   * @throws qcl_access_AuthenticationException if authentication fails
   */
  public function authenticate( $username, $password )
  {
    $userModel = $this->getUserModel();
    try
    {
      $userModel->load( $username );
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      throw new qcl_access_AuthenticationException( $this->tr("Invalid user name or password.") );
    }

    // inactive users cannot authenticate
    if(!$userModel->getActive())
    {
      throw new qcl_access_AuthenticationException( $this->tr("User is deactivated.") );
    }
    
    $auth_method = $this->getApplication()->getPreference("authentication.method");
    
    $authenticated = false;
    $storedPw = $userModel->getPassword();
    
    switch ( $auth_method )
    {
      case "hashed":
        $this->log("Client sent hashed password: $password.", QCL_LOG_AUTHENTICATION );
        $randSalt   = $this->getLoginSalt();
        $serverHash = substr( $storedPw, QCL_ACCESS_SALT_LENGTH );   

        $authenticated = $password == sha1( $randSalt . $serverHash );
        break;
        
      case "plaintext":
        $this->log("Client sent plaintext password.", QCL_LOG_AUTHENTICATION );
        $authenticated = $this->generateHash( $password, $storedPw ) == $storedPw;
        break;
        
      default:
        throw new InvalidArgumentException("Unknown authentication method $auth_method");
    }

    // success
    if ( $authenticated ) 
    {
      $userId = $userModel->getId();
      
      // inform listeners
      $this->dispatchMessage( "user.login", $userId );
      
      return $userId;
    }
    // fail
    throw new qcl_access_AuthenticationException( $this->tr("Invalid user name or password.") );
    
  }
  
  /**
   * Checks if the given username has to be authenticated from an LDAP server#
   * @param string $username
   * @throws qcl_access_AuthenticationException if user does not exist
   * @return bool 
   */
  public function isLdapUser($username)
  {
    $userModel = $this->getUserModel();
    try 
    {
      $userModel->load($username);  
    }
    catch ( qcl_data_model_RecordNotFoundException $e)
    {
      throw new qcl_access_AuthenticationException( $this->tr("User '$username' does not exist.") );
    }
    return $userModel->getLdap();
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
  public function generateHash( $plainText, $salt = null)
  {
    if ( $salt === null )
    {
      $salt = substr( md5(uniqid(rand(), true) ), 0, QCL_ACCESS_SALT_LENGTH);
    }
    else
    {
      $salt = substr($salt, 0, QCL_ACCESS_SALT_LENGTH );
    }
    return $salt . sha1( $salt . $plainText);
  }

  /**
   * Create a one-time token for authentication. It consists of a random part and the
   * salt stored with the password hashed with this salt, concatenated by "|".
   * @param string $username
   * @return string The nounce
   * @throws qcl_access_AuthenticationException
   */
  public function createNounce( $username )
  {
    $userModel = $this->getUserModel();
    try
    {
      $userModel->load( $username );
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      throw new qcl_access_AuthenticationException( $this->tr("Invalid user name or password.") );
    }
    
    $randSalt   = md5(uniqid(rand(), true) );
    $storedSalt = substr( $userModel->getPassword(), 0, QCL_ACCESS_SALT_LENGTH );
    $nounce = $randSalt . "|" . $storedSalt;
    
    // store random salt  and return nounce
    $this->setLoginSalt( $randSalt );
    return $nounce;
  }

  
  private function setLoginSalt( $salt )
  {
    $_SESSION['qcl_access_UserController_createNounce_salt'] = $salt;
  }
  
  private function getLoginSalt()
  {
    return $_SESSION['qcl_access_UserController_createNounce_salt'];
  }
  
  
  
  /**
   * Registers a new user. When exposing this method in a
   * service class, make sure to protect it adequately.
   *
   * @param string $username
   * @param string $password
   * @param array $data
   *    Optional user data
   * @return qcl_access_model_User
   *    The newly created user model instance
   */
  public function register( $username, $password, $data= array() )
  {
    qcl_assert_valid_string( $username );
    qcl_assert_valid_string( $password );

    $userModel = $this->getUserModel();
    $data['password'] = $this->generateHash( $password );
    if( ! $data['name'])
    {
      $data['name'] = $username;
    }
    $userModel->create( $username, $data );
    return $userModel;
  }  

  /**
   * Terminates and destroys the active session
   * @return void
   */
  public function terminate()
  {
    $this->logout();
    session_destroy();
  }

  /**
   * Forces a logout on client by printing the required json response and exiting.
   * @param string|null $msg If given, show an alert dialog with the message.
   * @return void
   */
  public function forceLogout($msg="")
  {
    $this->logout();
    throw new JsonRpcException( $msg . " " . $this->tr("You will be logged out. Please reload the application and start again."));
  }

  /**
   * Logs out the the active user. If the user is anonymous, delete its record
   * in the user table.
   * @return bool success
   */
  public function logout()
  {

    // check whether anyone is logged in
    $activeUser = $this->getActiveUser();

    if ( ! $activeUser )
    {
      $this->log("No need to log out, nobody is logged in.", QCL_LOG_AUTHENTICATION);
      return false;
    }

    $username  = $activeUser->username();
    $userId    = $activeUser->getId();
    $sessionId = $this->getSessionId();

    $this->log("Logging out: user '$username' user #$userId, Session $sessionId.",QCL_LOG_AUTHENTICATION );
    
    // delete user data if anonymous guest
    if ( $activeUser->isAnonymous() )
    {
      $activeUser->delete();
    }
    
    // unset active user
    $this->log("Deleting active user ...",QCL_LOG_AUTHENTICATION );
    $this->setActiveUser(null);

    // destroy php session
    $this->log("Destroying session ...",QCL_LOG_AUTHENTICATION );
    $this->destroySession( $sessionId );
    
    // inform listeners
    $this->dispatchMessage( "user.logout", $userId );

    return true;
  }

  /**
   * Grant guest access, using a new session.
   * @return int user id
   */
  public function grantAnonymousAccess()
  {

    /*
     * create a new guest user
     */
    $userModel = $this->getUserModel();
    $userId = $userModel->createAnonymous();

    /*
     * create new session id and user session for this user
     */
    $this->log ("Granting anonymous access user #$userId.",QCL_LOG_AUTHENTICATION);
    $this->createSessionId();
    $this->createUserSessionByUserId( $userId );
    return $userId;
  }

  /**
   * Creates a valid user session for the given user id, i.e. creates
   * the user object if needed. A valid session must already exist.
   * @param $userId
   * @throws qcl_access_AccessDeniedException
   * @return void
   */
  public function createUserSessionByUserId( $userId )
  {

    $sessionId = $this->getSessionId();

    /*
     * check if user is already logged in or is not the one
     * we're supposed to log in
     */
    $activeUser = $this->getActiveUser();

    if ( $activeUser )
    {
      if ( $activeUser->getId() != $userId )
      {
        $this->warn(sprintf(
          "User %s (#%s) is already logged in, although we're about to login in user with id #%s. This should normally not be the case",
          $activeUser, $activeUser->id(), $userId
        ) );
      }
      else
      {
        $this->log("User #$userId already logged in. Continuing Session $sessionId.",QCL_LOG_AUTHENTICATION);
        return;
      }
    }

    /*
     * save the current user model as
     * the new active user and reset its timestamp
     */
    $activeUser = $this->getUserModel();
    $activeUser->load( $userId );
    $this->setActiveUser( $activeUser );
    $activeUser->resetLastAction();

    /*
     * save the user id in the session
     */
    qcl_util_registry_Session::set("activeUserId", $userId );

    /*
     * log message
     */
    $this->log( "User #$userId/ (Session $sessionId) sucessfully authenticated",QCL_LOG_AUTHENTICATION);
  }

  /**
   * Checks whether a timeout has occurred for a given user
   * @param $userId
   * @internal param int $userid id of user
   * @return bool true if user can stay logged in, false if logout should be forced
   * @todo Is this still used?
   */
  public function checkTimeout( $userId )
  {
    return true; // FIXME!!
    /*
    $configModel = $this->getApplication()->getConfigModel();
    $userModel   = $this->getUserModel( $userId );
    $userName    = $userModel->username();

    /*
     * timeout
     *
    if ( $configModel->keyExists("qcl.session.timeout") )
    {
      $timeout = $configModel->getKey("qcl.session.timeout");
    }
    else
    {
      $timeout = QCL_ACCESS_TIMEOUT;
    }
    $seconds = $userModel->getSecondsSinceLastAction();
    $this->log("User #$userId, $seconds seconds since last action, timeout is $timeout seconds.",QCL_LOG_AUTHENTICATION);

    /*
     * logout if timeout has occurred
     *
    if ( $seconds > $timeout )
    {
      return false;
    }

    /*
     * reset the timestamp
     *
    $userModel->resetLastAction();
    return true;
    */
  }

  /**
   * Purges all anonymous users. This will interfere with the sessions of these users,
   * therefore use this only during maintenance
   */
  public function purgeAnonymous()
  {
    $userModel = $this->getUserModel();
    $userModel->findAll();
    while( $userModel->loadNext() )
    {
      if( $userModel->isAnonymous() )
      {
        $userModel->delete();
      }
    }
  }

  //-------------------------------------------------------------
  // events and messages
  //-------------------------------------------------------------

  /**
   * Fires a server event which will be transported to the client
   * and dispatched by the jsonrpc data store.
   * @param string $type Message Event type
   */
  public function fireClientEvent ( $type )
  {
    $this->getEventDispatcher()->fireClientEvent( $this, $type );
  }

  /**
   * Fires a server data event which will be transported to the client
   * and dispatched by the jsonrpc data store.
   * @param $type
   * @param mixed $data Data dispatched with event
   * @internal param mixed $event Message Event type
   */
  public function fireClientDataEvent ( $type, $data )
  {
    $this->getEventDispatcher()->fireClientDataEvent( $this, $type, $data );
  }
}