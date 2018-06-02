/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Return the model for the datasource store
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
     * @param options {|null} Optional data, for example, when nodes
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
     * @param options {|null} Optional data, for example, when nodes
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
     * Saves the current search query as a subfolder
     * 
     * @param datasource {String} 
     * @param parentFolderId {Number} 
     * @param query {String} 
     * @return {Promise}
     * @see FolderController::actionSaveSearch
     */
    saveSearch : function(datasource, parentFolderId, query){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(parentFolderId);
      qx.core.Assert.assertString(query);
      return qx.core.Init.getApplication().getRpcClient("folder").send("save-search", [datasource, parentFolderId, query]);
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
     * Copies the given folder into the parent folder including all references contained. If copying within the #
     * same datasource, this creates a link, otherwise new references are created.
     * 
     * @param from_datasource {String} 
     * @param from_folderId {Number} 
     * @param to_datasource {String} 
     * @param to_parentId {Number} 
     * @return {Promise}
     * @see FolderController::actionCopy
     */
    copy : function(from_datasource, from_folderId, to_datasource, to_parentId){
      qx.core.Assert.assertString(from_datasource);
      qx.core.Assert.assertNumber(from_folderId);
      qx.core.Assert.assertString(to_datasource);
      qx.core.Assert.assertNumber(to_parentId);
      return qx.core.Init.getApplication().getRpcClient("folder").send("copy", [from_datasource, from_folderId, to_datasource, to_parentId]);
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
    },

    /**
     * Returns count of rows that will be retrieved when executing the current
     * query.
     * param object $queryData data to construct the query. Needs at least the
     * a string property "datasource" with the name of datasource and a property
     * "modelType" with the type of the model.
     * @param clientQueryData 
     * @return {Promise}
     * @see FolderController::actionRowCount
     */
    rowCount : function(clientQueryData){
      // @todo Document type for 'clientQueryData' in app\controllers\FolderController::actionRowCount
      return qx.core.Init.getApplication().getRpcClient("folder").send("row-count", [clientQueryData]);
    },

    /**
     * Returns row data executing a constructed query
     * 
     * @param firstRow {Number} First row of queried data
     * @param lastRow {Number} Last row of queried data
     * @param requestId {Number} Request id
     * param object $queryData Data to construct the query
     * @param clientQueryData 
     * @return {Promise}
     * @see FolderController::actionRowData
     */
    rowData : function(firstRow, lastRow, requestId, clientQueryData){
      qx.core.Assert.assertNumber(firstRow);
      qx.core.Assert.assertNumber(lastRow);
      qx.core.Assert.assertNumber(requestId);
      // @todo Document type for 'clientQueryData' in app\controllers\FolderController::actionRowData
      return qx.core.Init.getApplication().getRpcClient("folder").send("row-data", [firstRow, lastRow, requestId, clientQueryData]);
    },

    /**
     * Returns the form layout for the given reference type and
     * datasource
     * 
     * @param datasource 
     * @param reftype 
     * @return {Promise}
     * @see FolderController::actionFormLayout
     */
    formLayout : function(datasource, reftype){
      // @todo Document type for 'datasource' in app\controllers\FolderController::actionFormLayout
      // @todo Document type for 'reftype' in app\controllers\FolderController::actionFormLayout
      return qx.core.Init.getApplication().getRpcClient("folder").send("form-layout", [datasource, reftype]);
    }
  }
});