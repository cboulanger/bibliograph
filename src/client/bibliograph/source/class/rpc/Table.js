/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Default controller for the `z3950` module
 * 
 * @see app\modules\z3950\controllers\TableController
 * @file TableController.php
 */
qx.Class.define("rpc.Table",
{ 
  type: 'static',
  statics: {
    /**
     * Returns the layout of the columns of the table displaying
     * the records
     * 
     * @param datasource {String} 
     * @param modelClassType {|string} 
     * @return {Promise}
     * @see TableController::actionTableLayout
     */
    tableLayout : function(datasource, modelClassType){
      qx.core.Assert.assertString(datasource);
      // @todo Document type for 'modelClassType' in app\modules\z3950\controllers\TableController::actionTableLayout
      return qx.core.Init.getApplication().getRpcClient("table").send("table-layout", [datasource, modelClassType]);
    },

    /**
     * Service method that returns ListItem model data on the available library servers
     * 
     * @param activeOnly 
     * @param reloadFromXmlFiles {Boolean} Whether to reload the list from the XML Explain files in the filesystem.
     * This is neccessary if xml files have been added or removed.
     * @return {Promise}
     * @see TableController::actionServerList
     */
    serverList : function(activeOnly, reloadFromXmlFiles){
      // @todo Document type for 'activeOnly' in app\modules\z3950\controllers\TableController::actionServerList
      qx.core.Assert.assertBoolean(reloadFromXmlFiles);
      return qx.core.Init.getApplication().getRpcClient("table").send("server-list", [activeOnly, reloadFromXmlFiles]);
    },

    /**
     * Sets datasources active / inactive, so that they do not show up in the
     * list of servers
     * param array $map Maps datasource ids to status
     * 
     * @param map 
     * @return {Promise}
     * @see TableController::actionSetDatasourceState
     */
    setDatasourceState : function(map){
      // @todo Document type for 'map' in app\modules\z3950\controllers\TableController::actionSetDatasourceState
      return qx.core.Init.getApplication().getRpcClient("table").send("set-datasource-state", [map]);
    },

    /**
     * Returns count of rows that will be retrieved when executing the current
     * query.
     * param object $queryData an object of the structure array(
     *   'datasource' => datasource name
     *   'query'      => array(
     *      'properties'  =>
     *      'orderBy'     =>
     *      'cql'         => "the string query (ccl/cql format)"
     *   )
     * )
     * return array ( 'rowCount' => row count )
     * @param queryData 
     * @return {Promise}
     * @see TableController::actionRowCount
     */
    rowCount : function(queryData){
      // @todo Document type for 'queryData' in app\modules\z3950\controllers\TableController::actionRowCount
      return qx.core.Init.getApplication().getRpcClient("table").send("row-count", [queryData]);
    },

    /**
     * Returns row data executing a constructed query
     * 
     * @param firstRow {Number} First row of queried data
     * @param lastRow {Number} Last row of queried data
     * @param requestId {Number} Request id, deprecated
     * param object $queryData an array of the structure array(
     *   'datasource' => datasource name
     *   'query'      => array(
     *      'properties'  => array("a","b","c"),
     *      'orderBy'     => array("a"),
     *      'cql'         => "the string query (ccl/cql format)"
     *   )
     * )
     * return array Array containing the keys
     *                int     requestId   The request id identifying the request (mandatory)
     *                array   rowData     The actual row data (mandatory)
     *                string  statusText  Optional text to display in a status bar
     * @param queryData 
     * @return {Promise}
     * @see TableController::actionRowData
     */
    rowData : function(firstRow, lastRow, requestId, queryData){
      qx.core.Assert.assertNumber(firstRow);
      qx.core.Assert.assertNumber(lastRow);
      qx.core.Assert.assertNumber(requestId);
      // @todo Document type for 'queryData' in app\modules\z3950\controllers\TableController::actionRowData
      return qx.core.Init.getApplication().getRpcClient("table").send("row-data", [firstRow, lastRow, requestId, queryData]);
    },

    /**
     * Imports the found references into the main datasource
     * 
     * @param sourceDatasource {String} 
     * @param ids {Array} 
     * @param targetDatasource {String} 
     * @param targetFolderId {Number} 
     * @return {Promise}
     * @see TableController::actionImport
     */
    import : function(sourceDatasource, ids, targetDatasource, targetFolderId){
      qx.core.Assert.assertString(sourceDatasource);
      qx.core.Assert.assertArray(ids);
      qx.core.Assert.assertString(targetDatasource);
      qx.core.Assert.assertNumber(targetFolderId);
      return qx.core.Init.getApplication().getRpcClient("table").send("import", [sourceDatasource, ids, targetDatasource, targetFolderId]);
    }
  }
});