/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Provides services based on a generic model API, using datasource
 * and modelType information
 * 
 * @see app\controllers\ModelController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/ModelController.php
 */
qx.Class.define("rpc.Model",
{ 
  type: 'static',
  statics: {
    /**
     * @return {Promise}
     * @see ModelController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("model").send("index", []);
    }
  }
});