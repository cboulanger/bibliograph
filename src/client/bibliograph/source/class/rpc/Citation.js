/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * The controller for PubSub communication
 * 
 * @see app\controllers\CitationController
 * @file CitationController.php
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
      return qx.core.Init.getApplication().getRpcClient("citation").send("style-data", []);
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
    renderItems : function(datasource, ids, style){
      qx.core.Assert.assertString(datasource);
      qx.core.Assert.assertArray(ids);
      qx.core.Assert.assertString(style);
      return qx.core.Init.getApplication().getRpcClient("citation").send("render-items", [datasource, ids, style]);
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
    renderFolder : function(datasource, folderId, style){
      // @todo Document type for 'datasource' in app\controllers\CitationController::actionRenderFolder
      // @todo Document type for 'folderId' in app\controllers\CitationController::actionRenderFolder
      // @todo Document type for 'style' in app\controllers\CitationController::actionRenderFolder
      return qx.core.Init.getApplication().getRpcClient("citation").send("render-folder", [datasource, folderId, style]);
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
    renderQuery : function(datasource, query, style){
      // @todo Document type for 'datasource' in app\controllers\CitationController::actionRenderQuery
      // @todo Document type for 'query' in app\controllers\CitationController::actionRenderQuery
      // @todo Document type for 'style' in app\controllers\CitationController::actionRenderQuery
      return qx.core.Init.getApplication().getRpcClient("citation").send("render-query", [datasource, query, style]);
    }
  }
});