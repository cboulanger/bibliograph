<?php

namespace app\modules\webservices\controllers;

use app\controllers\AppController;
use app\models\{
  Datasource, Folder, Reference
};
use app\modules\webservices\models\{
  Datasource as WebservicesDatasource, Record, Result, Search
};
use app\modules\webservices\Module;
use lib\exceptions\UserErrorException;
use Yii;
use yii\db\Exception;
use yii\db\Expression;


/**
 * Default controller for the `webservices` module
 * @property Module $module
 */
class TableController extends AppController
{


  /**
   * The main model type of this controller
   */
  static $modelType = "record";

  //---------------------------------------------------------------------------
  //  ACTIONS
  //---------------------------------------------------------------------------


  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param string $datasource
   * @param null|string $modelClassType
   */
  public function actionTableLayout($datasource, $modelClassType = null)
  {
    return [
      'columnLayout' => [
        'id' => [
          'header' => "ID",
          'width' => 50,
          'visible' => false
        ],
        'creator' => [
          'header' => _("Creator"),
          'width' => "1*"
        ],
        'year' => [
          'header' => _("Year"),
          'width' => 50
        ],
        'title' => [
          'header' => _("Title"),
          'width' => "3*"
        ]
      ],
      'queryData' => [
        'link' => [],
        'orderBy' => "author,year,title",
      ],
      'addItems' => []
    ];
  }

  /**
   * Service method that returns ListItem model data on the available library servers
   * @param boolean $all
   *      Whether to return only the active datasources (default) or all datasource
   * @param boolean $reloadFromXmlFiles
   *      Whether to reload the list from the XML Explain files in the filesystem.
   *      This is neccessary if xml files have been added or removed.
   * @throws UserErrorException
   */
  public function actionServerList($activeOnly = true, $reloadFromXmlFiles = false)
  {
    // Reset list of Datasources
    if ($reloadFromXmlFiles) {
      try {
        $this->module->createDatasources();
      } catch (\Throwable $e) {
        throw new UserErrorException($e->getMessage(), null, $e);
      }
    }
    // Return list of Datasources
    $list = [];
    $query = Datasource::find()
      ->select("title as label, namedId as value, description, active")
      ->where(['schema'=> 'webservices'])
      ->orderBy("value")
      ->asArray();
    if( $activeOnly ) $query = $query->andWhere(['active'=>1]);
    return array_merge($list,$query->all());
  }

  /**
   * Sets datasources active / inactive, so that they do not show up in the
   * list of servers
   * param array $map Maps datasource ids to status
   * @throws \lib\exceptions\Exception
   * @throws UserErrorException
   * @todo add DTO
   */
  public function actionSetDatasourceState($map)
  {
    $this->requirePermission("webservices.manage");
    foreach ((array)$map as $namedId => $active) {
      $datasource = WebservicesDatasource::findByNamedId($namedId);
      $datasource->active = (int)$active;
      try {
        $datasource->save();
      } catch (\yii\db\Exception $e) {
        throw new UserErrorException($e->getMessage(), $e->getCode(), $e);
      }
    }
    $this->broadcastClientMessage("plugins.webservices.reloadDatasources");
    return "OK";
  }

  /**
   * Returns count of rows that will be retrieved when executing the current
   * query.
   *
   * param object $queryData an object of the structure array(
   *   'datasource' => datasource name
   *   'query'      => array(
   *      'properties'  =>
   *      'orderBy'     =>
   *      'cql'         => "the string query (ccl/cql format)"
   *   )
   * )
   * return array ( 'rowCount' => row count )
   */
  function actionRowCount(\stdClass $queryData)
  {
    $datasourceName =  $queryData->datasource;
    $datasource = Datasource::getInstanceFor( $datasourceName );
    $query = $this->module->getQueryString($queryData);
    Search::setDatasource($datasource);
    //Yii::debug("Row count query for datasource '$datasourceName', query '$query'", Module::CATEGORY, __METHOD__);
    $search = Search::findOne([
      'query' => $query,
      'datasource' => $datasourceName
    ]);
    if (!$search) {
      throw new UserErrorException(Yii::t(Module::CATEGORY, "No search data exists."));
    }
    $hits = $search->hits;
    Yii::debug("$hits hits.", Module::CATEGORY);
    return array(
      'rowCount' => $hits,
      'statusText' => Yii::t(Module::CATEGORY, "{number} hits", ['number' => $hits])
    );
  }

