/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\DatasourceController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/DatasourceController.php
 */
qx.Class.define("rpc.Datasource",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @param namedId 
     * @param type 
     * @return {Promise}
     */
    create : function(namedId=null, type=null){


      return this.getApplication().getRpcClient("datasource").send("create", [namedId, type]);
    },

    /**
     * 
     * @return {Promise}
     */
    load : function(){
      return this.getApplication().getRpcClient("datasource").send("load", []);
    },

    /**
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("datasource").send("index", []);
    }
  }
});