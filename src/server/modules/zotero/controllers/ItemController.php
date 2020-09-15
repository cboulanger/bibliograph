<?php

namespace app\modules\zotero\controllers;

use GuzzleHttp\Exception\ConnectException;
use lib\controllers\IItemController;
use lib\controllers\ITableController;
use Yii;

class ItemController
  extends Controller
  implements ITableController, IItemController
{

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasourceName
   * @param null|string $modelClassType
   */
  public function actionTableLayout($datasourceName, $modelClassType = null){
    return [
      'columnLayout' => [
        'id' => [
          'header' => "ID",
          'width' => 50,
          'visible' => false
        ],
        'creators' => [
          'header' => Yii::t('app', "Creator"),
          'width' => "1*"
        ],
        'date' => [
          'header' => Yii::t('app', "Date"),
          'width' => 50
        ],
        'title' => [
          'header' => Yii::t('app', "Title"),
          'width' => "3*"
        ]
      ],

      /**
       * This will feed back into addQueryConditions()
       * @todo implement differently
       */
      'queryData' => [
        'relation' => [
          'name' => "collections",
          'foreignId' => ''
        ],
        'orderBy' => "creators,date,title",
      ],
      'addItems' => []
    ];
  }

  /**
   * Returns count of rows that will be retrieved when executing the current
   * query.
   *
   * @param object $queryData data to construct the query. Needs at least the
   * a string property "datasource" with the name of datasource and a property
   * "modelType" with the type of the model.
   * @throws \InvalidArgumentException
   */
  public function actionRowCount(\stdClass $queryData){
    $datasourceId = $queryData->datasource;
    $collectionKey = $queryData->query->relation->id;
    $api = $this->getZoteroApi($datasourceId);
    try {
      $response = $api
        ->collections($collectionKey)
        ->items()
        ->limit(1)
        ->send();
    } catch (ConnectException $e) {
      $this->throwConnectionError();
    }
    $rowCount = (int) $response->getHeaders()['Total-Results'][0];
    return [
      "rowCount" => $rowCount,
      'statusText' => Yii::t('app', "{numberOfRecords} records", ['numberOfRecords' => $rowCount])
    ];
  }

  /**
   * Returns row data executing a constructed query
   *
   * @param int $firstRow First row of queried data
   * @param int $lastRow Last row of queried data
   * @param int $requestId Request id
   * @param object $queryData Data to construct the query
   * @throws \InvalidArgumentException
   * return array Array containing the keys
   *                int     requestId   The request id identifying the request (mandatory)
   *                array   rowData     The actual row data (mandatory)
   *                string  statusText  Optional text to display in a status bar
   */
  function actionRowData(int $firstRow, int $lastRow, int $requestId, \stdClass $queryData){
    $datasourceId = $queryData->datasource;
    $collectionKey = $queryData->query->relation->id;
    $api = $this->getZoteroApi($datasourceId);
    try {
      $response = $api
        ->collections($collectionKey)
        ->items()
        ->start($firstRow)
        ->limit($lastRow-$firstRow+1)
        ->send();
    } catch (ConnectException $e) {
      $this->throwConnectionError();
    }
    $items = $response->getBody();
    $rowData = [];
    foreach ($items as $item) {
      $creators = array_reduce(
        $item['data']['creators'] ?? [],
        function ($prev, $curr){
          if (isset($curr['lastName']) && isset($curr['lastName'])) {
            $name = $curr['lastName'] . ", " . $curr['lastName'];
            return $prev ? "$prev; $name" : $name;
          }
          return "";
        },
        ""
      );
      $rowData[] = [
        'creators' => $creators,
        'date'     => $item['data']['date'] ?? "",
        'title'    => $item['data']['title'] ?? ""
      ];
    }
    return [
      'requestId' => $requestId,
      'rowData'   => $rowData
    ];
  }

  /**
   * Returns the requested or all accessible properties of a reference
   * @param string $datasourceId
   * @param string $arg2 the id of the reference
   * @return array
   * @throws \InvalidArgumentException
   */
  function actionItem($datasourceId, $arg2, $arg3 = null, $arg4 = null){
    throw new \BadMethodCallException("Not implemented");
  }

  /**
   * Returns a HTML table with the reference data
   * @param string $datasource
   * @param int $id
   * @return array
   */
  public function actionItemHtml($datasource, $id){
    return ['html' => "Hello World"];
  }

}
