<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */
qcl_import( "qcl_access_Controller" );

/**
 * Base class that keeps track of connected clients
 * and dispatches or broadcasts messages. A "session" means the
 * connection established by a particular browser instance.
 */
class qcl_access_SessionController
  extends qcl_access_Controller
{

  /**
   * The id of the active user, determined from the
   * session id
   */
  private $activeUserId;

  /**
   * The session model object
   * @var qcl_access_model_Session
   */
  private $sessionModel;

  /**
   * seconds of inactivity after which anonymous users or
   * sessions will be deleted
   * @var int
   */
  public $secondsUntilPurge = 3600;

  /**
   * Returns singleton instance of this class
   * @return qcl_access_SessionController
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * This overrides and extends the parent method by providing a way to determine
   * the user by a given session id in the request.
   *
   * @override
   * @throws qcl_access_AccessDeniedException
   * @return int user id
   */
  public function createUserSession()
  {
    /*
     * on-the-fly authentication
     */
    $sessionId = $this->getSessionIdFromRequest();

    /*
     * we have no valid session
     */
    if ( ! $sessionId )
    {
      $this->log("Could not get session id from request...", QCL_LOG_AUTHENTICATION );

      /*
       * if the application allows unauthenticated access,
       * try to use the PHP session id
       */
      if ( $this->getApplication()->skipAuthentication() )
      {
        $sessionId = session_id();
        $this->log("Skipping authentication, using PHP session id: #$sessionId", QCL_LOG_AUTHENTICATION );
      }
      else
      {
        throw new qcl_access_AccessDeniedException("No valid session.");
      }
    }

    /*
     * get user id from session. deny access if not valid
     */
    try
    {
      $userId = $this->getUserIdFromSession( $sessionId );
    }
    catch( qcl_access_InvalidSessionException $e )
    {
      $this->warn($e->getMessage(), QCL_LOG_AUTHENTICATION );

      /*
       * if the application allows unauthenticated access,
       * and the PHP session id is not yet linked to a user,
       * create an anonymous user for all unauthenticated
       * requests
       */
      if ( $this->getApplication()->isAnonymousAccessAllowed() )
      {
        $userId = $this->grantAnonymousAccess();
      }

      /*
       * else, deny access
       */
      else
      {
        throw new qcl_access_AccessDeniedException("Invalid session id.");
      }
    }

    /*
     * We have a valid user now.
     */
    $this->log("Got user id from session $sessionId: $userId", QCL_LOG_AUTHENTICATION );

    /*
     * Check if the user's session has timed out
     */
    if ( ! $this->checkTimeout( $userId ) )
    {
      $this->forceLogout();
      throw new qcl_access_AccessDeniedException("Session timed out.");
    }

    /*
     * We have a valid session referring to a valid user.
     * Set sessioniId and make a copy of the user object as the
     * active user and return the user id.
     */
    $this->setSessionId( $sessionId );
    $this->createUserSessionByUserId( $userId );
    return $userId;
  }

  /**
   * Creates a valid user session for the given user id, i.e. creates
   * the user object and the session. Overridden to create session record.
   * @param $userId
   * @return void
   */
  public function createUserSessionByUserId( $userId )
  {
    parent::createUserSessionByUserId( $userId );
    $this->registerSession();
  }

  /**
   * Authenticates with data in the request data, either by a given session id or
   * by a username - password combination.Supports child and sibling sessions
   * @throws JsonRpcException
   * @return string|null The session id, if it can be retrieved by the server data. Null if
   * no valid session id can be determined from the request
   * @override
   */
  public function getSessionIdFromRequest()
  {
    /*
     * if we have a session id in the request data, return it
     */
    $sessionId = parent::getSessionIdFromRequest();

    /*
     * get real session id if this is a token
     */
    if ( $this->checkToken( $sessionId ) )
    {
      $sessionId = $this->createSessionFromToken( $sessionId );
    }
    
    /*
     * do we have a valid id already, return it
     */
    if ( $sessionId )
    {
      return $sessionId;
    }
    
    /*
     * otherwise, try getting a session id from authenticating a
     * user on-the-fly
     */
    $username = qcl_server_Request::getInstance()->getServerData("username");
    $password = qcl_server_Request::getInstance()->getServerData("password");

    if ( $username and $password )
    {
      $this->log("Authenticating from server data, user '$username'", QCL_LOG_AUTHENTICATION );

      /*
       * can we authenticate with the server data?
       */
      try
      {
        $userId = $this->authenticate( $username, $password );
        $this->createUserSessionByUserId( $userId );
      }
      catch(qcl_access_AuthenticationException $e) //TODO: do we need both AuthenticationException and AccesDeniedException?
      {
        throw new JsonRpcException($e->getMessage());
      }
    }
    
    /*
     * getting it from the PHP session id
     */
    else
    {
      $this->log(sprintf(
        "Getting session id from PHP session: '%s'", $this->getSessionId()
      ), QCL_LOG_AUTHENTICATION );
    }

    /*
     * return the (new) session id
     */
    return $this->getSessionId();
  }


  
  /**
   * Logs out a user
   * @return void
   */
  public function logout()
  {

    /*
     * unregister the current session
     */
    $this->unregisterSession();
    //$this->cleanup();
    
		/*
     * mark user as offline if no more sessions exist
     */
    $this->checkOnlineStatus( $this->getActiveUser()->id() );

    /*
     * logout
     */
    parent::logout();
  }

  //-------------------------------------------------------------
  // session management
  //-------------------------------------------------------------

  /**
   * Returns the session model singleton instance
   * @return qcl_access_model_Session
   */
  public function getSessionModel()
  {
    if ( $this->sessionModel === null )
    {
       qcl_import( "qcl_access_model_Session" );
       $this->sessionModel = new qcl_access_model_Session();
    }
    return $this->sessionModel;
  }


  /**
   * Checks if a session with the given id exists
   * @param string $sessionId
   * @return bool
   */
  public function sessionExists( $sessionId )
  {
    $sessionModel = $this->getSessionModel();
    return $sessionModel->namedIdExists( $sessionId );
  }

  /**
   * Registers the current session with the current user. Cleans up stale
   * sessions
   * @return void
   */
  public function registerSession()
  {
    $sessionId = $this->getSessionId();
    $user      = $this->getActiveUser();
    $remoteIp  = qcl_server_Request::getInstance()->getIp();

    /*
     * register current session
     */
    $this->log( sprintf("Registering session '%s', for %s from IP %s ", $sessionId, $user, $remoteIp ), QCL_LOG_AUTHENTICATION );
    $this->getSessionModel()->registerSession( $sessionId, $user, $remoteIp );
  }

  /**
   * Unregisters the current session and deletes all messages
   */
  public function unregisterSession()
  {
    $sessionId = $this->getSessionId();
    $this->log("Unregistering Session $sessionId.", QCL_LOG_AUTHENTICATION );
    $this->getSessionModel()->unregisterSession( $sessionId );
  }

  /**
   * Destroys a session by its id
   * @param $sessionId
   * @return void
   */
  public function destroySession( $sessionId )
  {
    parent::destroySession( $sessionId );
    $this->getSessionModel()->unregisterSession( $sessionId );
  }

  /**
   * Terminates a session
   * @return void
   * @override
   */
  public function terminate()
  {
    //$sessionModel = $this->getSessionModel();
    $activeUser   = $this->getActiveUser();
    $sessionId    = $this->getSessionId();
    $username     = $activeUser->username();
    $this->log("Session $sessionId ($username) is terminated.", QCL_LOG_AUTHENTICATION );
    $this->logout();
  }

  /**
   * Get the active user id from the session id.
   * @param int $sessionId
   * @return int The user id
   * @throws qcl_access_InvalidSessionException
   */
  public function getUserIdFromSession( $sessionId )
  {
    if ( ! $sessionId )
    {
      throw new qcl_access_InvalidSessionException( "Missing session id.");
    }

    $sessionModel = $this->getSessionModel();
    try
    {
      $sessionModel->load( $sessionId );
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      throw new qcl_access_InvalidSessionException("Session $sessionId does not exist." );
    }

    $activeUserId = (int) $sessionModel->get( $this->getUserModel()->foreignKey() );
    if ( ! $activeUserId )
    {
      throw new qcl_access_InvalidSessionException( "Session $sessionId is not connected with a user id!");
    }

    try
    {
      $this->getUserModel()->load( $activeUserId );
      
      /*
       * mark user as online
       */
      $this->getUserModel()->set("online",true);
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      throw new qcl_access_InvalidSessionException("Session $sessionId refers to a non-existing user.");
    }
    return $activeUserId;
  }

  /**
   * Checks if any session exists that are connected to the user id. 
   * If not, set the user's online status to false
   * @param integer $userId
   * @return boolean
   * @throws qcl_data_model_RecordNotFoundException if invalid user id is given
   */
  public function checkOnlineStatus( $userId )
  {
    $userModel = $this->getUserModel()->load( $userId );
    try
    {
      $this->getSessionModel()->findLinked($userModel);
      return true;
    }
    catch(qcl_data_model_RecordNotFoundException $e)
    {
       $userModel->set("online", false)->save();
       return false;
    }
  }

  /**
   * Checks if session id is a token
   * @param $sessionId
   * @return bool
   */
  public function checkToken( $sessionId )
  {
    return ( substr($sessionId,0,2) == "P_" or  substr($sessionId,0,2) == "S_" );
  }

  /**
   * Creates a child or sibling session based on the token. Returns the session id
   * @param $token
   * @return string
   */
  public function createSessionFromToken( $token )
  {
    /*
       * Sub-session of a parent session: creates a new session
       * from a parent session, for example, when opening
       * child windows hat share the user's access rights, but has to have
       * a different session to keep its data apart. The child windows session will be
       * deleted when the parent's session ends.
       */
    if( substr($token,0,2) == "P_" )
    {
      $sessionId = $this->createChildSession( substr($token,2) );
    }

    /*
     * Creates a new session from a session, for example, when opening
     * a new windows that share the user's access rights, but has to have
     * a different session to keep its data apart. These session will continue
     * to exist when the other session ends.
     * @return string
     */
    if( substr($token,0,2) == "S_" )
    {
      $sessionId = $this->createSiblingSession( substr($token,2) );
    }

    return $sessionId;
  }

  /**
   * Creates a token which will be replaced with a child session id
   * @return string
   */
  public function createChildSessionToken()
  {
    return "P_" . $this->getSessionId();
  }

  /**
   * Returns a new session id that depends on a parent session and
   * will be deleted when the parent session is deleted.
   * @param string|null $parentSessionId If null, the current session id is used.
   */
  public function createChildSession( $parentSessionId )
  {
    if ( ! $parentSessionId )
    {
      throw new InvalidArgumentException("No parent session id.");
    }

    /*
     * get user id from parent session
     */
    $ip = qcl_server_Server::getInstance()->getServerInstance()->getRequest()->getIp();
    $sessionModel = $this->getSessionModel();
    try
    {
      $sessionModel->load( $parentSessionId );
      if( $sessionModel->getIp() !== $ip )
      {
        throw new qcl_access_AccessDeniedException("Invalid IP");
      }      
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      throw new qcl_access_InvalidSessionException("Parent session $parentSessionId not found...");
    }
    $userId = $sessionModel->get( $this->getUserModel()->foreignKey() );

    /*
     * create random new session id and pass it to the client
     */
    $sessionId = $this->createSessionId();

    $this->log( sprintf(
      "Spawning child Session %s form parent Session %s",
      $sessionId,$parentSessionId
    ), QCL_LOG_AUTHENTICATION );

    /*
     * register new session
     * FIXME this is a manual hack, use API for this
     */
    $sessionModel->create($sessionId, array(
      'UserId'          => $userId,
      'ip'              => qcl_server_Server::getInstance()->getServerInstance()->getRequest()->getIp(),
      'parentSessionId' => $parentSessionId
    ));
    
    $this->setSessionId($sessionId);

    return $sessionId;
  }



  /**
   * Creates a token which will be replaced with a sibling session id
   * @return string
   */
  public function createSiblingSessionToken()
  {
    return "S_" . $this->getSessionId();
  }

  /**
   * Returns a new session of the user that owns the given session id.
   * @param string $sessionId
   * @throws qcl_access_InvalidSessionException
   * @throws qcl_access_AccessDeniedException
   * @return int session id
   */
  public function createSiblingSession( $siblingSessionId=null )
  {
    if ( ! $siblingSessionId )
    {
      throw new InvalidArgumentException("No sibling session id.");
    }

    /*
     * get user id from sibling session
     */
    $ip = qcl_server_Server::getInstance()->getServerInstance()->getRequest()->getIp();
    $sessionModel = $this->getSessionModel();
    try
    {
      $sessionModel->load( $siblingSessionId );
      if( $sessionModel->getIp() !== $ip )
      {
        throw new qcl_access_AccessDeniedException("Invalid IP");
      }
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      throw new qcl_access_InvalidSessionException("Sibling session $siblingSessionId not found...");
    }
      
    $userId = $sessionModel->get( $this->getUserModel()->foreignKey() );

    /*
     * create random new session id and pass it to the client
     */
    $sessionId = $this->createSessionId();

    $this->log( sprintf(
      "Spawning sibling Session %s from Session %s",
      $sessionId,$siblingSessionId
    ), QCL_LOG_AUTHENTICATION );

    /*
     * register new session
     * FIXME this is a manual hack, use API for this
     */
    $sessionModel->create($sessionId, array(
      'UserId'          => $userId,
      'ip'              => $ip
    ));
    
    $this->setSessionId($sessionId);
    
    return $sessionId;
  }

  /**
   * Manually cleanup access data, which are a mess, unfortunately
   * @throws LogicException
   */
  public function cleanup()
  {
    $this->log("Cleaning up stale data ....", QCL_LOG_AUTHENTICATION );

    $sessionModel = $this->getSessionModel();
    $userModel = $this->getUserModel();

    $sessionModel->findAll();
    while ( $sessionModel->loadNext() )
    {
      $sessionId=  $sessionModel->id();

      /*
       * get user that owns the session
       */
      try
      {
        $userModel->findLinked( $sessionModel );
        $userModel->loadNext();
      }
      catch(qcl_data_model_RecordNotFoundException $e)
      {
        /*
         * purge sessions without an associated user
         */
        $this->log("Session $sessionId has no associated user - discarded.", QCL_LOG_AUTHENTICATION  );
        $sessionModel->delete();
        continue;
      }

      /*
       * purge sessions that have expanded their lifetime
       */
      $modified = $sessionModel->getModified();
      $now      = $sessionModel->getQueryBehavior()->getAdapter()->getTime();
      $ageInSeconds = strtotime($now)-strtotime($modified);

      if( $userModel->isAnonymous() )
      {
        //$this->debug("Anonymous session $sessionId is $ageInSeconds seconds old, timeout is " . QCL_ACCESS_ANONYMOUS_SESSION_LIFETIME);
        if ( $ageInSeconds > QCL_ACCESS_ANONYMOUS_SESSION_LIFETIME )
        {
          $this->log("Anonymous Session $sessionId unmodified since $ageInSeconds seconds - discarded.", QCL_LOG_AUTHENTICATION  );
          $sessionModel->delete();
        }
      }
      else
      {
        // todo: how to deal with expired user sessions
        //$this->debug(" session $sessionId is $age seconds old, timeout is " . QCL_ACCESS_TIMEOUT);
//        if ( $age > QCL_ACCESS_TIMEOUT )
//        {
//          $userId =$userModel->id();
//          $this->log("Session $sessionId of user $userId unmodified since $age seconds - discarded.", QCL_LOG_AUTHENTICATION  );
//          $sessionModel->delete();
//        }
      }
    }

    /*
     * Checking for anonymous users without a session
     */
    $userModel->findWhere(array("anonymous"=>true));
    while($userModel->loadNext())
    {
      try
      {
        $sessionModel->findLinked($userModel); // will throw when no session is found
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        $userId= $userModel->id();
        $this->log("Anonymous user #$userId has no sessions - discarded.", QCL_LOG_AUTHENTICATION  );
        $userModel->delete();
      }
    }

    /*
     * Checking for sessions without a user
     */
    $sessionModel->findAll();
    while($sessionModel->loadNext())
    {
      try
      {
        $userModel->findLinked($sessionModel); // will throw when no session
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        $sessionId= $sessionModel->id();
        $this->log("Session $sessionId has no associated user - discarded.", QCL_LOG_AUTHENTICATION  );
        $sessionModel->delete();
      }
    }


    /*
     * cleanup messages
     */
    $this->getMessageBus()->cleanup();
  }

}
?>