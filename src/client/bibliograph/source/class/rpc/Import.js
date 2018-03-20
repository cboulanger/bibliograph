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
     * @see ImportController::actionGetTableLayout
     */
    getTableLayout : function(datasource){
      // @todo Document type for 'datasource' in app\controllers\ImportController::actionGetTableLayout
      return qx.core.Init.getApplication().getRpcClient("import").send("get-table-layout", [datasource]);
    },

    /**
     * Returns the list of import formats for a selectbox
     * 
     * @return {Promise}
     * @see ImportController::actionImportformats
     */
    importformats : function(){
      return qx.core.Init.getApplication().getRpcClient("import").send("importformats", []);
    }
  }
});