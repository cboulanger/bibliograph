/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\CitationController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/CitationController.php
 */
qx.Class.define("rpc.Citation",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @return {Promise}
     */
    styleData : function(){
      return this.getApplication().getRpcClient("citation").send("style-data", []);
    },

    /**
     * 
     * @param datasource 
     * @param ids 
     * @param style 
     * @return {Promise}
     */
    renderItems : function(datasource=null, ids=null, style=null){



      return this.getApplication().getRpcClient("citation").send("render-items", [datasource, ids, style]);
    },

    /**
     * 
     * @param datasource 
     * @param folderId 
     * @param style 
     * @return {Promise}
     */
    renderFolder : function(datasource=null, folderId=null, style=null){



      return this.getApplication().getRpcClient("citation").send("render-folder", [datasource, folderId, style]);
    },

    /**
     * 
     * @param datasource 
     * @param query 
     * @param style 
     * @return {Promise}
     */
    renderQuery : function(datasource=null, query=null, style=null){



      return this.getApplication().getRpcClient("citation").send("render-query", [datasource, query, style]);
    },

    /**
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("citation").send("index", []);
    }
  }
});