  /**
   * Returns row data executing a constructed query
   *
   * @param int $firstRow First row of queried data
   * @param int $lastRow Last row of queried data
   * @param int $requestId Request id, deprecated
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
   * @todo Add DTOs
   */
  function actionRowData(int $firstRow, int $lastRow, int $requestId, \stdClass $queryData)
  {
    $datasourceName = $queryData->datasource;
    $datasource = Datasource::getInstanceFor( $datasourceName );
    // set datasource table prefixes
    Search::setDatasource($datasource);
    Record::setDatasource($datasource);

    $query = $this->module->getQueryString($queryData);
    $properties = $queryData->query->properties;
    $hasCreatorProperty = array_search("creator",$properties) !== false;
    if( $hasCreatorProperty ){
      $properties = array_merge(
        ['author','editor',new Expression('coalesce(`author`,`editor`) as creator')],
        array_diff($properties,['creator'])
      );
    }
    //Yii::debug("Row data query for datasource '$datasourceName', query '$query'.", Module::CATEGORY, __METHOD__);
    $search = Search::findOne([
      'query' => $query,
      'datasource' => $datasourceName
    ]);
    if (!$search) {
      Yii::debug("Cache says we have no entry for query '$query'. Aborting.", Module::CATEGORY);
      throw new \RuntimeException(Yii::t(Module::CATEGORY, "No search data exists."));
    }
    $hits = $search->hits;
    Yii::debug("Cache says we have $hits hits for query '$query'.", Module::CATEGORY);

    // try to find already downloaded records and return them as rowData
    $searchId = $search->id;
    $lastRow = max($lastRow, $hits - 1);

    Yii::debug("Getting records from cache for search #$searchId, rows $firstRow-$lastRow...", Module::CATEGORY);

    // get row data from cache
    $rowData = Record::find()
      ->select($properties)
      ->where(['SearchId' => $searchId])
      ->orderBy('quality DESC')
      ->offset($firstRow)
      ->limit($lastRow-$firstRow+1)
      ->asArray()
      ->all();
    return array(
      'requestId' => $requestId,
      'rowData' => $rowData,
      'statusText' => "Loaded rows $firstRow-$lastRow."
    );
  }

  /**
   * Imports the found references into the main datasource
   * @param string $sourceDatasource
   * @param $ids
   * @param string $targetDatasource
   * @param int $targetFolderId
   * @return string Diagnostic Message
   * @throws UserErrorException
   */
  public function actionImport(string $sourceDatasource, array $ids, string $targetDatasource, int $targetFolderId)
  {
    /** @var Folder $targetFolderModel */
    $targetFolderModel = Datasource::in($targetDatasource, "folder")::findOne($targetFolderId);
    if (!$targetFolderModel) {
      throw new UserErrorException("Invalid folder id #$targetFolderId");
    }
    // import
    $numberImported = 0;
    foreach ($ids as $id) {
      // source
      $sourceModelClass = Datasource::in($sourceDatasource, "record");
      $sourceColumns = $sourceModelClass::getTableSchema()->columnNames;
      /** @var Record $sourceModel */
      $sourceModel = $sourceModelClass::findOne(['id' => $id]);
      if (!$sourceModel) {
        $dsn = $sourceModelClass::getDb()->dsn;
        throw new \RuntimeException("No record with id $id exists in datasource '$sourceDatasource' ($dsn).");
      }
      $copiedAttributes = $sourceModel->getAttributes();
      // target
      $targetModelClass = Datasource::in($targetDatasource, "reference");
      $targetColumns = $targetModelClass::getTableSchema()->columnNames;
      // common columns
      $commonColumns = array_intersect($sourceColumns,$targetColumns);
      $commonColumns = array_diff($commonColumns,['id','created','modified']);

      $copiedAttributes = array_filter( $copiedAttributes, function($key) use($commonColumns) {
        return in_array($key, $commonColumns);
      }, ARRAY_FILTER_USE_KEY);
      //Yii::debug("Copying over columns " . implode(', ', $commonColumns) );

      /** @var Reference $targetModel */
      $targetModel = new $targetModelClass($copiedAttributes);
      $targetModel->citekey = substr( $targetModel->computeCiteKey(), 0, 50);
      // remove leading "c" and other characters in year data
      $year = $targetModel->year;
      if ($year[0] == "c") {
        $year = trim(substr($year, 1));
      }
      $year = preg_replace("/[\{\[\\]\}\(\)]/", '', $year);
      $targetModel->year = $year;
      try {
        $targetModel->save();
        $targetModel->link('folders', $targetFolderModel);
        $numberImported++;
      } catch (\Exception $e) {
        Yii::error($e->getMessage());
      }
    }
    // update reference count
    $referenceCount = $targetFolderModel->getReferences()->count();
    $targetFolderModel->referenceCount = $referenceCount;
    try {
      $targetFolderModel->save();
    } catch (Exception $e) {
      Yii::error($e->getMessage());
    }

    // reload references and select the new reference
    $this->dispatchClientMessage("folder.reload", array(
      'datasource' => $targetDatasource,
      'folderId' => $targetFolderId
    ));

    return "Imported $numberImported references";
  }

  /**
   * Returns an empty rowData response with the error message as status text.
   * @param $requestId
   * @param $error
   * @return array
   */
  protected function rowDataError($requestId, $error)
  {
    return array(
      'requestId' => $requestId,
      'rowData' => array(),
      'statusText' => $error
    );
  }
}
