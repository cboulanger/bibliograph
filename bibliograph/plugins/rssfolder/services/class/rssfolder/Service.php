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

qcl_import("qcl_server_JsonRpcRestServer");
qcl_import("qcl_data_controller_TableController");
qcl_import("qcl_ui_dialog_Confirm");
qcl_import("qcl_ui_dialog_Prompt");
qcl_import("bibliograph_model_export_Bibtex");
qcl_import("bibliograph_model_import_RegistryModel");

class rssfolder_Service
  extends qcl_data_controller_TableController
{
  /**
   * Access control list. Determines what role has access to what kind
   * of information.
   * @var array
   */
  private $modelAcl = array(

    /*
     * The reference model of the given datasource
     */
    array(
      'datasource'  => "bibliograph_import",
      'modelType'   => "reference",

      'rules'         => array(
        array(
          'roles'       => array( BIBLIOGRAPH_ROLE_USER ),
          'access'      => "*",
          'properties'  => array( "allow" => "*" )
        )
      )
    )
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
   * Returns singleton instance of this class
   * @return bibliograph_service_Import
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
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
        'orderBy' => "author,year,title",
        'link'    => array( 'relation' => "Folder_Reference" ),
      ),
      'addItems' => null
    );
  }
  
  /*
  ---------------------------------------------------------------------------
     OTHER SERVICES
  ---------------------------------------------------------------------------
  */  
  
  public function method_getFeedUrl( $datasource, $folderId )
  {
    $app = $this->getApplication();
    $dsModel  = $this->getDatasourceModel( $datasource );
    
    // folder
    $fldModel = $dsModel->getInstanceOfType("folder");
    $fldModel->load( (int) $folderId );
    if( ! $fldModel->getPublic() ) 
    {
      throw new JsonRpcException(
        $this->tr( "'%s' is not public. RSS feeds can only be created from public folders.", $fldModel->getLabel() ) 
      );
    }
    
    $feedUrl = qcl_server_JsonRpcRestServer::getJsonRpcRestUrl(
      $this->serviceName(),"getFolderRSS", array($datasource, $folderId)
    );
    
    return $feedUrl;
  }
  
  public function method_parseFeed( $url )
  {
    $this->requirePermission("reference.import");
    qcl_assert_valid_string( $url, "Invalid format");

    /*
     * get the folder and reference models
     */
    $dsModel  = $this->getDatasourceModel("bibliograph_import");
    $refModel = $dsModel->getInstanceOfType("reference");
    $fldModel = $dsModel->getInstanceOfType("folder");

    /*
     * delete the folder with the session id as label
     */
    $sessionId = $this->getSessionId();
    try
    {
      $fldModel->findWhere( array('label' => $sessionId ) );
      $fldModel->loadNext();
      $refModel->findLinked( $fldModel );
      while( $refModel->loadNext() ) $refModel->delete();
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      $fldModel->create( array( 'label' => $sessionId ) );
    }

    /*
     * convert and import data
     */
    $xml = file_get_contents($url);
    //$this->debug($xml);
     
    libxml_use_internal_errors(true);
    $rss = simplexml_load_string($xml);
    if( ! $rss ) {
      foreach(libxml_get_errors() as $error) {
          $this->warn( $error->message);
      }      
      throw new JsonRpcException($this->tr("Cannot parse feed at %s", $url));
    }
    
    $data = array();
    foreach( $rss->channel->item as $item )
    {
      $dc = $item->children("http://purl.org/dc/elements/1.1/");
      $record = array(
        'url'     => (string) $item->link,
        'author'  => (string) $dc->creator,
        'title'   => (string) $dc->title,
        'year'    => (string) $dc->date
      );
      $refModel->create( $record );
      $refModel->linkModel($fldModel);      
    }

    /*
     * return information on containing folder
     */
    return array(
      'folderId' => $fldModel->id()
    );
  }
  
  /**
   * Imports the given records into the current datasource/folder
   */
  public function method_import( $ids, $datasource, $folderId )
  {
    $this->requirePermission("reference.import");
    qcl_assert_array( $ids );
    qcl_assert_valid_string( $datasource );
    qcl_assert_integer( $folderId );
    
    /*
     * load importer object according to format
     */
    $importRegistry = bibliograph_model_import_RegistryModel::getInstance();
    $importer = $importRegistry->getImporter( "bibtex" );

    /*
     * get the folder and reference models
     */
    $dsModel  = $this->getDatasourceModel("bibliograph_import");
    $refModel = $dsModel->getInstanceOfType("reference");

    /*
     * load imported data
     */
    $data = array();
    foreach( $ids as $id )
    {
      $refModel->load( $id );
      $url = $refModel->getUrl();
      $record = @file_get_contents( $url );
      if( ! $record ){
        $this->warn("Could not load data from $url");
      }
      $data .= "\n" . $record;
    }
    
    // parse data
    $records = $importer->import( $data );
    
    
    // import data in target datasource
    $dsModel  = $this->getDatasourceModel($datasource);
    $refModel = $dsModel->getInstanceOfType("reference");
    $fldModel = $dsModel->getInstanceOfType("folder");
    $fldModel->load($folderId);
    
    foreach( $records as $record )
    {
      $refModel->create( $record );
      $refModel->linkModel($fldModel);
    }

    /*
     * reload references and select the new reference
     */
    $this->dispatchClientMessage("folder.reload", array(
      'datasource'  => $datasource,
      'folderId'    => $folderId
    ) );

    /*
     * return information on containing folder
     */
    return array(
      'folderId' => $fldModel->id()
    );
  }
  
  /**
   * Create RSS 2.0 / Atom Feed from folder data
   * written with help from http://www.sanwebe.com/2013/08/creating-rss-feed-using-php-simplexml
   * Returns HTML 
   * 
   * @param string $datasource Name of datasource
   * @param int $folderId
   * @return void 
   */
  public function method_getFolderRSS( $datasource, $folderId )
  {
    if ( ! ($folderId = (int) $folderId ) or ! trim($datasource) )
    {
      die ("Invalid arguments");
    }
    
    $app = $this->getApplication();
    $dsModel  = $this->getDatasourceModel( $datasource );

    // folder
    $fldModel = $dsModel->getInstanceOfType("folder");
    $fldModel->load( (int) $folderId );
    if( ! $fldModel->getPublic() ) die("Access denied.");
    
    $appUrl = $app->getAppStateUrl( $datasource,$folderId,"");
    
    header('Content-Type: text/xml; charset=utf-8', true); 

    $preamble = <<<EOT
    <rss 
      xmlns:dc="http://purl.org/dc/elements/1.1/" 
      xmlns:content="http://purl.org/rss/1.0/modules/content/"
      xmlns:enc="http://purl.oclc.org/net/rss_2.0/enc#"  
      xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
      xmlns:atom="http://www.w3.org/2005/Atom">
    </rss>
EOT;
    
    $rss = new SimpleXMLElement($preamble);
    $rss->addAttribute('version', '2.0');
    $channel = $rss->addChild('channel');
    
    $atom = $rss->addChild('atom:atom:link'); 
    $atom->addAttribute('href', $appUrl); 
    $atom->addAttribute('rel', 'self');
    $atom->addAttribute('type', 'application/rss+xml');
    
    $title = $channel->addChild('title',$fldModel->getLabel());
    $description = $channel->addChild('description', $fldModel->getDescription() );
    $link = $channel->addChild('link', $appUrl );
    $language = $channel->addChild('language','en-us'); 
    
    //Create RFC822 Date format to comply with RFC822
    $date_f = date("D, d M Y H:i:s T", time());
    $build_date = gmdate(DATE_RFC2822, strtotime($date_f)); 
    $lastBuildDate = $channel->addChild('lastBuildDate',$date_f); 
    
    $generator = $channel->addChild('generator','PHP Simple XML');
  
    // get reference data
    $refModel = $dsModel->getInstanceOfType("reference");
    $refModel->findLinked( $fldModel, null, "modified DESC" );
    $count = 0;
    
    while($refModel->loadNext() )
    {
        $data = $refModel->data();
        $resourceUrl = qcl_server_JsonRpcRestServer::getJsonRpcRestUrl(
          $this->serviceName(),"getRssResource", array($datasource, $folderId, $refModel->id(),"bibtex")
        );        
        
        // new channel/item
        $item = $channel->addChild('item'); 
        
        // rss metadata
        $appUrl = $app->getAppStateUrl( $datasource,$folderId,$refModel->id() );
        $link = $item->addChild('link', htmlentities( $resourceUrl ) );
        $guid = $item->addChild('guid', $appUrl ); //add guid node under item
        $guid->addAttribute('isPermaLink', 'false'); //add guid node attribute
        $date_rfc = gmdate(DATE_RFC2822, strtotime( $refModel->getModified() ));
        $item->addChild('pubDate', $date_rfc); 
        
        // dc metadata
        $item->addChild('dc:dc:creator', $refModel->getAuthor());
        $item->addChild('dc:dc:title', $refModel->getTitle());
        $item->addChild('dc:dc:date', $refModel->getYear());

        // Enclosure
        // $enclosure = $item->addChild('enc:enc:enclosure');
        // $enclosure->addAttribute('rdf:resource',$resourceUrl);
        // $enclosure->addAttribute('enc:url',$resourceUrl);
        // $enclosure->addAttribute('enc:type',"application/x-bibtex");

        // main content
        $title = htmlspecialchars($refModel->getAuthor() . " (" . $refModel->getYear() . "), " . $refModel->getTitle());
        $item->addChild('title', htmlspecialchars($title));

        $description = $refModel->getAbstract();
        $item->addChild('description', htmlspecialchars( $description ) ); 

        // Only return the fist 100 references FIXME un-hardcode this
        if ( $count++ > 100 ) break; 
    }

    echo $rss->asXML(); 
    exit;
  }
  
  /**
   * Returns reference data in export format
   */
  public function method_getRssResource($datasource,$folderId,$modelId,$type="bibtex")
  {
    // headers
    header('Content-Type: application/x-bibtex; charset=utf-8', true); 
    header("Content-Disposition: attachment; filename=$datasource-{$modelId}.bib;");
    
    $app = $this->getApplication();
    $dsModel  = $this->getDatasourceModel( $datasource );
    
    // folder
    $fldModel = $dsModel->getInstanceOfType("folder");
    $fldModel->load( (int) $folderId );
    if( ! $fldModel->getPublic() ) die("Access denied.");
    
    // refererence
    $refModel = $dsModel->getInstanceOfType("reference");
    $refModel->load($modelId);
    
    // output bibtex data
    $data = $refModel->data();
    $bibtex = bibliograph_model_export_Bibtex::getInstance()->export( array( $data ) );
    echo $bibtex;
    exit;
  }
}
