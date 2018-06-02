/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Returns the form layout for the given reference type and
 * datasource
 * 
 * @see app\modules\converters\controllers\ExportController
 * @file ExportController.php
 */
qx.Class.define("rpc.Export",
{ 
  type: 'static',
  statics: {
    /**
     * Returns a dialog to export references
     * 
     * @param datasource {String} 
     * @param selector {String} 
     * @return {Promise}
     * @see ExportController::actionFormatDialog
     */
    formatDialog : function(datasource, selector){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertString(selector);
      return qx.core.Init.getApplication().getRpcClient("export").send("format-dialog", [datasource, selector]);
    },

    /**
     * Handles the dialog response.
     * 
     * @param data 
     * @param datasource {String} 
     * @param selector {String} 
     * @return {Promise}
     * @see ExportController::actionHandleDialogResponse
     */
    handleDialogResponse : function(data, datasource, selector){
      // @todo Document type for 'data' in app\modules\converters\controllers\ExportController::actionHandleDialogResponse
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertString(selector);
      return qx.core.Init.getApplication().getRpcClient("export").send("handle-dialog-response", [data, datasource, selector]);
    },

    /**
     * Service to create a file with the export data for download by the client.
     * Dispatches a message which will trigger the download
     * @param dummy Default response parameter from dialog widget, can be discarded
     * @param shelfId 
     * @return {Promise}
     * @see ExportController::actionStartExport
     */
    startExport : function(dummy, shelfId){
      // @todo Document type for 'dummy' in app\modules\converters\controllers\ExportController::actionStartExport
      // @todo Document type for 'shelfId' in app\modules\converters\controllers\ExportController::actionStartExport
      return qx.core.Init.getApplication().getRpcClient("export").send("start-export", [dummy, shelfId]);
    }
  }
});