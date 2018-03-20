/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\ImportController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/ImportController.php
 */
qx.Class.define("rpc.Import",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @param datasource 
     * @return {Promise}
     */
    getTableLayout : function(datasource=null){

      return this.getApplication().getRpcClient("import").send("get-table-layout", [datasource]);
    },

    /**
     * 
     * @return {Promise}
     */
    importformats : function(){
      return this.getApplication().getRpcClient("import").send("importformats", []);
    },

    /**
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("import").send("index", []);
    }
  }
});