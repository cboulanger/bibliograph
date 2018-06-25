/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Interface for a controller that works with tree models that implement
 * ITreeNodeModel
 * 
 * @see lib\controllers\ITreeController
 * @file ITreeController.php
 */
qx.Class.define("rpc.ITree",
{ 
  type: 'static',
  statics: {
    /**
     * Returns the data of child nodes of a branch ordered by the order field
     * 
     * @param datasource {String} Name of the datasource
     * @param parentId {Number} 
     * @param orderBy Optional propert name by which the returned
     * data should be ordered
     * @return {Promise}
     * @see ITreeController::actionChildren
     */
    children : function(datasource, parentId, orderBy){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(parentId);
      // @todo Document type for 'orderBy' in lib\controllers\ITreeController::actionChildren
      return qx.core.Init.getApplication().getRpcClient("itree").send("children", [datasource, parentId, orderBy]);
    },

    /**
     * Returns the ids of the child node ids optionally ordered by a property
     * 
     * @param datasource {String} Name of the datasource
     * @param parentId {Number} 
     * @param orderBy Optional propert name by which the returned
     * data should be ordered
     * @return {Promise}
     * @see ITreeController::actionChildIds
     */
    childIds : function(datasource, parentId, orderBy){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(parentId);
      // @todo Document type for 'orderBy' in lib\controllers\ITreeController::actionChildIds
      return qx.core.Init.getApplication().getRpcClient("itree").send("child-ids", [datasource, parentId, orderBy]);
    },

    /**
     * Returns the number of children of the given node
     * 
     * @param datasource {String} Name of the datasource
     * @param parentId {Number} 
     * @return {Promise}
     * @see ITreeController::actionChildCount
     */
    childCount : function(datasource, parentId){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(parentId);
      return qx.core.Init.getApplication().getRpcClient("itree").send("child-count", [datasource, parentId]);
    },

    /**
     * Reorders the position of the child node. If the tree data in the
     * model does not support reordering, implement as empty stub.
     * 
     * @param datasource {String} Name of the datasource
     * @param parentId {Number} parent folder id
     * @param orderBy defaults to position column
     * @return {Promise}
     * @see ITreeController::actionReorder
     */
    reorder : function(datasource, parentId, orderBy){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(parentId);
      // @todo Document type for 'orderBy' in lib\controllers\ITreeController::actionReorder
      return qx.core.Init.getApplication().getRpcClient("itree").send("reorder", [datasource, parentId, orderBy]);
    },

    /**
     * Change position the absolute position of the node among
     *   the node siblings
     * 
     * @param datasource {String} Name of the datasource
     * @param nodeId {Number} 
     * @param position New position
     * @return {Promise}
     * @see ITreeController::actionChangePosition
     */
    changePosition : function(datasource, nodeId, position){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(nodeId);
      // @todo Document type for 'position' in lib\controllers\ITreeController::actionChangePosition
      return qx.core.Init.getApplication().getRpcClient("itree").send("change-position", [datasource, nodeId, position]);
    },

    /**
     * Change parent node
     * 
     * @param datasource {String} Name of the datasource
     * @param nodeId {Number} Node id
     * @param parentId {Number} New parent node id
     * @param position Position among siblings (if supported)
     * @return {Promise}
     * @see ITreeController::actionChangeParent
     */
    changeParent : function(datasource, nodeId, parentId, position){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(nodeId);
      qx.core.Assert.assertNumber(parentId);
      // @todo Document type for 'position' in lib\controllers\ITreeController::actionChangeParent
      return qx.core.Init.getApplication().getRpcClient("itree").send("change-parent", [datasource, nodeId, parentId, position]);
    },

    /**
     * Returns the path of a node in the folder hierarchy as a
     *   string of the node labels, separated by the a given character
     * 
     * @param datasource {String} Name of the datasource
     * @param nodeId {Number} 
     * @param separator {String} 
     * @return {Promise}
     * @see ITreeController::actionLabelPath
     */
    labelPath : function(datasource, nodeId, separator){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(nodeId);
      qx.core.Assert.assertString(separator);
      return qx.core.Init.getApplication().getRpcClient("itree").send("label-path", [datasource, nodeId, separator]);
    },

    /**
     * Returns the path of a node in the folder hierarchy
     *   as an array of ids
     * 
     * @param datasource {String} Name of the datasource
     * @param nodeId {Number} 
     * @return {Promise}
     * @see ITreeController::actionIdPath
     */
    idPath : function(datasource, nodeId){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(nodeId);
      return qx.core.Init.getApplication().getRpcClient("itree").send("id-path", [datasource, nodeId]);
    },

    /**
     * Returns the id of a node given its label path.
     * 
     * @param datasource {String} Name of the datasource
     * @param path {String} 
     * @param separator {String} Separator character, defaults to "/"
     * @return {Promise}
     * @see ITreeController::actionIdByPath
     */
    idByPath : function(datasource, path, separator){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertString(path);
      qx.core.Assert.assertString(separator);
      return qx.core.Init.getApplication().getRpcClient("itree").send("id-by-path", [datasource, path, separator]);
    },

    /**
     * Creates nodes along the path if they don't exist.
     * 
     * @param datasource {String} Name of the datasource
     * @param path {String} 
     * @param separator {String} Separator character, defaults to "/"
     * @return {Promise}
     * @see ITreeController::actionCreatePath
     */
    createPath : function(datasource, path, separator){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertString(path);
      qx.core.Assert.assertString(separator);
      return qx.core.Init.getApplication().getRpcClient("itree").send("create-path", [datasource, path, separator]);
    }
  }
});