<?php

namespace app\modules\zotero\controllers;

use app\controllers\AppController;
use lib\controllers\ITableController;

class ItemController extends AppController implements ITableController {

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasource
   * @param null|string $modelClassType
   */
  public function actionTableLayout($datasource, $modelClassType = null){
    return [
      'columnLayout' => [
        'id' => [
          'header' => "ID",
          'width' => 50,
          'visible' => false
        ],
//        'markedDeleted'	=> array(
//        	'header' 		=> " ",
//        	'width'	 		=> 16
//        ),
        'creator' => [
          'header' => Yii::t('app', "Creator"),
          'width' => "1*"
        ],
        'year' => [
          'header' => Yii::t('app', "Year"),
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
          'name' => "folders",
          'foreignId' => 'FolderId'
        ],
        'orderBy' => "author,year,title",
      ],
      'addItems' => $this->getReferenceTypeListData($datasource)
    ];
  }

  /**
   * Returns count of rows that will be retrieved when executing the current
   * query.
   *
   * param object $queryData data to construct the query. Needs at least the
   * a string property "datasource" with the name of datasource and a property
   * "modelType" with the type of the model.
   * @throws \InvalidArgumentException
   */
  public function actionRowCount(\stdClass $clientQueryData){

  }

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
  function actionRowData(int $firstRow, int $lastRow, int $requestId, \stdClass $clientQueryData){

  }

}
