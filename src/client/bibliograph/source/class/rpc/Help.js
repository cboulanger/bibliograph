/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Changes the position of a folder within its siblings
 * 
 * @see app\controllers\HelpController
 * @file HelpController.php
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
    search : function(datasource){
      qx.core.Assert.assertString(datasource);
      return qx.core.Init.getApplication().getRpcClient("help").send("search", [datasource]);
    }
  }
});