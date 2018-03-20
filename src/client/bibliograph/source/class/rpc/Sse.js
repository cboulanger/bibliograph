qx.Class.define("rpc.Sse",
{ 
  extend: qx.core.Object,
  statics: {

    /**

     * @return {Promise}
     */
    test : function(){

      return this.getApplication().getRpcClient("sse").send("test", []);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("sse").send("index", []);
    },

    /**

     * @return {Promise}
     */
    time : function(){

      return this.getApplication().getRpcClient("sse").send("time", []);
    },
    ___eof : null
  }
});