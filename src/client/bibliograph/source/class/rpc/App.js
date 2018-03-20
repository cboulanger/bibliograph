/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
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
     */
    index : function(){
      return this.getApplication().getRpcClient("app").send("index", []);
    }
  }
});