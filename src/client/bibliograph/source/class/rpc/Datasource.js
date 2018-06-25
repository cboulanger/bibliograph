/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Service class providing methods to work with datasources.
 * 
 * @see app\controllers\DatasourceController
 * @file DatasourceController.php
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
    create : function(namedId, type){
      qx.core.Assert.assertString(namedId);
      // @todo Document type for 'type' in app\controllers\DatasourceController::actionCreate
      return qx.core.Init.getApplication().getRpcClient("datasource").send("create", [namedId, type]);
    },

    /**
     * Return the model for the datasource store
     * 
     * @return {Promise}
     * @see DatasourceController::actionLoad
     */
    load : function(){
      return qx.core.Init.getApplication().getRpcClient("datasource").send("load", []);
    }
  }
});