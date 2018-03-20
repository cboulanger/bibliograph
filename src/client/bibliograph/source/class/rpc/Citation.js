/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * The class used for authentication of users. Adds LDAP authentication
 * 
 * @see app\controllers\CitationController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/CitationController.php
 */
qx.Class.define("rpc.Citation",
{ 
  type: 'static',
  statics: {
    /**
     * Action to return model data on the available csl styles
     * 
     * @return {Promise}
     * @see CitationController::actionStyleData
     */
    styleData : function(){
      return this.getApplication().getRpcClient("citation").send("style-data", []);
    },

    /**
     * Render the given references in the given formatting style.
     * 
     * @param datasource {String} 
     * @param ids {Array} 
     * @param style {String} 
     * @return {Promise}
     * @see CitationController::actionRenderItems
     */
    renderItems : function(datasource=null, ids=null, style=null){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertArray(ids);
      qx.core.Assert.assertString(style);
      return this.getApplication().getRpcClient("citation").send("render-items", [datasource, ids, style]);
    },

    /**
     * Render the full content of a folder
     * 
     * @param datasource 
     * @param folderId 
     * @param style 
     * @return {Promise}
     * @see CitationController::actionRenderFolder
     */
    renderFolder : function(datasource=null, folderId=null, style=null){
      // @todo Document type for 'datasource' in app\controllers\CitationController::actionRenderFolder
      // @todo Document type for 'folderId' in app\controllers\CitationController::actionRenderFolder
      // @todo Document type for 'style' in app\controllers\CitationController::actionRenderFolder
      return this.getApplication().getRpcClient("citation").send("render-folder", [datasource, folderId, style]);
    },

    /**
     * process the result of a query
     * 
     * @param datasource 
     * @param query 
     * @param style 
     * @return {Promise}
     * @see CitationController::actionRenderQuery
     */
    renderQuery : function(datasource=null, query=null, style=null){
      // @todo Document type for 'datasource' in app\controllers\CitationController::actionRenderQuery
      // @todo Document type for 'query' in app\controllers\CitationController::actionRenderQuery
      // @todo Document type for 'style' in app\controllers\CitationController::actionRenderQuery
      return this.getApplication().getRpcClient("citation").send("render-query", [datasource, query, style]);
    },

    /**
     * @return {Promise}
     * @see CitationController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("citation").send("index", []);
    }
  }
});