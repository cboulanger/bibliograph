/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Provides services based on a generic model API, using datasource
 * and modelType information
 * 
 * @see app\controllers\ReferenceController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/ReferenceController.php
 */
qx.Class.define("rpc.Reference",
{ 
  type: 'static',
  statics: {
    /**
     * Returns the layout of the columns of the table displaying
     * the records
     * 
     * @param datasource 
     * @param modelClassType 
     * @return {Promise}
     * @see ReferenceController::actionTableLayout
     */
    tableLayout : function(datasource=null, modelClassType=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionTableLayout
      // @todo Document type for 'modelClassType' in app\controllers\ReferenceController::actionTableLayout
      return this.getApplication().getRpcClient("reference").send("table-layout", [datasource, modelClassType]);
    },

    /**
     * Returns count of rows that will be retrieved when executing the current
     * query.
     * param object $queryData data to construct the query. Needs at least the
     * a string property "datasource" with the name of datasource and a property
     * "modelType" with the type of the model.
     * @param clientQueryData 
     * @return {Promise}
     * @see ReferenceController::actionRowCount
     */
    rowCount : function(clientQueryData){
      // @todo Document type for 'clientQueryData' in app\controllers\ReferenceController::actionRowCount
      return this.getApplication().getRpcClient("reference").send("row-count", [clientQueryData]);
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
     * @see ReferenceController::actionRowData
     */
    rowData : function(firstRow, lastRow, requestId, clientQueryData){
      qx.core.Assert.assertNumber(firstRow);
      qx.core.Assert.assertNumber(lastRow);
      qx.core.Assert.assertNumber(requestId);
      // @todo Document type for 'clientQueryData' in app\controllers\ReferenceController::actionRowData
      return this.getApplication().getRpcClient("reference").send("row-data", [firstRow, lastRow, requestId, clientQueryData]);
    },

    /**
     * Returns the form layout for the given reference type and
     * datasource
     * 
     * @param datasource 
     * @param reftype 
     * @return {Promise}
     * @see ReferenceController::actionFormLayout
     */
    formLayout : function(datasource=null, reftype=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionFormLayout
      // @todo Document type for 'reftype' in app\controllers\ReferenceController::actionFormLayout
      return this.getApplication().getRpcClient("reference").send("form-layout", [datasource, reftype]);
    },

    /**
     * Returns data for the reference type select box
     * 
     * @param datasource 
     * @return {Promise}
     * @see ReferenceController::actionReferenceTypeList
     */
    referenceTypeList : function(datasource=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionReferenceTypeList
      return this.getApplication().getRpcClient("reference").send("reference-type-list", [datasource]);
    },

    /**
     * Returns data for the store that populates reference type lists
     * 
     * @param datasource 
     * @return {Promise}
     * @see ReferenceController::actionTypes
     */
    types : function(datasource=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionTypes
      return this.getApplication().getRpcClient("reference").send("types", [datasource]);
    },

    /**
     * Returns the requested or all accessible properties of a reference
     * 
     * @param datasource {String} 
     * @param arg2 
     * @param arg3 
     * @param arg4 
     * @return {Promise}
     * @see ReferenceController::actionItem
     */
    item : function(datasource=null, arg2=null, arg3=null, arg4=null){
      qx.core.Assert.assertString(datasource);
      // @todo Document type for 'arg2' in app\controllers\ReferenceController::actionItem
      // @todo Document type for 'arg3' in app\controllers\ReferenceController::actionItem
      // @todo Document type for 'arg4' in app\controllers\ReferenceController::actionItem
      return this.getApplication().getRpcClient("reference").send("item", [datasource, arg2, arg3, arg4]);
    },

    /**
     * Returns data for the qcl.data.controller.AutoComplete
     * 
     * @param datasource 
     * @param field 
     * @param input 
     * @return {Promise}
     * @see ReferenceController::actionAutocomplete
     */
    autocomplete : function(datasource=null, field=null, input=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionAutocomplete
      // @todo Document type for 'field' in app\controllers\ReferenceController::actionAutocomplete
      // @todo Document type for 'input' in app\controllers\ReferenceController::actionAutocomplete
      return this.getApplication().getRpcClient("reference").send("autocomplete", [datasource, field, input]);
    },

    /**
     * Saves a value in the model
     * 
     * @param datasource 
     * @param referenceId 
     * @param data 
     * @return {Promise}
     * @see ReferenceController::actionSave
     */
    save : function(datasource=null, referenceId=null, data=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionSave
      // @todo Document type for 'referenceId' in app\controllers\ReferenceController::actionSave
      // @todo Document type for 'data' in app\controllers\ReferenceController::actionSave
      return this.getApplication().getRpcClient("reference").send("save", [datasource, referenceId, data]);
    },

    /**
     * Returns data for a ComboBox widget.
     * 
     * @param datasource 
     * @param field 
     * @return {Promise}
     * @see ReferenceController::actionListField
     */
    listField : function(datasource=null, field=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionListField
      // @todo Document type for 'field' in app\controllers\ReferenceController::actionListField
      return this.getApplication().getRpcClient("reference").send("list-field", [datasource, field]);
    },

    /**
     * Creates a new reference
     * 
     * @param datasource {String} 
     * @param folderId 
     * @param data 
     * @return {Promise}
     * @see ReferenceController::actionCreate
     */
    create : function(datasource=null, folderId=null, data=null){
      qx.core.Assert.assertString(datasource);
      // @todo Document type for 'folderId' in app\controllers\ReferenceController::actionCreate
      // @todo Document type for 'data' in app\controllers\ReferenceController::actionCreate
      return this.getApplication().getRpcClient("reference").send("create", [datasource, folderId, data]);
    },

    /**
     * Remove references. If a folder id is given, remove from that folder
     * 
     * @param first If boolean, the response to the confirmation dialog. Otherwise, the datasource name
     * @param second If string, the shelve id. If array, an array of parameters for the action:
     * datasource; folder id; target folder id (not used); ids as a string separated by commas
     * @return {Promise}
     * @see ReferenceController::actionRemove
     */
    remove : function(first=null, second=null){
      // @todo Document type for 'first' in app\controllers\ReferenceController::actionRemove
      // @todo Document type for 'second' in app\controllers\ReferenceController::actionRemove
      return this.getApplication().getRpcClient("reference").send("remove", [first, second]);
    },

    /**
     * Removes all references from a folder
     * 
     * @param datasource 
     * @param folderId {Number} 
     * @return {Promise}
     * @see ReferenceController::actionFolderRemove
     */
    folderRemove : function(datasource=null, folderId=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionFolderRemove
      qx.core.Assert.assertNumber(folderId);
      return this.getApplication().getRpcClient("reference").send("folder-remove", [datasource, folderId]);
    },

    /**
     * Move references from one folder to another folder
     * 
     * @param datasource If true, it is the result of the confirmation
     * @param folderId {Number} The folder to move from
     * @param targetFolderId {Number} The folder to move to
     * @param ids The ids of the references to move
     * @return {Promise}
     * @see ReferenceController::actionMove
     */
    move : function(datasource=null, folderId=null, targetFolderId=null, ids=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionMove
      qx.core.Assert.assertNumber(folderId);
      qx.core.Assert.assertNumber(targetFolderId);
      // @todo Document type for 'ids' in app\controllers\ReferenceController::actionMove
      return this.getApplication().getRpcClient("reference").send("move", [datasource, folderId, targetFolderId, ids]);
    },

    /**
     * Copies a reference to a folder
     * 
     * @param datasource 
     * @param folderId 
     * @param targetFolderId 
     * @param ids 
     * @return {Promise}
     * @see ReferenceController::actionCopy
     */
    copy : function(datasource=null, folderId=null, targetFolderId=null, ids=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionCopy
      // @todo Document type for 'folderId' in app\controllers\ReferenceController::actionCopy
      // @todo Document type for 'targetFolderId' in app\controllers\ReferenceController::actionCopy
      // @todo Document type for 'ids' in app\controllers\ReferenceController::actionCopy
      return this.getApplication().getRpcClient("reference").send("copy", [datasource, folderId, targetFolderId, ids]);
    },

    /**
     * Returns information on the record as a HTML table
     * 
     * @param datasource 
     * @param referenceId 
     * @return {Promise}
     * @see ReferenceController::actionTableHtml
     */
    tableHtml : function(datasource=null, referenceId=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionTableHtml
      // @todo Document type for 'referenceId' in app\controllers\ReferenceController::actionTableHtml
      return this.getApplication().getRpcClient("reference").send("table-html", [datasource, referenceId]);
    },

    /**
     * Returns a HTML table with the reference data
     * 
     * @param datasource 
     * @param id 
     * @return {Promise}
     * @see ReferenceController::actionItemHtml
     */
    itemHtml : function(datasource=null, id=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionItemHtml
      // @todo Document type for 'id' in app\controllers\ReferenceController::actionItemHtml
      return this.getApplication().getRpcClient("reference").send("item-html", [datasource, id]);
    },

    /**
     * Returns data on folders that contain the given reference
     * 
     * @param datasource 
     * @param referenceId 
     * @return {Promise}
     * @see ReferenceController::actionContainers
     */
    containers : function(datasource=null, referenceId=null){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionContainers
      // @todo Document type for 'referenceId' in app\controllers\ReferenceController::actionContainers
      return this.getApplication().getRpcClient("reference").send("containers", [datasource, referenceId]);
    },

    /**
     * Returns potential duplicates in a simple data model format.
     * 
     * @param datasource {String} 
     * @param referenceId {Number} 
     * @return {Promise}
     * @see ReferenceController::actionDuplicatesData
     */
    duplicatesData : function(datasource=null, referenceId=null){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(referenceId);
      return this.getApplication().getRpcClient("reference").send("duplicates-data", [datasource, referenceId]);
    },

    /**
     * @return {Promise}
     * @see ReferenceController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("reference").send("index", []);
    }
  }
});