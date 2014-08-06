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

qcl_import("qcl_core_Object");

define("QCL_LOG_TYPE_EVENT", "QCL_LOG_TYPE_EVENT" );

/**
 * Event Dispatcher Singleton
 */
class qcl_event_Dispatcher
  extends qcl_core_Object
{

  /**
   * database for event listeners registered on this object
   * @var array
   */
  private $events = array();

  /**
   * Events that are forwarded to the client at the end of the request
   * @var array
   */
  private $serverEvents = array();

  /**
   * Returns a singleton instance of this class
   * @return qcl_event_Dispatcher
   */
  static function getInstance( )
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * Getter for events.
   * @param string|null $objectId if provided, return event data for the
   *   given object id. Otherwise return all event data.
   * @return array Returns a reference to the array value so that
   *  its values can be manipulated directly
   */
  protected function &getEvents( $objectId=null )
  {
    if ( $objectId === null )
    {
      return $this->events;
    }
    return $this->events[$objectId];
  }

  /**
   * Getter for server events
   * @return array
   */
  public function getClientEvents()
  {
    return $this->serverEvents;
  }

 /**
   * Adds an event listener. Works only during runtime, i.e. event bindings are not
   * persisted.
   *
   * @param qcl_core_Object $target The object that the Listener is added to
   * @param string $type The name of the event
   * @param string|qcl_core_Object $listener The object or the object id retrieved by '$this->objectId()'
   * @param string $method callback method of the object
   * @todo move to external class
   * @throws LogicException
   */
  public function addListener( $target, $type, $listener, $method )
  {

    /*
     * target object id
     */
    if ( ! is_a( $target, "qcl_core_Object" ) )
    {
      throw new LogicException("Invalid target object");
    }
    $targetObjectId = $target->objectId();

    /*
     * listener object id
     */
    if ( is_a( $listener,"qcl_core_Object" ) )
    {
      $listenerObjectId = $listener->objectId();
    }
    elseif ( is_string( $listener) and ! empty( $listener ) )
    {
      $listenerObjectId = $listener;
    }
    else
    {
      throw new LogicException("Invalid listener object or object id");
    }

    /*
     * event database
     */
    $event_db =& $this->getEvents($targetObjectId);
    if ( ! $event_db )
    {
      $event_db = array(
        'types' => array(),
        'data' => array()
      );
    }

    /*
     * search if we already have an entry for the event type
     */
    $index = array_search( $type, $event_db['types'] );
    if ( $index === false )
    {
      /*
       * filter not found, create new filter and data
       */
      $event_db['types'][] = $type;
      $index = count($event_db['types']) -1;
      $event_db['data'][$index] = array(
        array( $listenerObjectId, $method )
      );
    }
    else
    {
      /*
       * filter found, add data
       */
      $event_db['data'][$index][] = array( $listenerObjectId, $method );
    }
  }

  /**
   * Dispatches an event.
   * @param qcl_core_Object $target
   * @param qcl_event_type_Event $event
   * @return bool Whether the event was dispatched or not.
   * @throws LogicException
   */
  public function dispatch ( qcl_core_Object $target, qcl_event_type_Event $event )
  {
    $event->setTarget($target);
    $targetObjectId = $target->objectId();

    /*
     * search message database
     */
    $type = $event->getType();
    $event_db =& $this->getEvents($targetObjectId);

    if ( ! is_array( $event_db ) )
    {
      //throw new LogicException("Object #$targetObjectId has no listeners.");
      return false;
    }

    $index = array_search ( $type, $event_db['types'] );
    
    /*
     * if event name was found
     */
    if ( $index !== false )
    {
      /*
       * call object methods
       */
      foreach ( $event_db['data'][$index] as $listenerData )
      {
        list( $listenerObjectId, $method ) = $listenerData;
        $listenerObject = $this->getObjectById( $listenerObjectId );
        $listenerObject->$method($event);
      }
      return true;      
    }

    /*
     * if event type is not found,try the wildcard match 
     */
    $index = 0;
    $found = false;
    foreach( $event_db['types'] as $event_type )
    {
      $pos = strpos( $event_type, "*" );
      if( substr( $type, 0, $pos ) == substr( $event_type, 0, $pos ) )
      {
        /*
         * found, call object methods
         */
        $found = true;
        foreach ( $event_db['data'][$index] as $listenerData )
        {
          list( $listenerObjectId, $method ) = $listenerData;
          $listenerObject = $this->getObjectById( $listenerObjectId );
          $listenerObject->$method($event);
        }
      }
      $index++;
    }
    
    /*
     * not found, abort
     */
    if ( ! $found ) 
    {
      throw new LogicException("Object #$targetObjectId has no listeners for event '$type'");
    }
    return true;
  }

  /**
   * Fires an event.
   * @param qcl_core_Object $target
   * @param $type
   * @internal param string $name
   * @return void
   */
  public function fireEvent( $target, $type )
  {
    qcl_import("qcl_event_type_Event");
    $event = new qcl_event_type_Event( $type );
    $this->dispatch( $target, $event );
  }

  /**
   * Fires a data event.
   * @param qcl_core_Object $target
   * @param mixed $type
   * @param mixed $data
   * @internal param string $name
   * @return void
   */
  public function fireDataEvent( $target, $type, $data )
  {
    qcl_import("qcl_event_type_DataEvent");
    $event = new qcl_event_type_DataEvent( $type, $data );
    $this->dispatch( $target, $event );
  }

  /**
   * Fires a server event which will be forwarded to the client and
   * dispatched o the jsonrpc data store that has initiated the request.
   *
   * @param qcl_core_Object $target
   * @param $type
   * @internal param string $name
   * @return unknown_type
   */
  public function fireClientEvent( $target, $type )
  {
    qcl_import("qcl_event_type_ClientEvent");
    $event = new qcl_event_type_ClientEvent( $type );
    $this->dispatch( $target, $event );
    $this->serverEvents[] = array(
      'type' => $event->getType()
    );
  }

  /**
   * Fires a server data event which will be forwarded to the client and
   * dispatched o the jsonrpc data store that has initiated the request.
   * @param qcl_core_Object $target
   * @param $type
   * @param mixed $data
   * @internal param string $name
   * @return void
   */
  public function fireClientDataEvent( $target, $type, $data )
  {
    qcl_import("qcl_event_type_ClientDataEvent");
    $event = new qcl_event_type_ClientDataEvent( $type, $data );
    $this->dispatch( $target, $event );
    $this->serverEvents[] = array(
      'type'  => $event->getType(),
      'data'  => $event->getData()
    );
  }
}
