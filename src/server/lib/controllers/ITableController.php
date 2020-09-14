<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2018 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace lib\controllers;

/**
 * Interface for a controller that supplies data for a
 * Table Widget
 *
 */
interface ITableController
{

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   *
   * @param $datasourceName
   * @param null|string $modelClassType
   * @return array ['columnLayout' => [], 'queryData' => [], 'addItens' => []]
   */
  public function actionTableLayout($datasourceName, $modelClassType = null);

  /**
   * Returns count of rows that will be retrieved when executing the current
   * query.
   *
   * param object $queryData data to construct the query. Needs at least the
   * a string property "datasource" with the name of datasource and a property
   * "modelType" with the type of the model.
   * @throws \InvalidArgumentException
   */
  public function actionRowCount(\stdClass $clientQueryData);

 /**
  * Returns row data executing a constructed query
  *
  * @param int $firstRow First row of queried data
  * @param int $lastRow Last row of queried data
  * @param int $requestId Request id
  * param object $queryData Data to construct the query
  * @throws \InvalidArgumentException
  * return array Array containing the keys
  *                int     requestId   The request id identifying the request (mandatory)
  *                array   rowData     The actual row data (mandatory)
  *                string  statusText  Optional text to display in a status bar
  */
  function actionRowData(int $firstRow, int $lastRow, int $requestId, \stdClass $clientQueryData);
}
