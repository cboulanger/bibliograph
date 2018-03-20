/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Service class providing methods to work with datasources.
 * 
 * @see app\controllers\DatasourceController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/DatasourceController.php
 */
qx.Class.define("rpc.Datasource",
{ 
  type: 'static',
  statics: {
    /**
     * Creates a dasource with the given name, of the default type that the
     * application supports
     * 
     * @param namedId {String} 
     * @param type 
     * @return {Promise}
     * @see DatasourceController::actionCreate
     */
    create : function(namedId=null, type=null){
      qx.core.Assert.assertString(namedId);
      // @todo Document type for 'type' in app\controllers\DatasourceController::actionCreate
      return this.getApplication().getRpcClient("datasource").send("create", [namedId, type]);
    },

    /**
     * Return the model for the datasource store
     * 
     * @return {Promise}
     * @see DatasourceController::actionLoad
     */
    load : function(){
      return this.getApplication().getRpcClient("datasource").send("load", []);
    },

    /**
     * @return {Promise}
     * @see DatasourceController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("datasource").send("index", []);
    }
  }
});