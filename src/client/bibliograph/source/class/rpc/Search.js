/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\modules\z3950\controllers\SearchController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/../modules/z3950/controllers/SearchController.php
 */
qx.Class.define("rpc.Search",
{ 
  type: 'static',
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
     * 
     * @param datasource The name of the datasource
     * @param query The cql query
     * @param id The id of the progress widget
     * @return {Promise}
     */
    progress : function(datasource=null, query=null, id=null){



      return this.getApplication().getRpcClient("search").send("progress", [datasource, query, id]);
    }
  }
});