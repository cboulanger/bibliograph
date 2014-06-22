<?php
/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2009 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */


/**
 * Abstract class for jsonrpc data stores that handles event propagation.
 * This is a very simple implementation not meant for production. We simply
 * store the event data in the $_SESSION variable, separated by the
 * class name of the extending class, so that events of differnt widget
 * types do not get mixed up.
 */
class AbstractStore
{

  function test_register( $params )
  {
    list( $storeId ) = $params;

    if ( ! isset( $_SESSION[get_class($this)]['storeIds'] ) )
    {
      $_SESSION[get_class($this)]['storeIds'] = array();
    }
    if ( ! in_array( $storeId, $_SESSION[get_class($this)]['storeIds'] ) )
    {
      /*
       * register the store and create an event queue
       * In a real application, this would be saved in the database
       */
      $_SESSION[get_class($this)]['storeIds'][] = $storeId;
      $_SESSION[get_class($this)]['events'][$storeId] = array();
    }

    return array(
      'statusText' => "Store registered."
      );
  }

  function test_unregister( $params )
  {
    list( $storeId ) = $params;

    if ( in_array( $storeId, $_SESSION[get_class($this)]['storeIds'] ) )
    {
      /*
       * unregister the store
       */
      array_splice( $_SESSION[get_class($this)]['storeIds'], array_search( $storeId, $_SESSION['storeIds'], 1 ) );
      unset( $_SESSION[get_class($this)]['events'][$storeId] );
    }
    return array(
      'statusText' => "Store unregistered."
      );
  }


  function test_unregisterAll()
  {
    $_SESSION[get_class($this)]['storeIds'] = array();
    $_SESSION[get_class($this)]['events'] = array();
    return array(
      'statusText' => "All stores unregistered."
    );
  }

  function test_getEvents( $params )
  {

    list( $storeId, $events ) = $params;

    //echo "/* Store #$storeId: Retrieving events, Server event queue: " . print_r( $_SESSION, true ) . "*/";

    /*
     * save client events
     */
    if ( count( $events ) )
    {
      foreach( $events as $event )
      {
        $this->saveEvent( $storeId, $event );
      }
    }

    /*
     * retrieve events from queue and empty queue
     */
    if ( isset( $_SESSION[get_class($this)]['events'][$storeId] ) )
    {
      $events = $_SESSION[get_class($this)]['events'][$storeId];
      $_SESSION[get_class($this)]['events'][$storeId] = array();
    }
    else
    {
      $events = array();
    }

    return array(
      'events' => $events,
    );
  }

  function test_saveEvents( $params )
  {
    list( $storeId, $events ) = $params;
    foreach( $events as $event )
    {
      $this->saveEvent( $storeId, $event );
    }
    return array();
  }

  function saveEvent( $storeId, $event )
  {

    /*
     * for each connected store except the requesting one,
     * save an event in the event queue
     */
    foreach( $_SESSION[get_class($this)]['storeIds'] as $id )
    {
      if ( $id != $storeId )
      {
        $_SESSION[get_class($this)]['events'][$id][] = $event;
      }
    }

    //echo "/* Store #$storeId: Saving event " . print_r( $event, true) . "\nServer event queue: " . print_r( $_SESSION, true ) . "*/";

  }

}

?>