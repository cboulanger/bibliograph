/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Creates rpc proxy stub methods
 * 
 * @see app\controllers\RpcProxyController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/RpcProxyController.php
 */
qx.Class.define("rpc.RpcProxy",
{ 
  type: 'static',
  statics: {
    /**
     * Creates stubs
     * 
     * @return {Promise}
     * @see RpcProxyController::actionCreate
     */
    create : function(){
      return this.getApplication().getRpcClient("rpc-proxy").send("create", []);
    }
  }
});