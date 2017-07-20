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

qcl_import("qcl_data_controller_Controller");
qcl_import("qcl_util_system_Executable");
qcl_import("bibliograph_service_Reference");
qcl_import("bibliograph_service_Folder");
qcl_import("z3950_DatasourceModel");
qcl_import("qcl_ui_dialog_ServerProgress");
    
require_once "lib/yaz/YAZ.php";
/** @noinspection PhpIncludeInspection */
require_once "bibliograph/lib/bibtex/BibtexParser.php";

class class_z3950_Service
  extends qcl_data_controller_Controller
{

  /**
   * Access control list. Determines what role has access to what kind
   * of information.
   * @var array
   */
  private $modelAcl = array(

    /*
     * The record model of the given z39.50 datasource
     */
    array(
      'datasource'  => "*",
      'modelType'   => "record",

      'rules'         => array(
        array(
          'roles'       => "*",
          'access'      => array( QCL_ACCESS_READ ),
          'properties'  => array( "allow" =>  "*" )
        )
      )
    ),

    /*
     * The reference model of the datasource into which we'll import
     */
    array(
      'datasource'  => "*",
      'modelType'   => array("reference","folder"),

      'rules'         => array(
        array(
          'roles'       => array( QCL_ROLE_USER ),
          'access'      => array( QCL_ACCESS_READ, QCL_ACCESS_WRITE, QCL_ACCESS_CREATE ),
          'properties'  => array( "allow" =>  "*" )
        )
      )
    ),
  );

  /*
  ---------------------------------------------------------------------------
     INITIALIZATION
  ---------------------------------------------------------------------------
  */

  /**
   * Constructor, adds model acl
   */
  function __construct()
  {
    $this->addModelAcl( $this->modelAcl );
  }

  /**
   * Returns the default model type for which this controller is providing
   * data.
   * @return string
   */
  protected function getModelType()
  {
    return "record";
  }


  /*
  ---------------------------------------------------------------------------
     TABLE INTERFACE API
  ---------------------------------------------------------------------------
  */

  /**
   * Returns the layout of the columns of the table displaying
   * the records
   *
   * @param $datasource
   * @return array
   */
  public function method_getTableLayout( $datasource )
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
   * Returns the query as a string constructed from the
   * query data object
   * @param object $queryData
   * @return string
   */
  protected function getQueryString( $queryData )
  {
    qcl_assert_object( $queryData->query );
    $query = $queryData->query->cql;
    return $this->fixQueryString($query);
  }
  
  /**
   * Checks the query and optimizes it before
   * sending it to the remote server
   * @param $query
   * @return string
   */
  protected function fixQueryString( $query )
  {
    if( substr( $query, 0, 3 ) == "978")
    {
      $query = 'isbn=' . $query;
    }
    elseif ( ! strstr( $query, "=" ) )
    {
      $query = 'all="' . $query . '"';
    }
    return $query;
  }


  /**
   * Configures the yaz object for a ccl query with a minimal common set of fields:
   * title, author, keywords, year, isbn, all
   * @param YAZ $yaz
   * @return void
   */
  protected function configureCcl( $yaz )
  {
    $yaz->ccl_configure(array(
      "title"     => "1=4",
      "author"    => "1=1004",
      "keywords"  => "1=21",
      "year"      => "1=31",
      "isbn"      => "1=7",
      "all"       => "1=1016"
    ) );
  }

  /**
   * Service method that returns ListItem model data on the available library servers
   * @param $all Whether to return only the active datasources (default) or all 
   * @param $reloadFromXmlFiles Whether to reload the list from the XML Explain files 
   * in the filesystem. This is neccessary if xml files have been added or removed.
   * @return array
   */
  public function method_getServerListItems($activeOnly=true,$reloadFromXmlFiles=false)
  {
    // Reset list of Datasources
    if ( $reloadFromXmlFiles )
    {
      z3950_DatasourceModel::getInstance()->createFromExplainFiles();
    }

    // Return list of Datasources
    $listItemData = array();
    $lastDatasource = $this->getApplication()->getPreference("z3950.lastDatasource");
    $dsModel = z3950_DatasourceModel::getInstance();
    $dsModel->findAll();
    while( $dsModel->loadNext() )
    {
      // clear cache
      try
      {
        $dsModel->getInstanceOfType("record")->deleteAll();
        $dsModel->getInstanceOfType("search")->deleteAll();
        $dsModel->getInstanceOfType("result")->deleteAll();
      }
      catch( PDOException $e) {} // FIXME This should not be a PDOException, see https://github.com/cboulanger/bibliograph/issues/133
      
      // assemble data
      if( $activeOnly and ! $dsModel->getActive() ) continue;
      
      $name   = $dsModel->getName();
      $value  = $dsModel->getNamedId();
      $listItemData[] = array(
        'label'     => $name,
        'value'     => $value,
        'active'    => $dsModel->getActive(),
        'selected'  => $value == $lastDatasource
      );
    }
    return $listItemData;
  }
  
  /**
   * Sets datasources active / inactive, so that they do not show up in the
   * list of servers
   * @param array $map Maps datasource ids to status
   */
  public function method_setDatasourceState( $map )
  {
    $this->requirePermission("z3950.manage");
    foreach( $map as $datasource => $active )
    {
      $dsModel = z3950_DatasourceModel::getInstance();
      $dsModel->load($datasource);
      $dsModel->setActive($active)->save();
    }
    $this->broadcastClientMessage("z3950.reloadDatasources");
    return "OK";
  }
  
  
  /**
   * Executes a Z39.50 request on the remote server. Called 
   * by the ServerProgress widget on the client
   *
   * @param $datasource 
   * @param $query 
   * @param $progressWidgetId
   * @return a chunked HTTP response
   */  
  public function method_executeZ3950Request( $datasource, $query, $progressWidgetId )
  {
    $progressBar = new qcl_ui_dialog_ServerProgress( $progressWidgetId );
    try
    {
      $this->executeZ3950Request( $datasource, $query, $progressBar );
      $progressBar->dispatchClientMessage( "z3950.dataReady", $query );
      return $progressBar->complete();
    }
    catch( qcl_server_ServiceException $e )
    {
      $this->log($e->getMessage(), BIBLIOGRAPH_LOG_Z3950);
      return $progressBar->error( $e->getMessage() );  
    }
    catch( Exception $e )
    {
      $this->warn( $e->getFile() . ", line " . $e->getLine() . ": " . $e->getMessage() );
      return $progressBar->error( $e->getMessage() );
    }
  }
     
   
  /**
   * Does the actual work of executing the Z3950 request on the remote server.
   *
   * @param $datasource 
   * @param $query 
   * @param qcl_ui_dialog_ServerProgress $progressBar 
   *    A progressbar object responsible for displaying the progress
   *    on the client (optional)
   * @throws Exception
   * @return void
   */ 
  function executeZ3950Request($datasource, $query, qcl_ui_dialog_ServerProgress $progressBar=null)
  {

    // remember last datasource used
    $this->getApplication()->setPreference("z3950.lastDatasource", $datasource);

    $dsModel = $this->getDatasourceModel( $datasource );
    $recordModel = $dsModel->getInstanceOfType("record");
    $searchModel = $dsModel->getInstanceOfType("search");
    $resultModel = $dsModel->getInstanceOfType("result");
    
    $query = $this->fixQueryString($query); //todo
    
    $this->log("Executing query '$query' on remote Z39.50 database '$datasource' ...", BIBLIOGRAPH_LOG_Z3950);
    
    $yaz = new YAZ( $dsModel->getResourcepath() );
    $yaz->connect();
    $this->configureCcl( $yaz );
    
    /*
     * execute search
     */
    try
    {
      $yaz->search( new YAZ_CclQuery( $query ) );
    }
    catch(YAZException $e)
    {
      throw new qcl_server_ServiceException($this->tr("The server does not understand the query \"%s\". Please try a different query.", $query));
    }
    
    try
    {
      $syntax = $yaz->setPreferredSyntax(array("marc"));
      $this->log("Syntax is '$syntax' ...", BIBLIOGRAPH_LOG_Z3950);
    }
    catch( YAZException $e)
    {
      throw new qcl_server_ServiceException($this->tr("Server does not support a convertable format.") );
    }
    
    if( $progressBar) $progressBar->setProgress( 0, $this->tr("Waiting for remote server...") ); 
    
    /*
     * Result
     */
    $yaz->wait();
    
    $error = $yaz->getError();
    if( $error )
    {
      $this->log("Server error (yaz_wait): $error. Aborting.", BIBLIOGRAPH_LOG_Z3950);
      throw new qcl_server_ServiceException($this->tr( "Server error: %s", $error ) );
    }    
    
    $info = array();
    $hits = $yaz->hits($info);
    $this->log("(Optional) result information: " . json_encode($info), BIBLIOGRAPH_LOG_Z3950);

    /*
     * No result or a too large result
     */
    $maxHits = 1000; // @todo make this configurable
    if ( $hits == 0)
    {
      throw new qcl_server_ServiceException($this->tr("No results."));
    }
    elseif ( $hits > $maxHits ) 
    {
      throw new qcl_server_ServiceException($this->tr("The number of results is higher than %s records. Please narrow down your search.", $maxHits) );
    }
    
    
    /*
     * save search results
     */
    $this->log("Found $hits records...", BIBLIOGRAPH_LOG_Z3950);
  
    try
    {
      $searchModel->loadWhere( array( 'query' => $query ) );
      $searchModel->delete();
      $this->log("Deleted existing search data for query '$query'.", BIBLIOGRAPH_LOG_Z3950);
    }
    catch( qcl_data_model_RecordNotFoundException $e){}
    
    $activeUserId = $this->getApplication()->getAccessController()->getActiveUser()->id();
    $searchId = $searchModel->create( array(
      'query'   => $query,
      'hits'    => $hits,
      'UserId'  => $activeUserId
    ) );
    $this->log("Created new search record #$searchId for query '$query' for user #$activeUserId.", BIBLIOGRAPH_LOG_Z3950);     
     
    if ($progressBar) $progressBar->setProgress( 10, $this->tr("%s records found.", $hits ) ); 
    
    /*
     * Retrieve record data
     */
    $this->log("Getting row data from remote Z39.50 database ...", BIBLIOGRAPH_LOG_Z3950);
    $yaz->setRange( 1, $hits );
    $yaz->present();
    
    $error = $yaz->getError();
    if( $error )
    {
      $this->log("Server error (yaz_present): $error. Aborting.", BIBLIOGRAPH_LOG_Z3950);
      throw new qcl_server_ServiceException( $this->tr("Server error: $error.") );
    }      

    $result = new YAZ_MarcXmlResult($yaz);
    
    for( $i=1; $i<=$hits; $i++)
    {
      try
      {
        $result->addRecord( $i );
        if ($progressBar) 
          $progressBar->setProgress( 10+(($i/$hits)*80), $this->tr("Retrieving %s of %s records...", $i, $hits ) );
      } 
      catch ( YAZException $e)
      {
        if( stristr( $e->getMessage(), "timeout" ) )
        {
          throw new qcl_server_ServiceException( $this->tr("Server timeout trying to retrieve %s records: try a more narrow search", $hits) );
        }
        throw new qcl_server_ServiceException( $this->tr("Server error: %s.", $e->getMessage() ) );
      }
    }

    // visually separate verbose output for debugging
    function ml( $description, $text ) {
      $hl = "\n" . str_repeat("-",100) . "\n"; 
      return  $hl . $description . $hl . $text . $hl;
    }
    
    $this->log(ml("XML",$result->getXml()),BIBLIOGRAPH_LOG_Z3950_VERBOSE);
    $this->log("Formatting data...", BIBLIOGRAPH_LOG_Z3950);

    if ($progressBar) $progressBar->setProgress( 90, $this->tr("Formatting records...") );  

    /*
     * convert to MODS
     */
    $mods = $result->toMods();
    $this->log(ml("MODS",$mods),BIBLIOGRAPH_LOG_Z3950_VERBOSE);

    /*
     * convert to bibtex and fix some issues
     */
    $xml2bib = new qcl_util_system_Executable( BIBUTILS_PATH . "xml2bib");
    $bibtex = $xml2bib->call("-nl -fc -o unicode", $mods );
    $bibtex = str_replace( "\nand ", "; ", $bibtex );
    $this->log(ml("BibTeX",$bibtex),BIBLIOGRAPH_LOG_Z3950_VERBOSE);

    /*
     * convert to array
     */
    $parser = new BibtexParser;
    $records = $parser->parse( $bibtex );

    if ( count( $records) === 0 )
    {
      $this->log("Empty result set, aborting...", BIBLIOGRAPH_LOG_Z3950);
      throw new qcl_server_ServiceException( $this->tr("Cannot convert server response") );
    }

    /*
     * saving to local cache
     */
    $this->log("Saving data...", BIBLIOGRAPH_LOG_Z3950);

    $firstRecordId = 0;
    //$rowData = array();

    $step = 10/count($records);
    $i= 0;
    
    foreach( $records as $item )
    {
      if ($progressBar) $progressBar->setProgress( 90+ ($step*$i++), $this->tr("Caching records...") );  
      
      $p = $item->getProperties();

      // fix bibtex issues
      foreach( array("author","editor") as $key )
      {
        $p[$key] = str_replace( "{", "", $p[$key]);
        $p[$key] = str_replace( "}", "", $p[$key]);
      }

      /*
       * create record
       */
      $id = $recordModel->create( $p );
      if (! $firstRecordId ) $firstRecordId = $id;

      $recordModel->set( array(
        'citekey' => $item->getItemID(),
        'reftype' => $item->getItemType()
      ) );
      $recordModel->save();
      $recordModel->linkModel($searchModel);
      $this->log(ml("Model Data",print_r($recordModel->data(),true)),BIBLIOGRAPH_LOG_Z3950_VERBOSE);
    }
    
    $lastRecordId = $id;
    $firstRow     = 0;
    $lastRow      = $hits-1;
    
    $data = array(
      'firstRow'      => $firstRow,
      'lastRow'       => $lastRow,
      'firstRecordId' => $firstRecordId,
      'lastRecordId'  => $lastRecordId
    );
    $searchId = $resultModel->create( $data );
    $this->log("Saved result data for search #$searchId, rows $firstRow-$lastRow...", BIBLIOGRAPH_LOG_Z3950);
    
    $resultModel->linkModel( $searchModel );    

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
# REWI20170717 - disabled next line (ignoring side effects...)
#      $searchModel->deleteAll();
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
# REWI20170717
#      $searchModel->deleteAll();
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
# REWI20170717
#      $searchModel->deleteAll();
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
