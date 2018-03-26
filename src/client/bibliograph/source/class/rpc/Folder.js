/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Service to reset email. Called by a REST request
 * 
 * @see app\controllers\FolderController
 * @file FolderController.php
 */
qx.Class.define("rpc.Folder",
{ 
  type: 'static',
  statics: {
    /**
     * Returns the number of nodes in a given datasource
     * 
     * @param datasource {String} 
     * @param options {Array} Optional data, for example, when nodes
     * should be filtered by a certain criteria
     * @return {Promise}
     * @see FolderController::actionNodeCount
     */
    nodeCount : function(datasource, options){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertArray(options);
      return qx.core.Init.getApplication().getRpcClient("folder").send("node-count", [datasource, options]);
    },

    /**
     * Returns the number of children of a node with the given id
     * in the given datasource.
     * 
     * @param datasource 
     * @param nodeId 
     * @param options Optional data, for example, when nodes
     * should be filtered by a certain criteria
     * @return {Promise}
     * @see FolderController::actionChildCount
     */
    childCount : function(datasource, nodeId, options){
      // @todo Document type for 'datasource' in app\controllers\FolderController::actionChildCount
      // @todo Document type for 'nodeId' in app\controllers\FolderController::actionChildCount
      // @todo Document type for 'options' in app\controllers\FolderController::actionChildCount
      return qx.core.Init.getApplication().getRpcClient("folder").send("child-count", [datasource, nodeId, options]);
    },

    /**
     * Returns all nodes of a tree in a given datasource
     * 
     * @param datasource {String} 
     * @param options Optional data, for example, when nodes
     *   should be filtered by a certain criteria
     * //return { nodeData : [], statusText: [] }.
     * @return {Promise}
     * @see FolderController::actionLoad
     */
    load : function(datasource, options){
      qx.core.Assert.assertString(datasource);
      // @todo Document type for 'options' in app\controllers\FolderController::actionLoad
      return qx.core.Init.getApplication().getRpcClient("folder").send("load", [datasource, options]);
    },

    /**
     * Edit folder data
     * 
     * @param datasource 
     * @param folderId 
     * @return {Promise}
     * @see FolderController::actionEdit
     */
    edit : function(datasource, folderId){
      // @todo Document type for 'datasource' in app\controllers\FolderController::actionEdit
      // @todo Document type for 'folderId' in app\controllers\FolderController::actionEdit
      return qx.core.Init.getApplication().getRpcClient("folder").send("edit", [datasource, folderId]);
    },

    /**
     * Saves the result of the edit() method
     * 
     * @param data 
     * @param datasource {String} 
     * @param folderId {Number} 
     * @return {Promise}
     * @see FolderController::actionSave
     */
    save : function(data, datasource, folderId){
      // @todo Document type for 'data' in app\controllers\FolderController::actionSave
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(folderId);
      return qx.core.Init.getApplication().getRpcClient("folder").send("save", [data, datasource, folderId]);
    },

    /**
     * Change the public state - creates dialog event.
     * 
     * @param datasource 
     * @param folderId 
     * @return {Promise}
     * @see FolderController::actionVisibilityDialog
     */
    visibilityDialog : function(datasource, folderId){
      // @todo Document type for 'datasource' in app\controllers\FolderController::actionVisibilityDialog
      // @todo Document type for 'folderId' in app\controllers\FolderController::actionVisibilityDialog
      return qx.core.Init.getApplication().getRpcClient("folder").send("visibility-dialog", [datasource, folderId]);
    },

    /**
     * Change the public state
     * 
     * @param data 
     * @param datasource {String} 
     * @param folderId {Number} 
     * @return {Promise}
     * @see FolderController::actionVisibilityChange
     */
    visibilityChange : function(data, datasource, folderId){
      // @todo Document type for 'data' in app\controllers\FolderController::actionVisibilityChange
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(folderId);
      return qx.core.Init.getApplication().getRpcClient("folder").send("visibility-change", [data, datasource, folderId]);
    },

    /**
     * Action to add a folder. Creates a dialog event
     * 
     * @param datasource {String} 
     * @param folderId {Number} 
     * @return {Promise}
     * @see FolderController::actionAddDialog
     */
    addDialog : function(datasource, folderId){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(folderId);
      return qx.core.Init.getApplication().getRpcClient("folder").send("add-dialog", [datasource, folderId]);
    },

    /**
     * Creates a new folder
     * 
     * @param data 
     * @param datasource 
     * @param parentFolderId 
     * @return {Promise}
     * @see FolderController::actionCreate
     */
    create : function(data, datasource, parentFolderId){
      // @todo Document type for 'data' in app\controllers\FolderController::actionCreate
      // @todo Document type for 'datasource' in app\controllers\FolderController::actionCreate
      // @todo Document type for 'parentFolderId' in app\controllers\FolderController::actionCreate
      return qx.core.Init.getApplication().getRpcClient("folder").send("create", [data, datasource, parentFolderId]);
    },

    /**
     * Creates a confimation dialog to remove a folder
     * 
     * @param datasource 
     * @param folderId 
     * @return {Promise}
     * @see FolderController::actionRemoveDialog
     */
    removeDialog : function(datasource, folderId){
      // @todo Document type for 'datasource' in app\controllers\FolderController::actionRemoveDialog
      // @todo Document type for 'folderId' in app\controllers\FolderController::actionRemoveDialog
      return qx.core.Init.getApplication().getRpcClient("folder").send("remove-dialog", [datasource, folderId]);
    },

    /**
     * Removes the given folder
     * 
     * @param data 
     * @param datasource 
     * @param folderId 
     * @return {Promise}
     * @see FolderController::actionRemove
     */
    remove : function(data, datasource, folderId){
      // @todo Document type for 'data' in app\controllers\FolderController::actionRemove
      // @todo Document type for 'datasource' in app\controllers\FolderController::actionRemove
      // @todo Document type for 'folderId' in app\controllers\FolderController::actionRemove
      return qx.core.Init.getApplication().getRpcClient("folder").send("remove", [data, datasource, folderId]);
    },

    /**
     * Move a folder to a different parent
     * 
     * @param datasource {String} 
     * @param folderId {Number} 
     * @param parentId {Number} 
     * @return {Promise}
     * @see FolderController::actionMove
     */
    move : function(datasource, folderId, parentId){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(folderId);
      qx.core.Assert.assertNumber(parentId);
      return qx.core.Init.getApplication().getRpcClient("folder").send("move", [datasource, folderId, parentId]);
    },

    /**
     * Changes the position of a folder within its siblings
     * 
     * @param datasource 
     * @param folderId 
     * @param position 
     * @return {Promise}
     * @see FolderController::actionPositionChange
     */
    positionChange : function(datasource, folderId, position){
      // @todo Document type for 'datasource' in app\controllers\FolderController::actionPositionChange
      // @todo Document type for 'folderId' in app\controllers\FolderController::actionPositionChange
      // @todo Document type for 'position' in app\controllers\FolderController::actionPositionChange
      return qx.core.Init.getApplication().getRpcClient("folder").send("position-change", [datasource, folderId, position]);
    }
  }
});