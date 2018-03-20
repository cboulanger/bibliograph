/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\TrashController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/TrashController.php
 */
qx.Class.define("rpc.Trash",
{ 
  type: 'static',
  statics: {
    /**
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("trash").send("index", []);
    }
  }
});