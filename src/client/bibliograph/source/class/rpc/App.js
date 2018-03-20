qx.Class.define("rpc.App",
{ 
  extend: qx.core.Object,
  statics: {

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("app").send("index", []);
    },
    ___eof : null
  }
});