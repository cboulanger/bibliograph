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
//qcl_import("qcl_data_controller_ITreeController");

/**
 * Interface for a controller that supplies data for a
 * TreeVirtual widget
 *
 */
interface qcl_data_controller_ITreeVirtualController
//  extends qcl_data_controller_ITreeController
{

  /**
   * Return the data of a node of the tree.
   * @param string $datasource Datasource name
   * @param int    $nodeId
   * @param int    $parentId Optional id of parent folder
   * @param mixed|null $options Optional data
   * @return array
   */
  function getNodeData( $datasource, $nodeId, $parentId=null, $options=null );

  /**
   * Returns the number of nodes in a given datasource
   * @param string $datasource
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array containing the keys 'nodeCount', 'transactionId'
   *   and (optionally) 'statusText'.
   */
  function method_getNodeCount( $datasource, $options=null );

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
  function method_getChildCount( $datasource, $nodeId, $options=null );


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
   * @return array
   */
  function method_getChildNodeData(
    $datasource, $ids, $max=null, $recurse=false, $storeId=null, $options=null );

}
?>