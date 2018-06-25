/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Creates rpc proxy stub methods
 * 
 * @see app\controllers\RpcProxyController
 * @file RpcProxyController.php
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
      return qx.core.Init.getApplication().getRpcClient("rpc-proxy").send("create", []);
    }
  }
});