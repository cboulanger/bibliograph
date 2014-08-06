<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */


/**
 * Interface for a controller that supplies data for a
 * Table Widget
 *
 */
interface qcl_data_controller_ITableController
{

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasource
   * @return array An arrary
   * @todo: specify return array
   */
  function method_getTableLayout( $datasource );

  /**
   * Returns the number of rows displayed in the table,
   * according to the query data
   *
   * @param object $queryData
   * @return int
   */
  function method_getRowCount( $queryData );

  /**
   * Returns row data executing a constructed query
   *
   * @param integer $firstRow first row of queried data
   * @param integer $lastRow last row of queried data
   * @param object $queryData data to construct the query. Needs at least the following properties:
   *                (string) datasource name of datasource
   *                (string) modelType type of the model
   *                (object) query A qcl_data_db_Query- compatible object
   * @internal param string $requestId Request id
   * @return array Array containing the keys
   *                (int) requestId The request id identifying the request (mandatory)
   *                (array) rowData The actual row data (mandatory)
   *                (string) statusText Optional text to display in a status bar
   */
  function method_getRowData( $firstRow, $lastRow, $queryData=null );
}
