qx.Class.define("rpc.Trash",
{ 
  extend: qx.core.Object,
  statics: {

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("trash").send("index", []);
    },
    ___eof : null
  }
});