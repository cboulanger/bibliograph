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
qcl_import("qcl_ui_dialog_Confirm");
qcl_import("qcl_ui_dialog_Select");
qcl_import("qcl_ui_dialog_Prompt");
qcl_import("qcl_ui_dialog_Alert");
qcl_import("qcl_ui_dialog_Popup");
qcl_import("bibliograph_webapis_identity_WorldCatIdentities");
qcl_import("bibliograph_webapis_identity_SimpleNameParser");

/**
 * Class providing methods and services to import bibliographic data from scanned ISBN numbers
 */
class class_bibliograph_plugin_isbnscanner_Service
  extends qcl_data_controller_Controller
{

  /**
   * Service to display a dialog to enter an ISBN manually or with a barcode 
   * scanner. Once an ISBN number is entered, the dialog will automatically
   * submit the input after 2 seconds
   */
  public function method_enterIsbnDialog( $datasource, $folderId=null)
  {
    $this->requirePermission("reference.import");

    $this->checkDatasourceAccess($datasource);
    if (!$folderId)
    {
      throw new qcl_server_ServiceException($this->tr("No folder selected."));
    }

    return new qcl_ui_dialog_Prompt(
      $this->tr("Please enter the ISBN:"), /*value*/ "",
      $this->serviceName(),"getReferenceDataByIsbn",
      array(array($datasource, $folderId)),
      /*require input*/ true, /*autosubmit after 2 seconds*/ 2
    );    
  }

  /**
   * Resolves the ISBN to reference data.
   * @param string $isbn The ISBN
   * @param array $data Additional data
   * @return String
   */
  public function method_getReferenceDataByIsbn( $isbn, $data )
  {
    $this->requirePermission("reference.import");

    // hack todo fix this!
    if (! is_array($data) )
    {
      return "ABORTED: wrong signature, ignoring request";
    }

    // cancel button
    if (! $isbn )
    {
      $this->dispatchClientMessage("plugin.isbnscanner.ISBNInputListener.start");
      return "CANCEL";
    }

    qcl_assert_valid_string( $isbn, "ISBN must be a non-empty string" );
    
    $connectors = array(
      "LCVoyager","Xisbn"
    );

    $shelveId = $this->shelve($connectors, $isbn, $data);
    
    return new qcl_ui_dialog_Popup(
      $this->tr("Contacting webservices to resolve ISBN..."),
      $this->serviceName(), "iterateConnectors",
      array($shelveId)
    );
  }
  
  /**
   * Private function to return the connector object for a given name
   * @param string $connectorName The name of the connector
   * @return bibliograph_plugin_isbnscanner_IConnector
   */
  private function getConnectorObject( $connectorName )
  {
    $namespace = "bibliograph_plugin_isbnscanner_connector_";
    $connectorClass = $namespace . $connectorName;
    qcl_import($connectorClass);
    return new $connectorClass();    
  }
  
  
  /**
   * Service to iterate over the given connector names
   * @param null $dummy First parameter returned by client must be ignored
   * @param string $shelveId The id of stored data
   * @return qcl_ui_dialog_Popup
   */
  public function method_iterateConnectors( $dummy, $shelveId )
  {  
    $this->requirePermission("reference.import");

    list($connectors, $isbn, $data) = $this->unshelve($shelveId);

    if ( ! count($connectors) )
    {
      return new qcl_ui_dialog_Alert(
        $this->tr("Could not find any data for ISBN %s.", $isbn),
        $this->serviceName(),"enterIsbnDialog",
        $data
      );
    }
    
    $connector = $this->getConnectorObject( $connectors[0] );

    return new qcl_ui_dialog_Popup(
      $this->tr("Contacting %s. Please wait...", $connector->getDescription() ),
      $this->serviceName(), "tryConnector",
      array( $this->shelve( $connectors, $isbn, $data ) )
    );
  }

  /**
   * Given a record with BibTeX-conforming field names, return a formatted reference.
   * Todo: use proper formatting
   * @param array $record
   * @return string
   */
  protected  function formatReference(array $record)
  {
    return sprintf(
      "%s (%s): %s. %s:%s. %s.",
      $record['author'],
      $record['year'],
      $record['title'],
      $record['address'],
      $record['publisher'],
      $record['edition']
    );
  }
  
  /**
   * Service to try the first of the connector names in the given array
   * @param null $dummy First parameter returned by client must be ignored
   * @param string $shelveId The id of stored data
   * @return qcl_ui_dialog_Popup
   */
  public function method_tryConnector( $dummy, $shelveId )
  {
    $this->requirePermission("reference.import");

    list($connectors, $isbn, $data) = $this->unshelve($shelveId);

    $connector = $this->getConnectorObject( $connectors[0] );

    try
    {
      $records = $connector->getDataByIsbn( $isbn );
    }
    catch(qcl_server_IOException $e)
    {
      $records = array();
    }

    /*
     * if no result, try next connector
     */
    if( count($records) == 0 )
    {
      array_shift($connectors);
      $shelveId = $this->shelve( $connectors, $isbn, $data );
      return $this->method_iterateConnectors( null, $shelveId );
    }

    // remember the connector name
    $data[] = $connectors[0];

    // take only the first entry, ignore others
    $record = $records[0];

    /*
     * check for duplicates
     */
    $datasource = $data[0];
    $dsModel = $this->getDatasourceModel($datasource);
    $referenceModel = $dsModel->getInstanceOfType("reference");
    $isbn = str_replace("-","",$isbn);

    // try ISBN
    $referenceModel->findWhere(array(
      "isbn" => array("like","%$isbn%")
    ));

    // try title/year
    if( $referenceModel->foundNothing() ) $referenceModel->findWhere(array(
      "title" => array("like","%" . $record['title'] . "%"),
      "year"  => $record['year']
    ));

    /*
     * possible duplicates found
     */
    if( $referenceModel->foundSomething() )
    {
      $refs = array();
      while( $referenceModel->loadNext() )
      {
        // ignore refs in the trash
        if ( $referenceModel->get( "markedDeleted" ) ) continue;
        $refs[] = $this->formatReference( $referenceModel->data() );
      }
      if( count($refs) )
      {
        $msg = $this->tr(
          "Found: %s<br><br><b>Possible duplicates:</b><br>%s",
          $this->formatReference( $record ),
          join("<br>", $refs)
        );
      }
      else
      {
        $msg = $this->tr(
          "Found: %s",
          $this->formatReference( $record )
        );
      }
    }

    /*
     * no duplicates
     */
    else
    {
      $msg = $this->tr(
        "Found: %s",
        $this->formatReference( $record )
      );
    }

    $options = array(
      array( "value" => "continue", "label" => $this->tr("Import and continue")),
      array( "value" => "edit", "label" => $this->tr("Import and edit")),
      array( "value" => "skip", "label" => $this->tr("Skip and continue"))
    );

    /*
     * display found record and confirm import
     */
    return new qcl_ui_dialog_Select(
      $msg, $options, true,
      $this->serviceName(),"handleConfirmImport",
      array( $this->shelve( $record, $data ) )
    );
  }

  /**
   * Handle the response to the confirm dialog
   * @param null|bool $response
   * @param null $dummy First parameter returned by client must be ignored
   * @param string $shelveId The id of stored data
   * @return qcl_ui_dialog_Popup|string
   */
  public function method_handleConfirmImport( $response, $shelveId )
  {

    list( $record, $data ) = $this->unshelve( $shelveId );

    // CANCEL button -> exit
    if( ! $response )
    {
      $this->dispatchClientMessage("plugin.isbnscanner.ISBNInputListener.start");
      return "CANCEL";
    }

    list( $datasource, $folderId, $connectorName ) = $data;

    // SKIP
    if ( $response == "skip" )
    {
      return $this->method_enterIsbnDialog( $datasource, $folderId );
    }


    list( $datasource, $folderId, $connectorName ) = $data;
    // append response
    $data[] = $response;

    $connector  = $this->getConnectorObject( $connectorName );
    $nameformat = $connector->getNameFormat();
    if( $nameformat & NAMEFORMAT_SORTABLE_FIRST )
    {
      return new qcl_ui_dialog_Popup(
        $this->tr("Importing data. Please wait..." ),
        $this->serviceName(),"importReferenceData",
        array($this->shelve( $record, $data ))
      );
    }

    return new qcl_ui_dialog_Popup(
      $this->tr("Converting names. Please wait..." ),
      $this->serviceName(),"convertToSortableNames",
      array($this->shelve( $record, $data ))
    );
  }


  /**
   * Convert names to sortable names
   * @param null $dummy
   * @param string $shelveId The id of stored data
   * @return qcl_ui_dialog_Popup|string
   */
  public function method_convertToSortableNames( $dummy, $shelveId )
  {
    $this->requirePermission("reference.import");

    // unpack stored variables
    list( $record, $data ) = $this->unshelve( $shelveId );
    $record = object2array( $record );
    list( $datasource, $folderId, $connectorName, $response ) = $data;

    $namefields = array("author","editor");
    $connector  = $this->getConnectorObject( $connectorName );
    $nameformat = $connector->getNameFormat();

    if( ! ($nameformat & NAMEFORMAT_SORTABLE_FIRST) )
    {
      $separators = $connector->getNameSeparators();
      try
      {
        $record = $this->convertToSortable($namefields,$separators,$record);
      }
      catch(qcl_server_IOException $e)
      {
        $this->warn( $e );
      }
    }

    return new qcl_ui_dialog_Popup(
      $this->tr("Importing data. Please wait..." ),
      $this->serviceName(),"importReferenceData",
      array($this->shelve( $record, $data ))
    );
  }

  /**
   * Private function to convert the names of a record into a sortable format
   * @param $namefields
   * @param $separators
   * @param $record
   * @return mixed
   */
  private function convertToSortable($namefields, $separators, $record)
  {
    $engine = $this->getApplication()->getConfigModel()->getKey("bibliograph.sortableName.engine");
    $service =
      $engine == "web" ?
        new bibliograph_webapis_identity_WorldCatIdentities() :
        new bibliograph_webapis_identity_SimpleNameParser();

    foreach( $namefields as $field )
    {
      $content = trim($record[$field]);
      if( empty( $content) ) continue;

      // replace separators with bibliograph name separator
      foreach( $separators as $separator )
      {
        $content = str_replace($separator, BIBLIOGRAPH_VALUE_SEPARATOR, $content);
      }

      $sortableNames = array();
      $names = explode(BIBLIOGRAPH_VALUE_SEPARATOR, $content );

      foreach( $names as $name )
      {
        $name = trim($name);
        //$this->debug("Name: $name");

        $sortableName = $service->getSortableName($name);

        if( $sortableName === false )
        {
          //$this->debug("No match, keeping $name");
          $sortableName = $name;
        }
        elseif ( is_string( $sortableName) )
        {
          //$this->debug("Sortable name: $sortableName");
          if( strlen($sortableName) < strlen($name) )
          {
            //$this->debug("Not usable, keeping $name");
            $sortableName = $name;
          }
        }
        elseif ( is_array( $sortableName) )
        {
          // for now, use the first
          $sortableName = array_shift( $sortableName );
        }
        else
        {
          $sortableName = $name;
        }

        $sortableNames[] = $sortableName;
      }

      // re-join names
      $record[$field] = join(BIBLIOGRAPH_VALUE_SEPARATOR, $sortableNames ); // todo: this is schema-dependent!
    }
    return $record;
  }

  /**
   * Import the found reference data into the database
   * @param null|bool $response
   * @param string $shelveId The id of stored data
   * @return qcl_ui_dialog_Prompt|string
   */
  public function method_importReferenceData( $dummy, $shelveId )
  {
    $this->requirePermission("reference.import");

    // unpack stored variables
    list( $record, $data ) = $this->unshelve( $shelveId );
    $record = object2array( $record );
    list( $datasource, $folderId, $connectorName, $response ) = $data;

$this->debug($record);
$this->debug($data);

    /*
     * import
     */
    $dsModel = $this->getDatasourceModel($datasource);
    $referenceModel = $dsModel->getInstanceOfType("reference");
    $record['createdBy']= $this->getActiveUser()->namedId();
    $referenceModel->create($record);
    $referenceModel->set("citekey", $referenceModel->computeCitekey() )->save();

    /*
     * link to folder
     */
    $folderModel = $dsModel->getInstanceOfType("folder");
    $folderModel->load( $folderId );
    $folderModel->linkModel( $referenceModel );

    /*
     * update reference count
     */
    $referenceCount = count( $referenceModel->linkedModelIds( $folderModel ) );
    $folderModel->set( "referenceCount", $referenceCount );
    $folderModel->save();

    /*
     * reload references and select the new reference
     */
    $this->dispatchClientMessage("folder.reload", array(
      'datasource'  => $datasource,
      'folderId'    => $folderId
    ) );

    if ( $response == "edit" )
    {
      $this->dispatchClientMessage("bibliograph.setModel", array(
        'datasource'    => $datasource,
        'modelType'     => "reference",
        'modelId'       => $referenceModel->id()
      ) );
      $this->dispatchClientMessage("plugin.isbnscanner.ISBNInputListener.start");
      return new qcl_ui_dialog_Popup(null);
    }

    /*
     * import next reference
     */
    return $this->method_enterIsbnDialog( $datasource, $folderId );
  }
  
  
 /**
  * Service to ask for or confirm the email address to which to send an email
  */
 public function method_confirmEmailAddress($data)
  {
    $this->requirePermission("reference.import");
    $activeUser = $this->getAccessController()->getActiveUser();
    $email = $activeUser->get("email");
    $msg = $this->tr("Bibliograph will send you an email with a link. Open this email on your iOS device and click on the link to get to the Barcode Scanner App. Please enter an email address that you check on your iOS device.") ;
    return new qcl_ui_dialog_Prompt(
      $msg, $email,
      $this->serviceName(),"sendEmailWithLink",
      array($data)
    );
  }
  
 /**
  * Service for sending an email to a mobile device, containing the 
  * link to the mobile web app
  */
  public function method_sendEmailWithLink($email, $data )
  {
    if( !$email ) return "ABORTED";
    $this->requirePermission("reference.import");
    try
    {
      qcl_assert_valid_email( $email );
    }
    catch( InvalidArgumentException $e )
    {
      throw new JsonRpcException(sprintf(
        $this->tr("'%s' is not a valid email address.", $email)
      ));
    }
    $activeUser = $this->getAccessController()->getActiveUser();
    qcl_import("qcl_util_system_Mail");
    $mail = new qcl_util_system_Mail();
    $mail->setSender("Bibliograph");
    $mail->setSenderEmail("donotreply@bibliograph.org");
    $mail->setRecipient($activeUser->get("name"));
    $mail->setRecipientEmail($email);
    $mail->setSubject($this->tr("Link to Barcode Scanner App"));

    $lbr     = "\n\n";
    $token   = $this->getAccessController()->createSiblingSessionToken();
    list($datasource,$folderId) = $data;
    $appUrl  =
      dirname(dirname($this->getApplication()->getClientUrl()))
      . "/bibliograph-mobile/build/"
      . "#sessionId.$token"
      . "!action.scanimport"
      . "!datasource.$datasource!folderId.$folderId";

    $appstoreUrl = "https://itunes.apple.com/de/app/scanner-go/id498868298?mt=8";
    $mail->setBody(
      $this->tr("Please open the following link in your iOS device.") .
      $lbr . $appUrl . $lbr .
      $this->tr("You need the Scanner Go app installed for this to work:") .
      $lbr . $appstoreUrl
    );
    $mail->send();

    $msg = $this->tr("An email has been sent to '%s' with instructions.", $email);
    return new qcl_ui_dialog_Alert($msg);
  }  

}
