/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
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
     */
    index : function(){
      return this.getApplication().getRpcClient("model").send("index", []);
    }
  }
});