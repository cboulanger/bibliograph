qx.Class.define("rpc.Search",
{ 
  extend: qx.core.Object,
  statics: {

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("search").send("index", []);
    },

    /**

     * @return {Promise}
     */
    test : function(){

      return this.getApplication().getRpcClient("search").send("test", []);
    },

    /**
     * @param datasource
     * @param query
     * @param id
     * @return {Promise}
     */
    progress : function(datasource=null, query=null, id=null){



      return this.getApplication().getRpcClient("search").send("progress", [datasource, query, id]);
    },
    ___eof : null
  }
});