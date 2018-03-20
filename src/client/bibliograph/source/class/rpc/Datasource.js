qx.Class.define("rpc.Datasource",
{ 
  extend: qx.core.Object,
  statics: {

    /**
     * @param namedId
     * @param type
     * @return {Promise}
     */
    create : function(namedId=null, type=null){


      return this.getApplication().getRpcClient("datasource").send("create", [namedId, type]);
    },

    /**

     * @return {Promise}
     */
    load : function(){

      return this.getApplication().getRpcClient("datasource").send("load", []);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("datasource").send("index", []);
    },
    ___eof : null
  }
});