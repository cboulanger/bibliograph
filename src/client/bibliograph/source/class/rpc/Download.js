/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Empties the trash folder
 * 
 * @see app\modules\converters\controllers\DownloadController
 * @file DownloadController.php
 */
qx.Class.define("rpc.Download",
{ 
  type: 'static',
  statics: {
    /**
     * Returns count of rows that will be retrieved when executing the current
     * query.
     * param object $queryData data to construct the query. Needs at least the
     * a string property "datasource" with the name of datasource and a property
     * "modelType" with the type of the model.
     * @param clientQueryData 
     * @return {Promise}
     * @see DownloadController::actionRowCount
     */
    rowCount : function(clientQueryData){
      // @todo Document type for 'clientQueryData' in app\modules\converters\controllers\DownloadController::actionRowCount
      return qx.core.Init.getApplication().getRpcClient("download").send("row-count", [clientQueryData]);
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
     * @see DownloadController::actionRowData
     */
    rowData : function(firstRow, lastRow, requestId, clientQueryData){
      qx.core.Assert.assertNumber(firstRow);
      qx.core.Assert.assertNumber(lastRow);
      qx.core.Assert.assertNumber(requestId);
      // @todo Document type for 'clientQueryData' in app\modules\converters\controllers\DownloadController::actionRowData
      return qx.core.Init.getApplication().getRpcClient("download").send("row-data", [firstRow, lastRow, requestId, clientQueryData]);
    },

    /**
     * Returns the form layout for the given reference type and
     * datasource
     * 
     * @param datasource 
     * @param reftype 
     * @return {Promise}
     * @see DownloadController::actionFormLayout
     */
    formLayout : function(datasource, reftype){
      // @todo Document type for 'datasource' in app\modules\converters\controllers\DownloadController::actionFormLayout
      // @todo Document type for 'reftype' in app\modules\converters\controllers\DownloadController::actionFormLayout
      return qx.core.Init.getApplication().getRpcClient("download").send("form-layout", [datasource, reftype]);
    }
  }
});