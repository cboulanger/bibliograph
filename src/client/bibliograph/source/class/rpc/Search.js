/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Class ProgressController
 * 
 * @see app\modules\z3950\controllers\SearchController
 * @file SearchController.php
 */
qx.Class.define("rpc.Search",
{ 
  type: 'static',
  statics: {
    /**
     * @return {Promise}
     * @see SearchController::actionTest
     */
    test : function(){
      return qx.core.Init.getApplication().getRpcClient("search").send("test", []);
    },

    /**
     * Executes a Z39.50 request on the remote server. Called
     * by the ServerProgress widget on the client. If server times out
     * it will retry up to three times.
     * 
     * @param datasource {String} The name of the datasource
     * @param query {String} The cql query
     * @param id {String} The id of the progress widget
     * @return {Promise}
     * @see SearchController::actionProgress
     */
    progress : function(datasource, query, id){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertString(query);
      qx.core.Assert.assertString(id);
      return qx.core.Init.getApplication().getRpcClient("search").send("progress", [datasource, query, id]);
    }
  }
});