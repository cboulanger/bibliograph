<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2018 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace lib\controllers;


/**
 * Interface for a controller that works with tree models that implement
 * ITreeNodeModel
 */
interface ITreeController
{

  /**
   * Returns the number of nodes in a given datasource
   *
   * @param string $datasource
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * @return array containing the keys 'nodeCount', 'transactionId'
   *   and (optionally) 'statusText'.
   */
  function actionNodeCount(string $datasource, array $options = null);

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
  function actionChildCount($datasource, $nodeId, $options = null);


  /**
   * Returns all nodes of a tree in a given datasource
   * @param string $datasource
   * @param mixed|null $options Optional data, for example, when nodes
   *   should be filtered by a certain criteria
   * //return { nodeData : [], statusText: [] }.
   */
  function actionLoad($datasource, $options = null);

//  /**
//   * Returns the data of child nodes of a branch ordered by the order field
//   * @param string $datasource Name of the datasource
//   * @param int $parentId
//   * @param string|null $orderBy Optional propert name by which the returned
//   *   data should be ordered
//   * @return array
//   */
//  function actionChildren ( $datasource, $parentId, $orderBy=null );
//
//  /**
//   * Returns the ids of the child node ids optionally ordered by a property
//   * @param string $datasource Name of the datasource
//   * @param int $parentId
//   * @param string|null $orderBy Optional propert name by which the returned
//   *   data should be ordered
//   * @return array
//   */
//  function actionChildIds ( $datasource, $parentId, $orderBy=null );
//
//  /**
//   * Returns the number of children of the given node
//   * @param string $datasource Name of the datasource
//   * @param int $parentId
//   * @return int
//   */
//  function actionChildCount ( $datasource, $parentId );
//
//  /**
//   * Reorders the position of the child node. If the tree data in the
//   * model does not support reordering, implement as empty stub.
//   * @param string $datasource Name of the datasource
//   * @param int $parentId parent folder id
//   * @param string|null $orderBy defaults to position column
//   * @return void
//   */
//  function actionReorder ( $datasource, $parentId, $orderBy=null );
//
//   /**
//    * Change position the absolute position of the node among
//    *   the node siblings
//    * @param string $datasource Name of the datasource
//    * @param int $nodeId
//    * @param int|string $position New position
//    * @return void
//    */
//  function actionChangePosition ( $datasource, $nodeId, $position );
//
//   /**
//    * Change parent node
//    * @param string $datasource Name of the datasource
//    * @param int $nodeId  Node id
//    * @param int $parentId  New parent node id
//    * @param int|null $position Position among siblings (if supported)
//    * @return int Old parent id
//    */
//  function actionChangeParent( $datasource, $nodeId, $parentId, $position=null );
//
//  /**
//   * Returns the path of a node in the folder hierarchy as a
//   *   string of the node labels, separated by the a given character
//   * @param string $datasource Name of the datasource
//   * @param int $nodeId
//   * @param string $separator
//   * @return string
//   */
//  function actionLabelPath( $datasource, $nodeId, $separator );
//
//  /**
//   * Returns the path of a node in the folder hierarchy
//   *   as an array of ids
//   * @param string $datasource Name of the datasource
//   * @param int $nodeId
//   * @internal param string $separator
//   * @return string
//   */
//  function actionIdPath( $datasource, $nodeId );
//
//  /**
//   * Returns the id of a node given its label path.
//   * @param string $datasource Name of the datasource
//   * @param string $path
//   * @param string $separator Separator character, defaults to "/"
//   * @return int|null The id of the node or null if node does not exist
//   */
//  function actionIdByPath ( $datasource, $path, $separator="/" );
//
//  /**
//   * Creates nodes along the path if they don't exist.
//   * @param string $datasource Name of the datasource
//   * @param string $path
//   * @param string $separator Separator character, defaults to "/"
//   * @return int Node id
//   */
//  function actionCreatePath( $datasource, $path, $separator="/" );
}
