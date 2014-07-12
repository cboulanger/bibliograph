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
 
qcl_import( "qcl_access_UserController" );

/**
 * Base class that keeps track of connected clients
 * and dispatches or broadcasts messages. A "session" means the
 * connection established by a particular browser instance.
 */
class qcl_access_SessionController
  extends qcl_access_UserController
{

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
      throw new qcl_access_InvalidSessionException("No valid session id.");
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
      $this->log( $e->getMessage(), QCL_LOG_AUTHENTICATION );

      /*
       * if the application allows unauthenticated access,
       * and the PHP session id is not yet linked to a user,
       * create an anonymous user for all unauthenticated
       * requests
       */
      if ( $this->getApplication()->isAnonymousAccessAllowed() )
      {
        return $this->grantAnonymousAccess();
      }

      /*
       * else, deny access
       */
      else
      {
        throw $e;
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
      $this->forceLogout($this->tr("Session has expired." . " " . $this->tr("Please log in again.")));
    }

    /*
     * We have a valid session referring to a valid user.
     * Set sessionId and make a copy of the user object as the
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
   * @throws qcl_access_AccessDeniedException
   * @return void
   */
  public function createUserSessionByUserId( $userId )
  {
    parent::createUserSessionByUserId( $userId );
    $this->registerSession();
  }

  /**
   * Authenticates with data in the request data, either by a given session id or
   * by a username - password combination.
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
     * do we have a valid id already, return it
     */
    if ( $sessionId )
    {
      return $sessionId;
    }
    
    /*
     * get session id from PHP session
     */
    $this->log(sprintf(
      "Getting session id from PHP session: '%s'", $this->getSessionId()
    ), QCL_LOG_AUTHENTICATION );
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
       $this->sessionModel = $this->getAccessDatasource()->getSessionModel();
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
   * @throws qcl_access_AccessDeniedException
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

    try
    {
      $this->getSessionModel()->registerSession( $sessionId, $user, $remoteIp );
    }
    catch ( qcl_access_InvalidSessionException $e)
    {
      $this->forceLogout( $e->getMessage() );
    }

    /*
     * let the client know
     */
    if( $sessionId != parent::getSessionIdFromRequest() )
    {
      $this->dispatchClientMessage("setSessionId", $sessionId );
    }
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
      throw new InvalidArgumentException( "Missing session id.");
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
      // mark user as online
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
   * If not, set the user's online status to false.
   * @param integer $userId
   * @return boolean Whether the user is online or not.
   * @throws LogicException if user does not exist.
   */
  public function checkOnlineStatus( $userId )
  {
    try
    {
      $userModel = $this->getUserModel()->load( $userId );
    }
    catch(qcl_data_model_RecordNotFoundException $e)
    {
      throw new LogicException( "User #$userId does not exist." );
    }

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
   * Creates a new session of the user that owns the given session id that depends on a parent session and
   * will be deleted when the parent session is deleted. Returns a token for this session
   * that be used to authenticate the user in a different window, a different device, etc.
   * @param string|null $parentSessionId If null, the current session id is used.
   * @return string Token
   * todo implement
   */
  public function createChildSession( $parentSessionId=null )
  {
    return new qcl_core_NotImplementedException(__METHOD__);
    if ( ! $parentSessionId )
    {
      $parentSessionId = $this->getSessionId();
    }

    /*
     * get user id from parent session
     */
    $sessionModel = $this->getSessionModel();
    try
    {
      $sessionModel->load( $parentSessionId );
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      throw new LogicException("Parent session $parentSessionId not found...");
    }
    $userId = $sessionModel->get( $this->getUserModel()->foreignKey() );

    /*
     * create random token
     */
    $token = md5( microtime() );

    /*
     * register new session
     */
    $sessionModel->create($token, array(
      'UserId'          => $userId,
      'parentSessionId' => $parentSessionId
    ));

    $this->log( sprintf(
      "Created child session from parent session %s with token %s",
      $parentSessionId, $token
    ), QCL_LOG_AUTHENTICATION );

    return $token;
  }

  /**
   * Creates a new session of the user that owns the given session id. Returns a token for this session
   * that be used to authenticate the user in a different window, a different device, etc.
   * @param string|null $siblingSessionId If null, the current session id is used.
   * @return string Token
   */
  public function createSiblingSession( $siblingSessionId=null )
  {
    return new qcl_core_NotImplementedException(__METHOD__);
    if ( ! $siblingSessionId )
    {
      $siblingSessionId = $this->getSessionId();
    }

    /*
     * get user id from sibling session
     */
    $sessionModel = $this->getSessionModel();
    try
    {
      $sessionModel->load( $siblingSessionId );
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      throw new LogicException("Sibling session $siblingSessionId not found...");
    }
      
    $userId = $sessionModel->get( $this->getUserModel()->foreignKey() );

    /*
     * create random token
     */
    $token = md5( microtime() );

    /*
     * register new session
     */
    $sessionModel->create($token, array(
      'UserId'          => $userId
    ));

    $this->log( sprintf(
      "Created sibling session from session %s with token %s",
      $siblingSessionId, $token
    ), QCL_LOG_AUTHENTICATION );

    return $token;
  }

  /**
   * Manually cleanup access data
   * @throws LogicException
   */
  public function cleanup()
  {
    $this->log("Cleaning up stale session data ....", QCL_LOG_AUTHENTICATION );

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
          $this->log("Anonymous Session $sessionId has expired.", QCL_LOG_AUTHENTICATION  );
          $sessionModel->delete();
        }
      }
      else
      {
//        if( $sessionModel->isToken())
//        {
//          if ( $ageInSeconds > QCL_ACCESS_TOKEN_LIFETIME )
//          {
//            $this->log("Token $sessionId has expired.", QCL_LOG_AUTHENTICATION  );
//            $sessionModel->delete();
//           }
//        }
//        else
//        {
        // todo: how to deal with expired user sessions
        //$this->debug(" session $sessionId is $age seconds old, timeout is " . QCL_ACCESS_TIMEOUT);
//        if ( $age > QCL_ACCESS_TIMEOUT )
//        {
//          $userId =$userModel->id();
//          $this->log("Session $sessionId of user $userId unmodified since $age seconds - discarded.", QCL_LOG_AUTHENTICATION  );
//          $sessionModel->delete();
//        }
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