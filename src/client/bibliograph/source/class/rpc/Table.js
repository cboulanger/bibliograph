/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\modules\z3950\controllers\TableController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/../modules/z3950/controllers/TableController.php
 */
qx.Class.define("rpc.Table",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @param datasource 
     * @param modelClassType 
     * @return {Promise}
     */
    tableLayout : function(datasource=null, modelClassType=null){


      return this.getApplication().getRpcClient("table").send("table-layout", [datasource, modelClassType]);
    },

    /**
     * 
     * @param activeOnly 
     * @param reloadFromXmlFiles Whether to reload the list from the XML Explain files in the filesystem.
This is neccessary if xml files have been added or removed.
     * @return {Promise}
     */
    serverList : function(activeOnly=null, reloadFromXmlFiles=null){


      return this.getApplication().getRpcClient("table").send("server-list", [activeOnly, reloadFromXmlFiles]);
    },

    /**
     * 
     * @param map 
     * @return {Promise}
     */
    setDatasourceState : function(map=null){

      return this.getApplication().getRpcClient("table").send("set-datasource-state", [map]);
    },

    /**
     * 
     * @param queryData 
     * @return {Promise}
     */
    rowCount : function(queryData){

      return this.getApplication().getRpcClient("table").send("row-count", [queryData]);
    },

    /**
     * 
     * @param firstRow {Number} First row of queried data
     * @param lastRow {Number} Last row of queried data
     * @param requestId {Number} Request id, deprecated
param object $queryData an array of the structure array(
  'datasource' => datasource name
  'query'      => array(
     'properties'  => array("a","b","c"),
     'orderBy'     => array("a"),
     'cql'         => "the string query (ccl/cql format)"
  )
)
return array Array containing the keys
               int     requestId   The request id identifying the request (mandatory)
               array   rowData     The actual row data (mandatory)
               string  statusText  Optional text to display in a status bar
     * @param queryData 
     * @return {Promise}
     */
    rowData : function(firstRow, lastRow, requestId, queryData){
      qx.core.Assert.assertNumber(firstRow);
      qx.core.Assert.assertNumber(lastRow);
      qx.core.Assert.assertNumber(requestId);

      return this.getApplication().getRpcClient("table").send("row-data", [firstRow, lastRow, requestId, queryData]);
    },

    /**
     * 
     * @param sourceDatasource {String} 
     * @param ids {Array} 
     * @param targetDatasource {String} 
     * @param targetFolderId {Number} 
     * @return {Promise}
     */
    import : function(sourceDatasource, ids, targetDatasource, targetFolderId){
      qx.core.Assert.assertString(sourceDatasource);
      qx.core.Assert.assertArray(ids);
      qx.core.Assert.assertString(targetDatasource);
      qx.core.Assert.assertNumber(targetFolderId);
      return this.getApplication().getRpcClient("table").send("import", [sourceDatasource, ids, targetDatasource, targetFolderId]);
    },

    /**
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("table").send("index", []);
    }
  }
});