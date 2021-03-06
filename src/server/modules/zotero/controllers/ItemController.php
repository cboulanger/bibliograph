<?php

namespace app\modules\zotero\controllers;

use GuzzleHttp\Exception\ConnectException;
use lib\controllers\IItemController;
use lib\controllers\ITableController;
use lib\exceptions\UserErrorException;
use Yii;

class ItemController
  extends Controller
  implements ITableController, IItemController
{

  const CACHE_LAST_CACHED_VERSION = "last-cached-version";
  const CACHE_LAST_MODIFIED_VERSION = "last-modified-version";
  const CACHE_ROWDATA = "row-data";

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
    $collectionKey = $queryData->query->relation->id ?? null;
    $api = $this->getZoteroApi($datasourceId);
    try {
      $response = $api
        ->collections($collectionKey)
        ->items()
        ->top()
        ->limit(1)
        ->send();
    } catch (ConnectException $e) {
      $this->throwConnectionError();
    }
    $headers = $response->getHeaders();
    $rowCount = (int) $headers['Total-Results'][0];
    $lastModifiedVersion = $headers['Last-Modified-Version'][0];
    self::setCached(self::CACHE_LAST_MODIFIED_VERSION, $datasourceId, $lastModifiedVersion);
    return [
      "rowCount" => $rowCount,
      'statusText' => Yii::t('plugin.zotero', "{numberOfRecords} records", ['numberOfRecords' => $rowCount])
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
    $collectionKey = $queryData->query->relation->id ?? null;
    $lastCachedVersion   = self::getCached(self::CACHE_LAST_CACHED_VERSION, $datasourceId);
    $lastModifiedVersion = self::getCached(self::CACHE_LAST_MODIFIED_VERSION, $datasourceId);
    $rowDataCacheId = self::CACHE_ROWDATA . "-$collectionKey-$firstRow-$lastRow";
    if ($lastModifiedVersion === $lastCachedVersion) {
      $cachedRowData = self::getCached($rowDataCacheId, $datasourceId);
      if ($cachedRowData) {
        return [
          'requestId' => $requestId,
          'rowData'   => $cachedRowData
        ];
      }
    } else {
      self::deleteCached($rowDataCacheId, $datasourceId);
    }
    $api = $this->getZoteroApi($datasourceId);
    try {
      $response = $api
        ->collections($collectionKey)
        ->items()
        ->top()
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
            $name = $curr['lastName'] . ", " . $curr['firstName'];
            return $prev ? "$prev; $name" : $name;
          }
          return "";
        },
        ""
      );
      $date = $item['data']['date'] ?? "";
      if ($date) {
        try {
          $date = (new \DateTime($date))->format("Y");
        } catch (\Exception $e) {}
      }
      $rowData[] = [
        'id'       => $item['data']['key'],
        'creators' => $creators,
        'date'     => $date,
        'title'    => $item['data']['title'] ?? ""
      ];
    }
    // cache
    self::setCached($rowDataCacheId, $datasourceId, $rowData);
    self::setCached(self::CACHE_LAST_CACHED_VERSION, $datasourceId, $lastModifiedVersion);
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
   * @param string $datasourceId
   * @param string $itemId
   * @return array
   */
  public function actionItemHtml($datasourceId, $itemId){
    $api = $this->getZoteroApi($datasourceId);
    try {
      $response = $api
        ->items($itemId)
        ->setPath($api->getPath() . "?include=bib,data&linkwrap=1")
        ->send();
    } catch (ConnectException $e) {
      $this->throwConnectionError();
    }
    $body = $response->getBody();
    $formatted = $body['bib'] ?? "";
    $abstract = $body['data']['abstractNote'] ?? "";
    $html = "<p>$formatted</p>";
    if ($abstract) {
      $html .= "<p><b>Abstract:</b> $abstract";
    }
    //$html = $response->getBody()['data'];
//    $item = $response->getBody(); //['data'];
//    $html = "<pre>" . json_encode($item, JSON_PRETTY_PRINT) . "</pre>";

//
//
//    // create html table
//    $html = "<table>";
//    foreach ($fields as $field) {
//      $value = $reference->$field;
//      if (!$value or !$schema->isPublicField($field)) continue;
//      $label = $modelClass::getSchema()->getFieldLabel($field, $reftype);
//
//      // special fields
//      switch ($field) {
//        case "reftype":
//          $value = $schema->getTypeLabel($value);
//          break;
//        case "url":
//          $urls = explode(";", $value);
//          $value = implode("<br/>", array_map(function ($url) {
//            return "<a href='$url' target='_blank'>$url</a>";
//          }, $urls));
//          break;
//      }
//
//      $html .= "<tr><td><b>$label</b></td><td>$value</td></tr>";
//    }
//    $html .= "</table>";
    return ['html' => $html];
  }

}
