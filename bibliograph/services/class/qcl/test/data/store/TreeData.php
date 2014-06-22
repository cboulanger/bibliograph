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
require "qcl/test/AbstractStore.php";

class class_TreeData extends AbstractStore
{
  
  function test_getNodeCount( $params )
  {
    $_SESSION['nodeCount'] = rand(100,2000);
    $_SESSION['counter'] = 0;
    return array(
      'nodeCount'  => $_SESSION['nodeCount'],
      'statusText' => "Loading {$_SESSION['nodeCount']} nodes."
    );
  }
  
  /**
   * get node data
   * @param array $params
   */
  function test_getNodeData( $params )
  {
    /*
     * parameters
     */
    if ( count($params ) == 3 )
    {
      list( $storeId, $queue, $max ) = $params;
    }
    else
    {
      trigger_error("Wrong parameter count!");
    }
    
    /*
     * prune all connected trees if it is the first
     */
    if ( $queue[0] == 0 )
    $this->saveEvent( $storeId, array(
      'eventType' => "change",
      'type'      => "remove",
      'start'     => 0,
      'end'       => 0
    ) );
    
    
    /*
     * create node array
     */
    $nodeArr = array();
    $counter= 0;
    $firstFolder = true;
    
    
    /*
     * while we haven't reached the maximum of parent nodes to return
     * and there is still a parent id in the queue, get the parent's
     * children and add them to the node list.
     */
    while ( $counter++ <= $max and 
            is_numeric( $parentId = array_shift( $queue ) ) )
    {
      
      /*
       * abort when maximum number of nodes is reached
       */
      if ( $_SESSION['counter'] > $_SESSION['nodeCount'] )
      {
        return array(
          'result' => array(
            'nodes'      => array(),   
            'queue'      => array(),
            'statusText' => "Loaded {$_SESSION['nodeCount']} nodes."
          )
        );
      }
      
      $childCount     = rand(1,10);
      
      for( $i=0; $i < $childCount; $i++ )
      {
        /*
         * create node data with at least one folder that has children
         */
        $nodeId         = ++$_SESSION['counter'];
        
        /*
         * set start and end indexes
         */
        if( ! isset( $start ) )
        {
          $start = $nodeId;
        }
        $end = $nodeId;

        $isBranch       = $firstFolder ? true : (bool) rand(0,1); 
        $hasChildren    = true; //$firstFolder ? true : ( $isBranch ? (bool) rand(0,1) : false );
        $firstFolder    = false;
        
        $label          = $isBranch ? "Branch $nodeId" : "Leaf $nodeId";
        $recordCount    = $isBranch ? rand(0,100) : "";
    
        $node = array(
          'type'            => $isBranch ? 2 : 1,
          'label'           => $label,
          'bOpened'         => ! $hasChildren,
          'icon'            => null, // default
          'iconSelected'    => null, // default
          'bHideOpenClose'  => ! $hasChildren,
        
          /*
           * the data.id and data.parentId properties
           * define the node structure that exists on
           * the server. we cannot guarantee what node id
           * this node will have on the client.
           */
          'data'            => array (
                                'id'        => $nodeId,
                                'parentId'  => $parentId
                               ),
          'columnData'   => array( null, $recordCount )
        );
  
        /*
         * add to parent id queue if node has children
         */
        if ( $hasChildren )
        {
          array_push( $queue, $nodeId );
        }
        
        /*
         * add node to node array
         */
        array_push( $nodeArr, $node );
      }
    }
    
    $statusText = "Loaded {$_SESSION['counter']} of {$_SESSION['nodeCount']} nodes.";
    
    /*
     * dispatch an event to all connected stores
     */
    $this->saveEvent( $storeId, array(
      'eventType' => "change",
      'type'      => "add",
      'start'     => $start,
      'end'       => $end,
      'items'     => $nodeArr
    ) );
        
    /*
     * return data to client
     */
    return array(
      'nodeData'   => $nodeArr,   
      'queue'      => $queue,
      'statusText' => $statusText
    );
  }
}

?>