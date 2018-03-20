/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\RpcProxyController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/RpcProxyController.php
 */
qx.Class.define("rpc.RpcProxy",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @return {Promise}
     */
    create : function(){
      return this.getApplication().getRpcClient("rpc-proxy").send("create", []);
    }
  }
});