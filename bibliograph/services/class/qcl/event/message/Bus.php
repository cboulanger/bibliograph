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

qcl_import("qcl_core_Object");

/**
 * Message Bus
 */
class qcl_event_message_Bus
  extends qcl_core_Object
{

	/**
	 * Array of handlers called before a message is broadcasted to
	 * other clients
	 * 
	 * @var array
	 */
	private $onBeforeBroadcast = array();
	
  /**
   * The message database
   * @var array
   */
  private $messages = array(
    'filters'  => array(),
    'data'     => array()
  );

  /**
   * Returns a singleton instance of this class
   * @return qcl_event_message_Bus
   */
  static function getInstance( )
  {
    return qcl_getInstance( __CLASS__ );
  }


  /**
   * Returns the message data model.
   * @return qcl_event_message_db_Message
   */
  public function getModel()
  {
    qcl_import("qcl_event_message_db_Message");
    return qcl_event_message_db_Message::getInstance();
  }

  /**
   * Adds a message subscriber. This works only for objects which have been
   * initialized during runtime. Filtering not yet supported, i.e. message name must
   * match the one that has been used when subscribing the message, i.e. no wildcards!
   *
   *
   * @param string $filter
   * @param qcl_core_Object $subscriber
   * @param string $method Callback method of the subscriber
   * @throws InvalidArgumentException
   */
  public function addSubscriber( $filter, $subscriber, $method )
  {
    if ( ! $filter or ! $method or ! is_a( $subscriber, "qcl_core_Object" ) )
    {
      throw new InvalidArgumentException("Invalid parameter.");
    }

    $message_db = $this->messages;

    /*
     * object id
     */
    $subscriberId = $subscriber->objectId();

    /*
     * search if we already have an entry for the filter
     */
    $index = array_search( $filter, $message_db['filters'] );
    if ( $index === false )
    {
      /*
       * filter not found, create new filter and data
       */
      $message_db['filters'][] = $filter;
      $index = count($message_db['filters']) -1;
      $message_db['data'][$index] = array(
        array( $subscriberId, $method )
      );
    }
    else
    {
      /*
       * filter found, add data
       */
      $message_db['data'][$index][] = array( $subscriberId, $method );
    }
  }
  
  /**
   * Registers a callback which is called before a message is
   * broadcasted to another client. This can be used to filter 
   * the messages that are broadcasted. The callback is an array
   * with the object and the name of the method as elements.
   * The callback receives the message object and the session
   * object that the message is about to be dispatched to.
   * @param array $callback
   * @return void
   * @see qcl_event_message_Bus::filterMessage
   */
  public function registerOnBeforeBroadcastCallback( $callback )
  {
  	qcl_assert_array( $callback );
  	qcl_assert_object( $callback[0] );
  	qcl_assert_valid_string( $callback[1] );
		$this->onBeforeBroadcast[] = $callback;
  }
  
  /**
	 * Example filters callback for the registerOnBeforeBroadcastCallback()
	 * method.
	 * @param qcl_event_message_ClientMessage $message
	 * 		The message to dispatch
	 * @param qcl_access_model_Session $sessionModel
	 * 		The loaded model of the session that is to receive the message
	 * @param qcl_access_model_User $userModel
	 * 		The loaded model of the user that is to receive the message
	 * @return boolean True if message should be broadcast, false if not.
	 */
	public function filterMessage( 
		qcl_event_message_ClientMessage $message, 
		qcl_access_model_Session $sessionModel, 
		qcl_access_model_User $userModel )
	{
		return true;
	}

  /**
   * Dispatches a message. Filtering not yet supported, i.e. message name must
   * match the one that has been used when subscribing the message, i.e. no wildcards!
   *
   * @param qcl_event_message_Message $message Message
   * @internal param mixed $data Data dispatched with message
   * @return bool Success
   */
  public function dispatch ( qcl_event_message_Message $message )
  {
    /*
     * message data
     */
    $name = $message->getName();

    /*
     * models
     */
    static $accessController 	= null;
    static $sessionModel 			= null;
    static $userModel 				= null;
    if( $accessController === null )
    {
			$accessController = $this->getApplication()->getAccessController();
	    $sessionModel 		= $accessController->getSessionModel();
	    $userModel				= $accessController->getUserModel();
    }
    
    $sessionId = $accessController->getSessionId();    
    
    /*
     * search message database
     */
    $message_db = $this->messages;
    $index = array_search ( $name, $message_db['filters'] );

    /*
     * call registered subscriber methods
     */
    if ( $index !== false )
    {
      foreach ( $message_db['data'][$index] as $subscriberData )
      {
        list( $subscriberId, $method ) = $subscriberData;
        $subscriber = $this->getObjectById( $subscriberId );
        $subscriber->$method( $message );
      }
      return true; // todo: Should this really return here?
    }

    /*
     * Is message server-side only? Then we're done.
     */
    if ( ! ($message instanceof qcl_event_message_ClientMessage ))
    {
      return true;
    }

    /*
     * No, broadcast message to connected clients
     */
    $msgModel = $this->getModel();
    $cancelDispatch = false;

    /*
     * if message is a broadcast, get the ids of all sessions and store
     * a message for each session
     */
    if ( $message->isBroadcast() )
    {
      $sessionModel->findAll();
      while( $sessionModel->loadNext() )
      {
        /*
         * check if user of this session exists, otherwise
         * delete the session
         */
        try
        {
          $userModel->load( $sessionModel->get("UserId") );
        }
        catch ( qcl_data_model_RecordNotFoundException $e)
        {
          $this->log( "Deleting session with non-existing user: " . $sessionModel->namedId(), QCL_LOG_MESSAGE );
          $sessionModel->delete();
          continue;
        }

        /*
         * do not dispatch if the message should not be returned to
         * the client itself
         */
        if( $message->isExcludeOwnSession() and $sessionModel->namedId() == $sessionId )
        {
          continue;
        }

        /*
         * do not dispatch the message when one of the registered
         * callbacks returns false
         */

        foreach( $this->onBeforeBroadcast as $callback )
        {
          $callbackObject = $callback[0];
          $callbackMethod = $callback[1];
          if( $callbackObject->$callbackMethod( $message, $sessionModel, $userModel ) === false )
          {
            $cancelDispatch = true;
          }
        }
        if ( $cancelDispatch )
        {
          continue;
        }

        /*
         * create a message entry in the database
         */
        $data = $message->getData();
        $msgModel->create( array(
          'name'      => $name,
          'data'      => addSlashes( serialize( $data ) )
        ) );
        $msgModel->linkModel( $sessionModel );
      }

      return $cancelDispatch ? false : true;
    }

    /*
     * else, store a message for only the connected session
     */
    $userModel = $this->getApplication()->getAccessController()->getActiveUser();

    foreach( $this->onBeforeBroadcast as $callback )
    {
      $callbackObject = $callback[0];
      $callbackMethod = $callback[1];
      if( $callbackObject->$callbackMethod( $message, $sessionModel, $userModel ) === false )
      {
        return false;
      }
    }

    try
    {
      $sessionModel->load( $sessionId );
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      $this->warn(__METHOD__ . ": Invalid session id $sessionId");
      return false;
    }

    $msgModel->create( array(
      'name'      => $name,
      'data'      => addSlashes( serialize( $message->getData() ) )
    ) );
    $msgModel->linkModel( $sessionModel );
    return true;
  }

  /**
   * Shorthand method for dispatching a server-only message.
   * @param qcl_core_Object $sender
   * @param string $name
   * @param mixed $data
   * @return void
   */
  public function dispatchMessage( $sender, $name, $data )
  {
    qcl_import( "qcl_event_message_Message" );
    $message = new qcl_event_message_Message( $name, $data );
    if ( $sender)
    {
      $message->setSender( $sender );
    }
    $this->log("Sending message '$name' locally.", QCL_LOG_MESSAGE );
    $this->dispatch( $message );
  }

  /**
   * Shorthand method for dispatching a message that will be forwarded
   * to the client.
   * @param qcl_core_Object $sender
   * @param string $name
   * @param mixed $data
   * @return void
   */
  public function dispatchClientMessage( $sender, $name, $data )
  {
    qcl_import( "qcl_event_message_ClientMessage" );
    $message = new qcl_event_message_ClientMessage( $name, $data );
    if ( $sender)
    {
      $message->setSender( $sender );
    }
    $this->log("Sending message '$name' to requesting client.", QCL_LOG_MESSAGE );
    $this->dispatch( $message );
  }

  /**
   * Broadcasts a message to all connected clients.
   * @param qcl_core_Object $sender
   * @param bool|mixed $name
   * @param mixed $data
   *    Data dispatched with message
   * @param bool $excludeOwnSession
   *    Whether the current session should be excluded from the broadcast (Default: false).
   * @return void
   */
  public function broadcastClientMessage ( $sender, $name, $data, $excludeOwnSession=false )
  {
    qcl_import( "qcl_event_message_ClientMessage" );
    $message = new qcl_event_message_ClientMessage( $name, $data );
    $message->setBroadcast( true );
    $message->setExcludeOwnSession( $excludeOwnSession );
    if ( $sender)
    {
      $message->setSender( $sender );
    }
    $this->log("Broadcasting message '$name' to all connected clients.", QCL_LOG_MESSAGE );
    $this->dispatch( $message );
  }

  /**
   * Returns broadcasted messages for the client with the given session
   * id.
   * @param int $sessionId
   * @return array
   */
  public function getClientMessages( $sessionId )
  {
    $app = $this->getApplication();
    $msgModel = $this->getModel();
    $sessionModel = $app->getAccessController()->getSessionModel();

    try
    {
      $sessionModel->load( $sessionId );
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      $this->warn( __METHOD__ . ": Invalid session id $sessionId");
      return array();
    }

    /*
     * find messages that have been stored for session id
     */
    try
    {
      $msgModel->findLinked( $sessionModel );
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
    	return array();
    }

    /*
     * get name and data and delete message
     */
    $messages = array();
    while ( $msgModel->loadNext() )
    {
      $messages[] = array(
        'name'  => $msgModel->get( "name" ),
        'data'  => unserialize( stripslashes( $msgModel->get("data") ) )
      );    
      $msgModel->delete();
    }

    /*
     * return message array
     */
    return $messages;
  }

  /**
   * Manually cleans up unretrieved and stale messages
   */
  public function cleanup()
  {
    $this->log("Cleaning up unretrieved messages ....", QCL_LOG_MESSAGE );

    $messageModel = $this->getModel();
    $sessionModel = $this->getApplication()->getAccessController()->getSessionModel();

    $messageModel->findAll();
    while ( $messageModel->loadNext() )
    {
      $messageId = $messageModel->id();
      try
      {
        $sessionModel->findLinked($messageModel); // this will throw if no associated session exists

        // check age
        $modified = $messageModel->getModified();
        $now      = $messageModel->getQueryBehavior()->getAdapter()->getTime(); // todo: add methods age() and modificationAge()
        $ageInSeconds = strtotime($now)-strtotime($modified); // todo: use Date object and diff
        if ( $ageInSeconds > QCL_EVENT_MESSAGE_LIFETIME )
        {
          $this->log("Message #$messageId has exceeded its lifetime - discarded...", QCL_LOG_MESSAGE );
          $messageModel->delete();
        }
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        $this->log("Message #$messageId has no associated session - discarded...", QCL_LOG_MESSAGE );
        $messageModel->delete();
      }
    }
  }
}