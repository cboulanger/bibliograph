/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Service class providing methods to get or set configuration
 * values
 * 
 * @see app\controllers\AppController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/AppController.php
 */
qx.Class.define("rpc.App",
{ 
  type: 'static',
  statics: {
    /**
     * @return {Promise}
     * @see AppController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("app").send("index", []);
    }
  }
});