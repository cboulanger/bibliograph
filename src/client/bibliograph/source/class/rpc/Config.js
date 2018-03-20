/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\ConfigController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/ConfigController.php
 */
qx.Class.define("rpc.Config",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @param filter Filter
xxreturn \app\controllers\dto\ConfigLoadResult
     * @return {Promise}
     */
    load : function(filter=null){

      return this.getApplication().getRpcClient("config").send("load", [filter]);
    },

    /**
     * 
     * @param key Key
     * @param value Value
     * @return {Promise}
     */
    set : function(key=null, value=null){


      return this.getApplication().getRpcClient("config").send("set", [key, value]);
    },

    /**
     * 
     * @param key Key
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
    }
  }
});