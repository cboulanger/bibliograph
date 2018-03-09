<?php

namespace app\modules\z3950\controllers;

use app\models\Folder;
use app\models\Reference;
use app\modules\z3950\models\Record;
use app\modules\z3950\models\Result;
use app\modules\z3950\models\Search;
use Yii;
use app\controllers\AppController;
use app\models\Datasource;
use app\modules\z3950\models\Datasource as Z3950Datasource;
use app\modules\z3950\Module;
use lib\exceptions\UserErrorException;
use yii\db\Exception;

/**
 * Default controller for the `z3950` module
 * @property Module $module
 */
class SearchController extends AppController
{
  /**
   * Returns the default model type for which this controller is providing
   * data.
   * @return string
   */
  protected function getModelType()
  {
    return "record";
  }
  
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
    return array(
      'columnLayout' => array(
        'id' => array(
          'header'  => "ID",
          'width'   => 50,
          'visible' => false
        ),
        'author' => array(
          'header'  => _("Author"),
          'width'   => "1*"
        ),
        'year' => array(
          'header'  => _("Year"),
          'width'   => 50
        ),
        'title' => array(
          'header'  => _("Title"),
          'width'   => "3*"
        )
      ),
      'queryData' => array(
        'link'    => array(),
        'orderBy' => "author,year,title",
      ),
      'addItems' => array()
    );
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
  public function actionServerListItems($activeOnly=true,$reloadFromXmlFiles=false)
  {
    // Reset list of Datasources
    if ( $reloadFromXmlFiles )
    {
      try {
        $this->module->createDatasources();
      } catch (\Throwable $e) {
        throw new UserErrorException($e->getMessage(),null, $e);
      }
    }
    // Return list of Datasources
    $listItemData = array();
    $lastDatasource = $this->module->getPreference("lastDatasource");
    foreach (Datasource::findBySchema("z3950") as $datasource)
    {
      if( $activeOnly and ! $datasource->active ) continue;
      $listItemData[] = array(
        'label'     => $datasource->title,
        'value'     => $datasource->namedId,
        'active'    => $datasource->active,
        'selected'  => $datasource->namedId == $lastDatasource
      );
    }
    return $listItemData;
  }

  /**
   * Sets datasources active / inactive, so that they do not show up in the
   * list of servers
   * param array $map Maps datasource ids to status
   * @throws \JsonRpc2\Exception
   * @throws UserErrorException
   * @todo add DTO
   */
  public function actionSetDatasourceState( $map )
  {
    $this->requirePermission("z3950.manage");
    foreach( (array) $map as $namedId => $active )
    {
      $datasource = Z3950Datasource::findByNamedId($namedId);
      $datasource->active= (int) $active;
      try {
        $datasource->save();
      } catch (\yii\db\Exception $e) {
        throw new UserErrorException($e->getMessage(),$e->getCode(),$e);
      }
    }
    $this->broadcastClientMessage("z3950.reloadDatasources");
    return "OK";
  }

  /**
   * Returns count of rows that will be retrieved when executing the current
   * query.
   *
   * @param object $queryData an array of the structure array(
   *   'datasource' => datasource name
   *   'query'      => array(
   *      'properties'  =>
   *      'orderBy'     =>
   *      'cql'         => "the string query (ccl/cql format)"
   *   )
   * )
   * return array ( 'rowCount' => row count )
   */
  function actionRowCount( $queryData )
  {
    $datasource = $queryData->datasource;
    $query = $this->module->getQueryString( $queryData );
    Yii::debug("Row count query for datasource '$datasource', query '$query'", Module::CATEGORY);
    $search = Search::findOne([ 'query' => $query ]);
    if( ! $search){
      throw new UserErrorException(Yii::t(Module::CATEGORY, "No search data exists."));
    }
    $hits = $search->hits;
    Yii::debug("$hits hits.", Module::CATEGORY);
    return array(
      'rowCount'    => $hits,
      'statusText'  => Yii::t(Module::CATEGORY, "{number} hits", ['number'=>$hits])
    );
  }

