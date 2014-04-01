<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_controller_Controller" );

/**
 * Service class for retrieving and manipulating tree data
 */
class qcl_data_controller_TreeController
  extends qcl_data_controller_Controller
//  implements qcl_data_controller_ITreeController
{

  /**
   * The type of the model for the datasource
   */
  protected $modelType;


  /**
   * Returns the type of model managed by this controller
   * @throws LogicException
   * @return string
   */
  protected function getModelType()
  {
    if ( ! $this->modelType )
    {
      throw new LogicException("No model type set in " . $this->className() );
    }
    return $this->modelType;
  }

  /**
   * Returns the tree node model, optionally with a record loaded.
   * @param string $datasource
   * @param int $id
   * @return qcl_data_model_db_TreeNodeModel
   */
  protected function getTreeNodeModel( $datasource, $id=null )
  {
    $model = $this->getModel( $datasource, $this->getModelType() );
    if ( $id )
    {
      $model->load( $id );
    }
    return $model;
  }

  /*
  ---------------------------------------------------------------------------
     INTERFACE ITREECONTROLLER
  ---------------------------------------------------------------------------
  */

  /**
   * Creates a node.
   * @param string $datasource Name of the datasource which will contain
   *   the new record.
   * @param mixed|null $options Optional data that might be
   *   necessary to create the new record
   * @return mixed Id of the newly created record.
   * @override
   */
  function createNode( $datasource, $options=null )
  {
    $label            = $options->label;
    $position         = $options->position;
    $parentNodeId     = $options->parentId;

    /*
     * create new folder
     */
    $model = $this->getTreeNodeModel( $datasource );
    $model->create();
    $model->setParentId( $parentNodeId );
    $model->setLabel( $label );
    $model->setPosition( $position );
    $model->save();

    /*
     * reorder parent folder
     */
    $model->load( $parentNodeId );
    $model->reorder();

    /*
     * return client data
     */
    return true;
  }

  /**
   * Returns the data of child nodes of a branch ordered by the order field
   * @param string $datasource Name of the datasource
   * @param int $parentId
   * @param string|null $orderBy Optional propert name by which the returned
   *   data should be ordered
   * @return array
   */
  function getChildren ( $datasource, $parentId, $orderBy=null )
  {
    $model = $this->getTreeNodeModel( $datasource );
    $query = new qcl_data_db_Query( array(
      'where'   => array( 'parentId' => $parentId ),
      'orderBy' => $orderBy
    ) );
    return $model->getQueryBehavior()->fetch( $query );
  }

  /**
   * Returns the ids of the child node ids optionally ordered by a property
   * @param string $datasource
   *    Name of the datasource
   * @param int $parentId
   * @param string|null $orderBy
   *    Optional property name by which the returned
   *    data should be ordered.
   * @return array
   */
  function getChildIds ( $datasource, $parentId, $orderBy=null )
  {
    $model = $this->getTreeNodeModel( $datasource );
    $query = new qcl_data_db_Query( array(
      'where'   => array( 'parentId' => $parentId ),
      'orderBy' => $orderBy
    ) );
    return $model->getQueryBehavior()->fetchValues( "id", $query );
  }

  /**
   * Returns the number of children of the given node
   * @param string $datasource Name of the datasource
   * @param int $parentId If 0, get the top-level noded
   * @return int
   */
  function getChildCount ( $datasource, $parentId )
  {
    $model = $this->getTreeNodeModel( $datasource, $parentId );
    return $model->getQueryBehavior()->countWhere(array(
      'parentId' => $parentId
    ) );
  }

  /**
   * Reorders the position of the child node. If the tree data in the
   * model does not support reordering, implement as empty stub.
   * @param string $datasource Name of the datasource
   * @param int $parentId parent folder id
   * @param string|null $orderBy defaults to position column
   * @throws qcl_core_NotImplementedException
   * @return true
   */
  function reorder ( $datasource, $parentId, $orderBy="position" )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
    /*
    $model = $this->getTreeNodeModel( $datasource );
    if (! $model->hasProperty( $orderBy ) )
    {
      $this->raiseError("Invalid order field '$orderBy'");
    }
    $childIds = $this->getChildIds ( $orderBy );
    $index = 1;
    foreach ( $childIds as $id )
    {
      $data=array();
      $data["id"]     = $id;
      $data["position"]   = $index++;
      $this->set($data);
      $this->save();
    }
    return true;
    */
  }

   /**
    * Change position the absolute position of the node among
    *   the node siblings
    * @param string $datasource Name of the datasource
    * @param int $nodeId
    * @param int|string $position  New position
    * @return bool
    */
  function changePosition ( $datasource, $nodeId, $position )
  {
    /*
     * change folder position in database
     */
    $model = $this->getTreeNodeModel( $datasource, $nodeId );
    $model->changePosition( $position );
    return true;
  }

  /**
   * Change parent node
   * @param string $datasource Name of the datasource
   * @param int $nodeId Node id
   * @param int $parentId New parent node id
   * @param int $position Optional position among siblings
   * @throws LogicException
   * @return int Old parent id
   */
  function changeParent( $datasource, $nodeId, $parentId, $position=null )
  {

    if ( $nodeId == $parentId )
    {
      throw new LogicException("Node cannot be its own child!");
    }

    $model = $this->getTreeNodeModel( $datasource, $nodeId );
    //$oldParentId =  $model->getParentId();
    $model->changeParent($parentId );

    if ( ! is_null( $position )  )
    {
      $model->changePosition( $position );
    }

    /*
     * return response
     */
    return true;
  }

  /**
   * Returns the path of a node in the folder hierarchy as a
   *   string of the node labels, separated by the a given character
   * @param string $datasource Name of the datasource
   * @param int $nodeId
   * @param string $separator
   * @return string
   */
  function getLabelPath( $datasource, $nodeId, $separator="/" )
  {
    $model = $this->getTreeNodeModel( $datasource, $nodeId );
    return  $model->getLabelPath( $separator );
  }

  /**
   * Returns the path of a node in the folder hierarchy
   *   as an array of ids
   * @param string $datasource Name of the datasource
   * @param int $nodeId
   * @internal param string $separator
   * @return string
   */
  function getIdPath( $datasource, $nodeId )
  {
    $model = $this->getTreeNodeModel( $datasource, $nodeId );
    return $model->getIdPath();
  }

  /**
   * Returns the id of a node given its label path.
   * @param string $datasource Name of the datasource
   * @param string $path
   * @param string $separator Separator character, defaults to "/"
   * @return int|null The id of the node or null if node does not exist
   */
  function getIdByPath( $datasource, $path, $separator="/" )
  {
    $model = $this->getTreeNodeModel( $datasource );
    return $model->getIdByPath( $path, $separator );
  }

  /**
   * Creates nodes along the path if they don't exist.
   * @param string $datasource Name of the datasource
   * @param string $path
   * @param string $separator Separator character, defaults to "/"
   * @return int Node id
   */
  function createPath( $datasource, $path, $separator="/" )
  {
    $model = $this->getTreeNodeModel( $datasource );
    return $model->getIdByPath( $path, $separator );
  }

  /*
  ---------------------------------------------------------------------------
     INTERFACE QCL_DATA_CONTROLLER_IITEMCONTROLLER API
  ---------------------------------------------------------------------------
  */

  /**
   * Deletes a folder permanently.
   * @param string $datasource Name of the datasource that contains
   *   the record.
   * @param $nodeId
   * @param mixed|null $options Optional data that might be necessary
   *   to delete the record
   * @internal param mixed $id Id of the record within the datasource
   * @return boolean True if successful
   * @override
   */
  function delete( $datasource, $nodeId, $options=null )
  {

    $model = $this->getTreeNodeModel( $datasource, $nodeId );
    $childIds = $model->getChildIds();

    /*
     *  delete folder and unlink all records
     */
    $model->delete();
    $this->info("Deleted node #$nodeId");

    /*
     * recurse into children
     */
    if ( count($childIds) )
    {
      foreach( $childIds as $index => $childId )
      {
        $this->delete( $datasource, $childId, $options );
      }
    }
    return true;
  }

   /*
    ---------------------------------------------------------------------------
       INTERFACE ITREEVIRTUALCONTROLLER
    ---------------------------------------------------------------------------
    */

  /**
   * Return the data of a node of the tree.
   * @param string $datasource Datasource name
   * @param int $nodeId
   * @param int $parentId Optional id of parent folder
   * @param mixed|null $options Optional data
   * @throws qcl_core_NotImplementedException
   * @return array|null Returns null if node is not accessible, otherwise
   * returns a map of node properties.
   */
  function getNodeData( $datasource, $nodeId, $parentId=null, $options=null )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Returns the number of nodes in a given datasource
   * @param string $datasource
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array containing the keys 'nodeCount', 'transactionId'
   *   and (optionally) 'statusText'.
   */
  function method_getNodeCount( $datasource, $options=null )
  {
    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );

    $model = $this->getTreeNodeModel( $datasource );
    $nodeCount = $model->countRecords();
    return array(
      'nodeCount'     => $nodeCount,
      'transactionId' => $model->getTransactionId(),
      'statusText'    => ""
    );
  }

  /**
   * Returns the number of children of a node with the given id
   * in the given datasource.
   *
   * @param $datasource
   * @param $nodeId
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array
   */
  function method_getChildCount( $datasource, $nodeId, $options=null )
  {
    $childCount = $this->getChildCount( $datasource, $nodeId );
    return array(
      'childCount'   => $childCount
    );
  }

  /**
   * Returns the node data of the children of a given array of
   * parent node ids. If the "recurse" parameter is true,
   * also return the data of the whole branch. The number of
   * nodes returned can be limited by the "max" argument.
   *
   * Returns an associative array with at least the keys "nodeData" and
   * "queue". The "nodeData" value is an array of node data, each of which
   * contains information on the parent id in the data.parentId property.
   * The "queue" value is an array of ids that could not be retrieved
   * because of the "max" limitation.
   *
   * If you supply a 'storeId' parameter, the requesting tree will be
   * synchronized with all other trees that are connected to this store.
   *
   * @param string $datasource The name of the datasource
   * @param int|array $ids A node id or array of node ids
   * @param int $max The maximum number of queues to retrieve
   * @param bool $recurse Whether recurse into the tree branch
   * @param string $storeId The id of the connected datastore
   * @param string|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @throws LogicException
   * @return array
   */
  function method_getChildNodeData(
    $datasource, $ids, $max=null, $recurse=false, $storeId=null, $options=null )
  {
    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );


    $counter = 0;

    /*
     * create node array with root node
     */
    $nodeArr = array();

    /*
     * check array of nodes of which the children should be retrieved
     */
    if ( ! is_array( $ids ) )
    {
      if ( ! is_numeric( $ids ) )
      {
        throw new LogicException("Invalid argument.");
      }
    }
    $queue = (array) $ids;
    $queueLater = array();

    /*
     * retrieve the whole tree
     */
    while( count( $queue ) )
    {

      /*
       * get child nodes
       */
      $parentId = (int) array_shift( $queue );
      $childIds = (array) $this->getChildIds( $datasource, $parentId, "position" );
      $queueLater = array();
      while( count ($childIds ) )
      {
        $childId = array_shift( $childIds );


        /*
         * get child data
         */
        $childData = $this->getNodeData( $datasource, $childId, $parentId );

        /*
         * ingnore inaccessible nodes
         */
        if ( $childData === null )
        {
          //$this->debug("Node #$childId is not accessible");
          continue;
        }

        qcl_assert_array( $childData ); // FIXME assert keys

        /*
         * if the child has children itself, load those
         */
        if ( $recurse and $childData['data']['childCount'] )
        {
          if ( $max and $counter > $max )
          {
            $queueLater[]= (int) $childId;
          }
          else
          {
            array_push( $queue, (int) $childId );
          }
        }

        /*
         * add child data to result
         */
        $nodeArr[] = $childData;
        $counter++;
      }
      if ( $max and $counter > $max ) break;
    }

   /*
    * return the node data
    */
    $queue = array_merge( $queue, $queueLater );
    $queueCount = count($queue);
    //$nodeCount  = count( $nodeArr );
    //$this->debug("Returning $nodeCount nodes, remaining nodes $queueCount");

    return array(
      'nodeData'    => $nodeArr,
      'queue'       => $queue,
      'statusText'  => $queueCount ? "Loading..." : ""
    );
  }

   /**
   * Return the data of a node of the tree.
   * @param string $datasource Datasource name
   * @param int $nodeId
   * @param int $parentId Optional id of parent folder
   * @param mixed|null $options Optional data
   * @return array
   */
  function method_getNodeData( $datasource, $nodeId, $parentId=null, $options=null )
  {
    /*
     * check arguments
     */
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $nodeId );

    /*
     * check access
     */
    $this->checkDatasourceAccess( $datasource );

    return $this->getNodeData( $datasource, $nodeId, $parentId, $options );
  }
}
?>