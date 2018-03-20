/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\HelpController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/HelpController.php
 */
qx.Class.define("rpc.Help",
{ 
  type: 'static',
  statics: {
    /**
     * 
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
    }
  }
});