  /**
   * Returns row data executing a constructed query
   *
   * @param int $firstRow First row of queried data
   * @param int $lastRow Last row of queried data
   * @param int $requestId Request id, deprecated
   * @param object $queryData an array of the structure array(
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
   */
  function actionRowData( int $firstRow, int $lastRow, int $requestId, object $queryData )
  {
    $datasource = $queryData->datasource;
    $query = $this->module->getQueryString( $queryData );
    $properties = $queryData->query->properties;
    $orderBy = $queryData->query->orderBy;
    Yii::debug("Row data query for datasource '$datasource', query '$query'.", Module::CATEGORY);
    $search = Search::findOne([ 'query' => $query ]);
    if( ! $search){
      throw new \RuntimeException(Yii::t(Module::CATEGORY, "No search data exists."));
    }
    $hits = $search->hits;
    Yii::debug("Cache says we have $hits hits for query '$query'.", Module::CATEGORY);

    // try to find already downloaded records and return them as rowData
    $searchId = $search->id;
    $lastRow  = max($lastRow,$hits-1);

    Yii::debug("Looking for result data for search #$searchId, rows $firstRow-$lastRow...", Module::CATEGORY);
    $result = Result::findOne([
      'SearchId'  => $searchId,
      'firstRow'  => $firstRow
    ]);
    if( ! $result ) {
      throw new \RuntimeException(Yii::t(Module::CATEGORY, "No result data exists."));
    }
    $firstRecordId = $result->firstRecordId;
    $lastRecordId  = $result->lastRecordId;
    Yii::debug("Getting records $firstRecordId-$lastRecordId from cache ...", Module::CATEGORY);

    // get row data from cache
    $rowData = Record::find()
      ->select($properties)
      ->where(['between', 'id', $firstRecordId, $lastRecordId])
      ->orderBy( $orderBy )
      ->asArray();
    return array(
      'requestId'   => $requestId,
      'rowData'     => $rowData,
      'statusText'  => "Loaded rows $firstRow-$lastRow."
    );
  }

  /**
   * Imports the found references into the main datasource
   * @param string $sourceDatasource
   * @param $ids
   * @param string $targetDatasource
   * @param int $targetFolderId
   * @return string "OK"
   * @throws \JsonRpc2\Exception
   * @todo Identical method in app\controllers\ImportController
   * @throws Exception
   */
  public function actionImport( string $sourceDatasource, array $ids, string $targetDatasource, int $targetFolderId )
  {
    foreach( $ids as $id )
    {
      $sourceModelClass = Datasource::in($sourceDatasource, "record");
      $targetModelClass = Datasource::in($targetDatasource, "reference");
      /** @var Record $sourceModel */
      $sourceModel = $sourceModelClass::findOne($id);
      $copiedAttributeValues = $sourceModel->getAttributes(null, ['id','modified','created']);
      /** @var Reference $targetModel */
      $targetModel = new $targetModelClass($copiedAttributeValues);
      $targetModel->citekey = $targetModel->computeCiteKey();
      // remove leading "c" and other characters in year data
      $year = $targetModel->year;
      if( $year[0] == "c" ) {
        $year = trim(substr($year,1));
      }
      $year = preg_replace("/[\{\[\\]\}\(\)]/",'',$year);
      $targetModel->year = $year;
      try {
        $targetModel->save();
      } catch (Exception $e) {
        throw new UserErrorException($e->getMessage(), null, $e);
      }
      /** @var Folder $targetFolderModel */
      $targetFolderModel = Datasource::in($targetDatasource,"folder")::findOne($targetFolderId);
      if( ! $targetFolderModel ){
        throw new UserErrorException("Invalid folder id #$targetFolderId");
      }
      $targetModel->link('folders',  $targetFolderModel );
    }

    // update reference count
    $referenceCount = $targetFolderModel->getReferences()->count();
    $targetFolderModel->referenceCount = $referenceCount;
    $targetFolderModel->save();

    // reload references and select the new reference
    $this->dispatchClientMessage("folder.reload", array(
      'datasource'  => $targetDatasource,
      'folderId'    => $targetFolderId
    ) );

    return "OK";
  }


  /**
   * @throws \Exception
   * @throws \app\modules\z3950\lib\yaz\YazException
   */
  public function actionTest()
  {
    $this->module->test();
  }


  /**
   * Returns an empty rowData response with the error message as status text.
   * @param $requestId
   * @param $error
   * @return array
   */
  protected function rowDataError( $requestId, $error)
  {
    return array(
      'requestId'   => $requestId,
      'rowData'     => array(),
      'statusText'  => $error
    );
  }



}
