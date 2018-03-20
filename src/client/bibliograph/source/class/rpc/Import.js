/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * 
 * @see app\controllers\ImportController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/ImportController.php
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
    getTableLayout : function(datasource=null){
      // @todo Document type for 'datasource' in app\controllers\ImportController::actionGetTableLayout
      return this.getApplication().getRpcClient("import").send("get-table-layout", [datasource]);
    },

    /**
     * Returns the list of import formats for a selectbox
     * 
     * @return {Promise}
     * @see ImportController::actionImportformats
     */
    importformats : function(){
      return this.getApplication().getRpcClient("import").send("importformats", []);
    },

    /**
     * @return {Promise}
     * @see ImportController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("import").send("index", []);
    }
  }
});