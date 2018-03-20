qx.Class.define("rpc.RpcProxy",
{ 
  extend: qx.core.Object,
  statics: {

    /**

     * @return {Promise}
     */
    create : function(){

      return this.getApplication().getRpcClient("rpc-proxy").send("create", []);
    },
    ___eof : null
  }
});