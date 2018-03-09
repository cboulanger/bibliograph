<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */



class class_z3950_Service
  extends qcl_data_controller_Controller
{










  

  


  
  

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
   * @throws JsonRpcException
   * @return array ( 'rowCount' => row count )
   */
  function method_getRowCount( $queryData )
  {
    $datasource = $queryData->datasource;
    qcl_assert_valid_string( $datasource );
    $query = $this->getQueryString( $queryData );

    $this->log("Row count query for datasource '$datasource', query '$query'", BIBLIOGRAPH_LOG_Z3950);

    $dsModel     = $this->getDatasourceModel( $datasource );
    $searchModel = $dsModel->getInstanceOfType("search");
    
    try
    {
      $searchModel->loadWhere( array( 'query' => $query ) );
      $this->log("Getting hits number from local cache...", BIBLIOGRAPH_LOG_Z3950);
      $hits = $searchModel->getHits();
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      // this should never occur
      throw new qcl_server_ServiceException( "No search model data exists for this query" );
    }
    /*
     * return to client
     */
    $this->log("$hits hits.", BIBLIOGRAPH_LOG_Z3950);
    return array(
      'rowCount'    => $hits,
      'statusText'  => "$hits " . $this->tr("hits")
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
   * @throws JsonRpcException
   * @return array Array containing the keys
   *                int     requestId   The request id identifying the request (mandatory)
   *                array   rowData     The actual row data (mandatory)
   *                string  statusText  Optional text to display in a status bar
   */
  function method_getRowData( $firstRow, $lastRow, $requestId, $queryData )
  {
    $datasource = $queryData->datasource;
    qcl_assert_valid_string( $datasource );

    $query = $this->getQueryString( $queryData );

    $properties = $queryData->query->properties;
    qcl_assert_array( $properties );
    $orderBy = $queryData->query->orderBy;

    $this->log("Row data query for datasource '$datasource', query '$query'.", BIBLIOGRAPH_LOG_Z3950);

    $dsModel = $this->getDatasourceModel( $datasource );
    $recordModel = $dsModel->getInstanceOfType("record");
    $searchModel = $dsModel->getInstanceOfType("search");
    $resultModel = $dsModel->getInstanceOfType("result");

    /*
     * check that the search record exists
     */
    try
    {
      $searchModel->loadWhere( array( 'query' => $query ) );
      $hits = $searchModel->getHits();
      $this->log("Cache says we have $hits hits for query '$query'.", BIBLIOGRAPH_LOG_Z3950);
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      $this->warn("Logic Exception! Search model data missing.");
      // this should never happen
      throw new qcl_server_ServiceException( "No search model data exists for query '$query'" );
    }

    try
    {

      /*
       * try to find already downloaded records and
       * return them as rowData
       */
      $searchId = $searchModel->id();
      $lastRow  = max($lastRow,$hits-1); 
      
      $this->log("Looking for result data for search #$searchId, rows $firstRow-$lastRow...", BIBLIOGRAPH_LOG_Z3950);
      $resultModel->loadWhere( array(
        'SearchId'  => $searchId,
        'firstRow'  => $firstRow
      ) );

      $firstRecordId = $resultModel->get("firstRecordId");
      $lastRecordId  = $resultModel->get("lastRecordId");

      $this->log("Getting records $firstRecordId-$lastRecordId from cache ...", BIBLIOGRAPH_LOG_Z3950);

    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      // this should never happen
      $this->warn("Logic Exception! Result model data missing.");
      throw new qcl_server_ServiceException( "No result model data exists for this query" );
    }

    /*
     * get row data from cache
     */
    $rowData = $recordModel->getQueryBehavior()->fetchAll(
      new qcl_data_db_Query( array(
        'properties'  => $properties,
        'where'       => "id BETWEEN $firstRecordId AND $lastRecordId",
        'orderBy'     => $orderBy
      ) )
    );

    $this->log("Returning data to client ...", BIBLIOGRAPH_LOG_Z3950);
    return array(
      'requestId'   => $requestId,
      'rowData'     => $rowData,
      'statusText'  => "Loaded rows $firstRow-$lastRow."
    );
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

  /**
   * @todo Identical method in qcl_controller_ImportController
   * @param $sourceDatasource
   * @param $ids
   * @param $targetDatasource
   * @param $targetFolderId
   * @return string "OK"
   */
  public function method_importReferences( $sourceDatasource, $ids, $targetDatasource, $targetFolderId )
  {
    $this->requirePermission("reference.import");

    qcl_assert_valid_string( $sourceDatasource );
    qcl_assert_array( $ids );
    qcl_assert_valid_string( $targetDatasource );
    qcl_assert_integer( $targetFolderId );

    $sourceModel = $this->getModel( $sourceDatasource, "record" );

    $targetReferenceModel = bibliograph_service_Reference::getInstance()
      ->getReferenceModel($targetDatasource);

    $targetFolderModel = bibliograph_service_Folder::getInstance()
      ->getFolderModel( $targetDatasource );

    $targetFolderModel->load( $targetFolderId );

    foreach( $ids as $id )
    {
      $sourceModel->load($id);
      $targetReferenceModel->create();
      $targetReferenceModel->copySharedProperties( $sourceModel );
      
      // compute citation key
      $targetReferenceModel->set("citekey", $targetReferenceModel->computeCiteKey());
      
      // rmove leading "c" and other characters in year data
      $year = $targetReferenceModel->get("year");
      if( $year[0] == "c" )
      {
         $year = trim(substr($year,1));
      }
      $year = preg_replace("/[\{\[\\]\}\(\)]/",'',$year);
      $targetReferenceModel->set("year", $year);
      
      $targetReferenceModel->save();
      $targetReferenceModel->linkModel( $targetFolderModel );
    }

    /*
     * update reference count
     */
    $referenceCount = count( $targetReferenceModel->linkedModelIds( $targetFolderModel ) );
    $targetFolderModel->set( "referenceCount", $referenceCount );
    $targetFolderModel->save();

    /*
     * reload references and select the new reference
     */
    $this->dispatchClientMessage("folder.reload", array(
      'datasource'  => $targetDatasource,
      'folderId'    => $targetFolderId
    ) );

    return "OK";
  }

  public function method_test()
  {

    $gbvpath = realpath( dirname(__FILE__) . "/servers/z3950.gbv.de-20010-GVK-de.xml" );

    $yaz = new YAZ( $gbvpath );
    $yaz->connect();

    $yaz->ccl_configure(array(
      "title"     => "1=4",
      "author"    => "1=1004",
      "keywords"  => "1=21",
      "year"      => "1=31"
    ) );

    $query = new YAZ_CclQuery("author=boulanger");

    $yaz->search( $query );

    $yaz->wait();
    $hits = $yaz->hits();

    $this->info( "$hits hits.");

    $yaz->setSyntax("USmarc");
    $yaz->setElementSet("F");
    $yaz->setRange( 1, 3 );
    $yaz->present();

    $result = new YAZ_MarcXmlResult($yaz);

    for( $i=1; $i<3; $i++)
    {
      $result->addRecord( $i );
    }
    $mods = $result->toMods();

    $xml2bib = new qcl_util_system_Executable("xml2bib");
    $bibtex = $xml2bib->call("-nl -b -o unicode", $mods );
    $parser = new BibtexParser;

    $this->debug( $parser->parse( $bibtex ) );

    return "OK";
  }
}
