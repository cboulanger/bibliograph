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

/**
 * Interface for a model providing methods to maintain a tree structure
 * based on parent-child relationships between nodes identified by a numeric
 * id.
 */
interface qcl_data_model_ITreeNodeModel
{

  /**
   * Returns the id of the parent node.
   * @return int
   */
  function getParentId();

  /**
   * Returns the data of child nodes of a branch ordered by the order field
   *
   * @param string|null $orderBy Optional propert name by which the returned
   *   data should be ordered
   * @return array
   */
  function getChildren ( $orderBy=null );

  /**
   * Returns the ids of the child node ids optionally ordered by a property
   *
   * @param string|null $orderBy Optional propert name by which the returned
   *   data should be ordered
   * @return array
   */
  function getChildIds ( $orderBy=null );

  /**
   * Returns the number of children of the given node
   * @return int
   */
  function getChildCount ();

  /**
   * Reorders the position of the child node. If the tree data in the
   * model does not support reordering, implement as empty stub.
   *
   * @param string|null $orderBy defaults to position column
   * @return void
   */
  function reorder ( $orderBy=null );

  /**
   * Whether tree model supports positioning
   * @return boolean
   */
  function supportsPositioning();

  /**
   * Change position the absolute position of the node among
   *   the node siblings
   *
   * @param $position
   * @return void
   */
  function changePosition ( $position );

   /**
    * Change parent node
    * @param int $parentId  New parent node id
    * @return int Old parent id
    */
  function changeParent( $parentId );

  /**
   * Returns the path of a node in the folder hierarchy as a
   * string of the node labels, separated by the a given character
   *
   * @param string $separator Separator character, defaults to "/"
   * @return string
   */
  function getLabelPath( $separator="/" );

  /**
   * Returns the path of a node in the folder hierarchy,
   * as an array of ids.
   *
   * @return string
   */
  function getIdPath();

  /**
   * Returns the id of a node given its label path
   * @param string $path
   * @param string $separator Separator character, defaults to "/"
   * @return int|null The id of the node or null if node does not exist
   */
  function getIdByPath ( $path, $separator="/" );

  /**
   * Creates nodes along the path if they don't exist
   * @param string $path
   * @param string $separator Separator character, defaults to "/"
   * @return int Node id
   */
  function createPath( $path, $separator="/" );

}
