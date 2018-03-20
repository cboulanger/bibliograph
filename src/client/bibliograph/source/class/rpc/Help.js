/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Changes the position of a folder within its siblings
 * 
 * @see app\controllers\HelpController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/HelpController.php
 */
qx.Class.define("rpc.Help",
{ 
  type: 'static',
  statics: {
    /**
     * Returns the html for the search help text
     * 
     * @param datasource {String} 
     * @return {Promise}
     * @see HelpController::actionSearch
     */
    search : function(datasource=null){
      qx.core.Assert.assertString(datasource);
      return this.getApplication().getRpcClient("help").send("search", [datasource]);
    },

    /**
     * @return {Promise}
     * @see HelpController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("help").send("index", []);
    }
  }
});