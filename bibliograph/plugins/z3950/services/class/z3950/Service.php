<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2014 Christian Boulanger

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
   * @return unknown_type
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
    qcl_assert_valid_string( $query );
    if ( ! strstr( $query, "=" ) )
    {
      $query = 'all="' . $query . '"';
    }
    return $query;
  }

  /**
   * Configures the yaz object for a ccl query, given the datasource
   * @param YAZ $yaz
   * @param string $datasource
   * @return void
   */
  protected function configureCcl( $yaz, $datasource )
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

    $dsModel = $this->getDatasourceModel( $datasource );
    //$recordModel = $dsModel->getModelOfType("record");
    $searchModel = $dsModel->getInstanceOfType("search");

    try
    {
      $searchModel->loadWhere( array( 'query' => $query ) );
      /*
       * a search record exists, simply return the hits
       */
      $this->log("Getting hits number from local cache...", BIBLIOGRAPH_LOG_Z3950);
      $hits = $searchModel->getHits();
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      /*
       * no search record exists, we have to create it
       */
      $this->log("Sending query to remote Z39.50 database...", BIBLIOGRAPH_LOG_Z3950);
      $path = realpath( dirname(__FILE__) . "/servers/z3950.gbv.de-20010-GVK-de.xml" );
      if ( ! file_exists( $path ) )
      {
        throw new JsonRpcException("No EXPLAIN file exists for Z39.50 database '$datasource'.");
      }
      $yaz = new YAZ( $path );
      $yaz->connect();
      $this->configureCcl( $yaz, $datasource );
      $yaz->search( new YAZ_CclQuery($query) );
      $yaz->wait();
      $hits = $yaz->hits();

      /*
       * save to local cache
       */
      $searchModel->create( array(
        'query' => $query,
        'hits'  => $hits
      ) );
    }
    /*
     * return to client
     */
    $this->log("$hits hits.", BIBLIOGRAPH_LOG_Z3950);
    return array(
      'rowCount'    => $hits,
      'statusText'  => "$hits hits"
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

    $this->log("Row data query for datasource '$datasource', query '$query' hits.", BIBLIOGRAPH_LOG_Z3950);

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
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      throw new JsonRpcException("Invalid query. You have to call the getRowCount method first");
    }

    try
    {
      /*
       * try to find already downloaded records and
       * return them as rowData
       */
      $resultModel->loadWhere( array(
        'SearchId'  => $searchModel->id(),
        'firstRow'  => $firstRow,
        'lastRow'   => $lastRow
      ) );

      /*
       * we have the records
       */
      $firstRecordId = $resultModel->get("firstRecordId");
      $lastRecordId  = $resultModel->get("lastRecordId");

      $this->log("Getting rows $firstRow-$lastRow from local cache (rows $firstRecordId-$lastRecordId)...", BIBLIOGRAPH_LOG_Z3950);

    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      /*
       * those rows have not yet been downloaded, get
       * them from the z39.50 database
       */
      $this->log("Getting row data from remote Z39.50 database ...", BIBLIOGRAPH_LOG_Z3950);
      $path = realpath( dirname(__FILE__) . "/servers/z3950.gbv.de-20010-GVK-de.xml" ); // todo: allow to choose other servers
      $yaz = new YAZ( $path );
      $yaz->connect();
      $this->configureCcl( $yaz, $datasource );
      $yaz->search( new YAZ_CclQuery($query) );
      $yaz->wait();

      /*
       * retrieving records
       */
      $this->log("Retrieving records ...", BIBLIOGRAPH_LOG_Z3950);
      $yaz->setSyntax("USmarc");
      $yaz->setElementSet("F");
      $length = $lastRow-$firstRow+1;
      $yaz->setRange( $firstRow+1, $length );
      $yaz->present();
      $result = new YAZ_MarcXmlResult($yaz);
      for( $i=$firstRow; $i<=$lastRow; $i++)
      {
        $result->addRecord( $i );
      }

      $this->log("Formatting data...", BIBLIOGRAPH_LOG_Z3950);

      /*
       * convert to MODS
       */
      $mods   = $result->toMods();
      //$this->debug($mods);

      /*
       * convert to bibtex
       */
      $xml2bib = new qcl_util_system_Executable( BIBUTILS_PATH . "xml2bib");
      $bibtex = $xml2bib->call("-nl -fc -o unicode", $mods );

      /*
       * fix formatting issues
       */
      $bibtex = str_replace( "\nand ", "; ", $bibtex );
      //$this->debug($bibtex);

      /*
       * convert to array
       */
      $parser = new BibtexParser;
      $records = $parser->parse( $bibtex );

      if ( count( $records) === 0 )
      {
        throw new JsonRpcException("Error during conversion from MARC");
      }

      /*
       * saving to local cache
       */
      $this->log("Saving data to local cache...", BIBLIOGRAPH_LOG_Z3950);

      $firstRecordId = 0;
      //$rowData = array();

      foreach( $records as $item )
      {
        $p = $item->getProperties();

        /*
         * fix bibtex issues
         */
        foreach( array("author","editor") as $key )
        {
          $p[$key] = str_replace( "{", "", $p[$key]);
          $p[$key] = str_replace( "}", "", $p[$key]);
        }

        /*
         * create record
         */
        $id = $recordModel->create( $p );
        if (! $firstRecordId) $firstRecordId = $id;

        $recordModel->set( array(
          'citekey' => $item->getItemID(),
          'reftype' => $item->getItemType()
        ) );
        $recordModel->save();
        $recordModel->linkModel($searchModel);
        //$this->debug($record);

      }
      $lastRecordId = $id;
      $resultModel->create(array(
        'firstRow'      => $firstRow,
        'lastRow'       => $lastRow,
        'firstRecordId' => $firstRecordId,
        'lastRecordId'  => $lastRecordId
      ) );
      $resultModel->linkModel($searchModel);
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