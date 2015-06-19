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

qcl_import( "qcl_data_controller_Controller" );
qcl_import( "qcl_access_IAccessService" );
qcl_import( "qcl_access_AuthenticationResult" );

/**
 * Service providing methods for authentication and authorization
 *
 */
class qcl_access_Service
  extends qcl_data_controller_Controller
//  implements qcl_access_IAccessService
{

  /**
   * Flag to indicate whether a new user was created during
   * the authentication process
   * @var string
   */
  protected $newUser = null;


  /**
   * Flag to indicate that user was authenticated from LDAP
   * @var bool
   */
  protected $ldapAuth = false;

  /**
   * Actively authenticate the user with session id or with username and password.
   * Returns data for the authentication store. This will also try to authenticate
   * with a remote LDAP store if this is enabled in the application.ini.php file.
   *
   * @param string|null $first
   *    If two arguments, this is the username. If one argument,
   *    this is the session id.
   * @param string $password
   *    Plaintext Password
   * @throws JsonRpcException
   * @return qcl_access_AuthenticationResult
   */
  public function method_authenticate( $first=null, $password=null )
  {
    $app = $this->getApplication();
    $accessController = $app->getAccessController();
    $activeUserId = $this->getActiveUser()->id();

    /*
     * check if authentication must be done through https
     * although technically, it is already to late since the
     * user has sent the password already.
     */
    if ( $first and $password and $app->getIniValue("access.enforce_https_login") === true )
    {
      /*
       * check if this is a https connection
       */
      if ( $_SERVER['HTTPS'] != "on" )
      {
        throw new JsonRpcException(
          $this->tr("Authentication must be done through a secure connection, using the https:// protocol.")
         );
      }
    }

    /*
     * authentication with session id
     */
    if ( is_null( $first )  or is_null( $password ) )
    {
      $sessionId = either( $first, $this->getSessionId() );

      $this->log("Authenticating from existing session '$sessionId'...", QCL_LOG_AUTHENTICATION);
      try
      {
        $userId = $accessController->getUserIdFromSession( $sessionId );
      }
      catch( qcl_access_InvalidSessionException $e )
      {
        $this->log("Invalid session ...", QCL_LOG_AUTHENTICATION);
        if ( $accessController->isAnonymousAccessAllowed() )
        {
          $userId = $accessController->grantAnonymousAccess();
        }
        else
        {
          throw new JsonRpcException("Access denied.");
        }
      }
    }

    /*
     * username-password-authentication
     */
    else
    {
      $username = $first;

      /*
       * is ldap authentication enabled? If yes, try to authenticate
       * using LDAP. if this fails, try to authenticate locally
       */
      if( $app->getIniValue("ldap.enabled") )
      {
        try
        {
          $userId = $this->authenticateByLdap( $username, $password );
          $this->ldapAuth = true;
        }
        catch( qcl_access_AuthenticationException $e)
        {
          $this->log("LDAP authentication failed, trying to authenticate locally ...", QCL_LOG_AUTHENTICATION);
          $userId = $accessController->authenticate( $username, $password );
        }
      }

      /*
       * otherwise authenticate from local database
       */
      else
      {
        $this->log("Authenticating locally from username/password ...", QCL_LOG_AUTHENTICATION);
        $userId = $accessController->authenticate( $username, $password );
      }

      $this->log("Authenticated user: #$userId", QCL_LOG_AUTHENTICATION);

      /*
       * authentication successful, logout the accessing user to log in the
       * new one.
       */
      if ( $activeUserId and $userId != $activeUserId )
      {
        $accessController->logout();
        $accessController->createSessionId();
      }
    }

    /*
     * create (new) valid user session
     */
    $accessController->createUserSessionByUserId( $userId );

    /*
     * Save the IP of the user in the session to allow to check for 
     * session hijacking within PHP code that does not have access
     * to the QCL session management
     */
    $_SESSION['qcl_remote_ip'] = $_SERVER["REMOTE_ADDR"];
    
    /*
     * response data
     */
    $response = new qcl_access_AuthenticationResult();

    /*
     * permissions
     */
    $activeUser = $accessController->getActiveUser();
    $permissions = $activeUser->permissions();
    $response->set( "permissions", $permissions );

    /*
     * session id
     */
    $sessionId  = $this->getSessionId();
    $response->set( "sessionId",$sessionId);

    /*
     * user data
     */
    $response->set( "userId", (int) $activeUser->getId() );
    $response->set( "anonymous", $activeUser->isAnonymous() );
    $response->set( "username", $activeUser->username() );
    $response->set( "fullname", $activeUser->get("name") );

    /*
     * user data is editable only if the user is not anonymous
     * and not an ldap-authenticated user.
     */
    $response->set( "editable", ( ! $activeUser->isAnonymous()  and ! $activeUser->getLdap() ) );

    /*
     * no error
     */
    $response->set( "error", false );

    /*
     * return data to client
     */
    return $response;
  }

  /**
   * Authenticate using a remote LDAP server.
   * @param $username
   * @param $password
   * @return int User Id
   */
  protected function authenticateByLdap( $username, $password )
  {
    $this->log("Authenticating against LDAP server...", QCL_LOG_LDAP);

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
    qcl_import( "qcl_access_LdapServer" );
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
   * Service method to log out the active user. Automatically creates guest
   * access. Override this method if this is not what you want.
   * @return qcl_data_Result
   */
  public function method_logout()
  {
    $accessController = $this->getApplication()->getAccessController();

    /**
     * log out only if the current session id and the requesting session id match
     */
    $requestingSessionId = qcl_server_Request::getInstance()->getServerData("sessionId") ;
    if ( $requestingSessionId and $this->getSessionId() != $requestingSessionId )
    {
      $this->log("Session that requested logout already terminated, no need to log out.",QCL_LOG_AUTHENTICATION);
    }
    else
    {
      $accessController->logout();
      $accessController->grantAnonymousAccess();
    }

    /*
     * return authentication data
     */
    return $this->method_authenticate(null);
  }

  /**
   * Service method to terminate a session (remove session and user data).
   * Useful for example when browser window is closed.
   * @return null
   */
  public function method_terminate()
  {
    $this->getApplication()->getAccessController()->terminate();
    return null;
  }

  /**
   * Creates a SSHA hash from a password
   * 
   * @param string $password
   * @return string hash	
   */
  public function makeSshaPassword($password)
  {
    mt_srand((double)microtime()*1000000);

    $salt = mhash_keygen_s2k( MHASH_SHA1, $password, substr( pack('h*', md5(mt_rand())), 0, 8), 4);

    $hash = "{SSHA}".base64_encode(mhash(MHASH_SHA1, $password.$salt).$salt);
    return $hash;
  }

  /**
   * Validates a SSHA hash
   * @param string $password
   * @param string $hash
   * @return boolean
   */
  public function validateSshaPassword($password, $hash)
  {
    $hash = base64_decode(substr($hash, 6));
    $original_hash = substr($hash, 0, 20);
    $salt = substr($hash, 20);
    $new_hash = mhash(MHASH_SHA1, $password . $salt);
    return (strcmp($original_hash, $new_hash) == 0);
  }

  
  function ssha_encode($text)
  {
    mt_srand((double)microtime()*1000000);
    $salt = pack("CCCCCCCC", mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand());
    $sshaPassword = "{SSHA}" . base64_encode( pack("H*", sha1($text . $salt)) . $salt);
    return $sshaPassword;
  }

  function ssha_check($text,$hash)
  {
    $ohash = base64_decode(substr($hash,6));
    $osalt = substr($ohash,20);
    $ohash = substr($ohash,0,20);
    $nhash = pack("H*",sha1($text.$osalt));
    return $ohash == $nhash;
  }

}

