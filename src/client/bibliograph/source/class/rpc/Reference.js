/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Provides services based on a generic model API, using datasource
 * and modelType information
 * 
 * @see app\controllers\ReferenceController
 * @file ReferenceController.php
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
     * @param modelClassType {|string} 
     * @return {Promise}
     * @see ReferenceController::actionTableLayout
     */
    tableLayout : function(datasource, modelClassType){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionTableLayout
      // @todo Document type for 'modelClassType' in app\controllers\ReferenceController::actionTableLayout
      return qx.core.Init.getApplication().getRpcClient("reference").send("table-layout", [datasource, modelClassType]);
    },

    /**
     * Returns data for the reference type select box
     * 
     * @param datasource 
     * @return {Promise}
     * @see ReferenceController::actionReferenceTypeList
     */
    referenceTypeList : function(datasource){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionReferenceTypeList
      return qx.core.Init.getApplication().getRpcClient("reference").send("reference-type-list", [datasource]);
    },

    /**
     * Returns data for the store that populates reference type lists
     * 
     * @param datasource 
     * @return {Promise}
     * @see ReferenceController::actionTypes
     */
    types : function(datasource){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionTypes
      return qx.core.Init.getApplication().getRpcClient("reference").send("types", [datasource]);
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
    item : function(datasource, arg2, arg3, arg4){
      qx.core.Assert.assertString(datasource);
      // @todo Document type for 'arg2' in app\controllers\ReferenceController::actionItem
      // @todo Document type for 'arg3' in app\controllers\ReferenceController::actionItem
      // @todo Document type for 'arg4' in app\controllers\ReferenceController::actionItem
      return qx.core.Init.getApplication().getRpcClient("reference").send("item", [datasource, arg2, arg3, arg4]);
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
    autocomplete : function(datasource, field, input){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionAutocomplete
      // @todo Document type for 'field' in app\controllers\ReferenceController::actionAutocomplete
      // @todo Document type for 'input' in app\controllers\ReferenceController::actionAutocomplete
      return qx.core.Init.getApplication().getRpcClient("reference").send("autocomplete", [datasource, field, input]);
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
    save : function(datasource, referenceId, data){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionSave
      // @todo Document type for 'referenceId' in app\controllers\ReferenceController::actionSave
      // @todo Document type for 'data' in app\controllers\ReferenceController::actionSave
      return qx.core.Init.getApplication().getRpcClient("reference").send("save", [datasource, referenceId, data]);
    },

    /**
     * Returns distinct values for a field, sorted alphatbetically, in a format suitable
     * for a ComboBox widget.
     * 
     * @param datasource 
     * @param field 
     * @return {Promise}
     * @see ReferenceController::actionListField
     */
    listField : function(datasource, field){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionListField
      // @todo Document type for 'field' in app\controllers\ReferenceController::actionListField
      return qx.core.Init.getApplication().getRpcClient("reference").send("list-field", [datasource, field]);
    },

    /**
     * Creates a new reference
     * 
     * @param datasource {String} 
     * @param folderId {Number|string} 
     * @param data 
     * @return {Promise}
     * @see ReferenceController::actionCreate
     */
    create : function(datasource, folderId, data){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(folderId);
      // @todo Document type for 'data' in app\controllers\ReferenceController::actionCreate
      return qx.core.Init.getApplication().getRpcClient("reference").send("create", [datasource, folderId, data]);
    },

    /**
     * Removes references from a folder. If the reference is not contained in any other folder,
     * move it to the trash
     * 
     * @param datasource {String} The name of the datasource
     * @param folderId {Number} The numeric id of the folder. If zero, remove from all folders
     * @param ids {String} A string of the numeric ids of the references, joined by a comma
     * @return {Promise}
     * @see ReferenceController::actionRemove
     */
    remove : function(datasource, folderId, ids){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(folderId);
      qx.core.Assert.assertString(ids);
      return qx.core.Init.getApplication().getRpcClient("reference").send("remove", [datasource, folderId, ids]);
    },

    /**
     * Confirm that a reference should be moved to the trash folder
     * 
     * @param confirmed 
     * @param datasource {String} 
     * @param ids {String} 
     * @return {Promise}
     * @see ReferenceController::actionConfirmMoveToTrash
     */
    confirmMoveToTrash : function(confirmed, datasource, ids){
      // @todo Document type for 'confirmed' in app\controllers\ReferenceController::actionConfirmMoveToTrash
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertString(ids);
      return qx.core.Init.getApplication().getRpcClient("reference").send("confirm-move-to-trash", [confirmed, datasource, ids]);
    },

    /**
     * Move references from one folder to another folder
     * 
     * @param datasource {String} If true, it is the result of the confirmation
     * @param folderId {Number} The folder to move from
     * @param targetFolderId {Number} The folder to move to
     * @param ids {String} The ids of the references to move, joined by  a comma
     * @return {Promise}
     * @see ReferenceController::actionMove
     */
    move : function(datasource, folderId, targetFolderId, ids){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(folderId);
      qx.core.Assert.assertNumber(targetFolderId);
      qx.core.Assert.assertString(ids);
      return qx.core.Init.getApplication().getRpcClient("reference").send("move", [datasource, folderId, targetFolderId, ids]);
    },

    /**
     * Copies a reference to a folder
     * 
     * @param datasource {String} 
     * @param targetFolderId {Number} 
     * @param ids {String} Numeric ids joined by comma
     * @return {Promise}
     * @see ReferenceController::actionCopy
     */
    copy : function(datasource, targetFolderId, ids){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(targetFolderId);
      qx.core.Assert.assertString(ids);
      return qx.core.Init.getApplication().getRpcClient("reference").send("copy", [datasource, targetFolderId, ids]);
    },

    /**
     * Removes all references from a folder
     * 
     * @param datasource {String} 
     * @param folderId {Number} 
     * @return {Promise}
     * @see ReferenceController::actionEmptyFolder
     */
    emptyFolder : function(datasource, folderId){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(folderId);
      return qx.core.Init.getApplication().getRpcClient("reference").send("empty-folder", [datasource, folderId]);
    },

    /**
     * Returns information on the record as a HTML table
     * 
     * @param datasource 
     * @param referenceId 
     * @return {Promise}
     * @see ReferenceController::actionTableHtml
     */
    tableHtml : function(datasource, referenceId){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionTableHtml
      // @todo Document type for 'referenceId' in app\controllers\ReferenceController::actionTableHtml
      return qx.core.Init.getApplication().getRpcClient("reference").send("table-html", [datasource, referenceId]);
    },

    /**
     * Returns a HTML table with the reference data
     * 
     * @param datasource 
     * @param id 
     * @return {Promise}
     * @see ReferenceController::actionItemHtml
     */
    itemHtml : function(datasource, id){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionItemHtml
      // @todo Document type for 'id' in app\controllers\ReferenceController::actionItemHtml
      return qx.core.Init.getApplication().getRpcClient("reference").send("item-html", [datasource, id]);
    },

    /**
     * Returns data on folders that contain the given reference
     * 
     * @param datasource 
     * @param referenceId 
     * @return {Promise}
     * @see ReferenceController::actionContainers
     */
    containers : function(datasource, referenceId){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionContainers
      // @todo Document type for 'referenceId' in app\controllers\ReferenceController::actionContainers
      return qx.core.Init.getApplication().getRpcClient("reference").send("containers", [datasource, referenceId]);
    },

    /**
     * Returns potential duplicates in a simple data model format.
     * 
     * @param datasource {String} 
     * @param referenceId {Number} 
     * @return {Promise}
     * @see ReferenceController::actionDuplicatesData
     */
    duplicatesData : function(datasource, referenceId){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertNumber(referenceId);
      return qx.core.Init.getApplication().getRpcClient("reference").send("duplicates-data", [datasource, referenceId]);
    },

    /**
     * 
     * 
     * @param input 
     * @param inputPosition 
     * @param tokens 
     * @param datasourceName 
     * @return {Promise}
     * @see ReferenceController::actionTokenizeQuery
     */
    tokenizeQuery : function(input, inputPosition, tokens, datasourceName){
      // @todo Document type for 'input' in app\controllers\ReferenceController::actionTokenizeQuery
      // @todo Document type for 'inputPosition' in app\controllers\ReferenceController::actionTokenizeQuery
      // @todo Document type for 'tokens' in app\controllers\ReferenceController::actionTokenizeQuery
      // @todo Document type for 'datasourceName' in app\controllers\ReferenceController::actionTokenizeQuery
      return qx.core.Init.getApplication().getRpcClient("reference").send("tokenize-query", [input, inputPosition, tokens, datasourceName]);
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
      return qx.core.Init.getApplication().getRpcClient("reference").send("row-count", [clientQueryData]);
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
      return qx.core.Init.getApplication().getRpcClient("reference").send("row-data", [firstRow, lastRow, requestId, clientQueryData]);
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
    formLayout : function(datasource, reftype){
      // @todo Document type for 'datasource' in app\controllers\ReferenceController::actionFormLayout
      // @todo Document type for 'reftype' in app\controllers\ReferenceController::actionFormLayout
      return qx.core.Init.getApplication().getRpcClient("reference").send("form-layout", [datasource, reftype]);
    }
  }
});