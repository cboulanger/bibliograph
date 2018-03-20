qx.Class.define("rpc.Table",
{ 
  extend: qx.core.Object,
  statics: {

    /**
     * @param datasource
     * @param modelClassType
     * @return {Promise}
     */
    tableLayout : function(datasource=null, modelClassType=null){


      return this.getApplication().getRpcClient("table").send("table-layout", [datasource, modelClassType]);
    },

    /**
     * @param activeOnly
     * @param reloadFromXmlFiles
     * @return {Promise}
     */
    serverList : function(activeOnly=null, reloadFromXmlFiles=null){


      return this.getApplication().getRpcClient("table").send("server-list", [activeOnly, reloadFromXmlFiles]);
    },

    /**
     * @param map
     * @return {Promise}
     */
    setDatasourceState : function(map=null){

      return this.getApplication().getRpcClient("table").send("set-datasource-state", [map]);
    },

    /**
     * @param queryData
     * @return {Promise}
     */
    rowCount : function(queryData){

      return this.getApplication().getRpcClient("table").send("row-count", [queryData]);
    },

    /**
     * @param firstRow {Number}
     * @param lastRow {Number}
     * @param requestId {Number}
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
    },
    ___eof : null
  }
});