qx.Class.define("rpc.Config",
{ 
  extend: qx.core.Object,
  statics: {

    /**
     * @param filter
     * @return {Promise}
     */
    load : function(filter=null){

      return this.getApplication().getRpcClient("config").send("load", [filter]);
    },

    /**
     * @param key
     * @param value
     * @return {Promise}
     */
    set : function(key=null, value=null){


      return this.getApplication().getRpcClient("config").send("set", [key, value]);
    },

    /**
     * @param key
     * @return {Promise}
     */
    get : function(key=null){

      return this.getApplication().getRpcClient("config").send("get", [key]);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("config").send("index", []);
    },
    ___eof : null
  }
});