/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * 
 * @see app\controllers\ImportController
 * @file ImportController.php
 */
qx.Class.define("rpc.Import",
{ 
  type: 'static',
  statics: {
    /**
     * Returns the layout of the columns of the table displaying
     * the records
     * 
     * @param datasource 
     * @return {Promise}
     * @see ImportController::actionTableLayout
     */
    tableLayout : function(datasource){
      // @todo Document type for 'datasource' in app\controllers\ImportController::actionTableLayout
      return qx.core.Init.getApplication().getRpcClient("import").send("table-layout", [datasource]);
    },

    /**
     * Returns the list of import formats for a selectbox
     * 
     * @return {Promise}
     * @see ImportController::actionImportFormats
     */
    importFormats : function(){
      return qx.core.Init.getApplication().getRpcClient("import").send("import-formats", []);
    },

    /**
     * Parse the data from the last uploaded file with the given format.
     * Returns an associative array containing the keys "datasource" with the name of the
     * datasource (usually "bibliograph_import") and "folderId" containing
     * the numeric value of the folder containing the processed references.
     * @param format {String} The name of the import format
     * @return {Promise}
     * @see ImportController::actionParseUpload
     */
    parseUpload : function(format){
      qx.core.Assert.assertString(format);
      return qx.core.Init.getApplication().getRpcClient("import").send("parse-upload", [format]);
    },

    /**
     * Imports the references with the given ids to a target folder
     * 
     * @param ids {String} Comma-separated ids
     * @param targetDatasource {String} 
     * @param targetFolderId {Number} 
     * @return {Promise}
     * @see ImportController::actionImport
     */
    import : function(ids, targetDatasource, targetFolderId){
      qx.core.Assert.assertString(ids);
      qx.core.Assert.assertString(targetDatasource);
      qx.core.Assert.assertNumber(targetFolderId);
      return qx.core.Init.getApplication().getRpcClient("import").send("import", [ids, targetDatasource, targetFolderId]);
    },

    /**
     * Returns count of rows that will be retrieved when executing the current
     * query.
     * param object $queryData data to construct the query. Needs at least the
     * a string property "datasource" with the name of datasource and a property
     * "modelType" with the type of the model.
     * @param clientQueryData 
     * @return {Promise}
     * @see ImportController::actionRowCount
     */
    rowCount : function(clientQueryData){
      // @todo Document type for 'clientQueryData' in app\controllers\ImportController::actionRowCount
      return qx.core.Init.getApplication().getRpcClient("import").send("row-count", [clientQueryData]);
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
     * @see ImportController::actionRowData
     */
    rowData : function(firstRow, lastRow, requestId, clientQueryData){
      qx.core.Assert.assertNumber(firstRow);
      qx.core.Assert.assertNumber(lastRow);
      qx.core.Assert.assertNumber(requestId);
      // @todo Document type for 'clientQueryData' in app\controllers\ImportController::actionRowData
      return qx.core.Init.getApplication().getRpcClient("import").send("row-data", [firstRow, lastRow, requestId, clientQueryData]);
    },

    /**
     * Returns the form layout for the given reference type and
     * datasource
     * 
     * @param datasource 
     * @param reftype 
     * @return {Promise}
     * @see ImportController::actionFormLayout
     */
    formLayout : function(datasource, reftype){
      // @todo Document type for 'datasource' in app\controllers\ImportController::actionFormLayout
      // @todo Document type for 'reftype' in app\controllers\ImportController::actionFormLayout
      return qx.core.Init.getApplication().getRpcClient("import").send("form-layout", [datasource, reftype]);
    }
  }
});