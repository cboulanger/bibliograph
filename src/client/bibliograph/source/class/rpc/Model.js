qx.Class.define("rpc.Model",
{ 
  extend: qx.core.Object,
  statics: {

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("model").send("index", []);
    },
    ___eof : null
  }
});