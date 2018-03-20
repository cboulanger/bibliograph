qx.Class.define("rpc.Import",
{ 
  extend: qx.core.Object,
  statics: {

    /**
     * @param datasource
     * @return {Promise}
     */
    getTableLayout : function(datasource=null){

      return this.getApplication().getRpcClient("import").send("get-table-layout", [datasource]);
    },

    /**

     * @return {Promise}
     */
    importformats : function(){

      return this.getApplication().getRpcClient("import").send("importformats", []);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("import").send("index", []);
    },
    ___eof : null
  }
});