qx.Class.define("rpc.Help",
{ 
  extend: qx.core.Object,
  statics: {

    /**
     * @param datasource
     * @return {Promise}
     */
    search : function(datasource=null){

      return this.getApplication().getRpcClient("help").send("search", [datasource]);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("help").send("index", []);
    },
    ___eof : null
  }
});