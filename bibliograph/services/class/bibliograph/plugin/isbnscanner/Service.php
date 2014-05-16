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
    # check data/folderid
    $activeUser = $this->getAccessController()->getActiveUser();    
    $msg = $this->tr("Please enter the ISBN:") ;
    return new qcl_ui_dialog_Prompt(
      $msg, /*value*/ "",
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
      "Xisbn"
    );
    
    return new qcl_ui_dialog_Popup(
      $this->tr("Contacting webservices to resolve ISBN..."),
      $this->serviceName(), "iterateConnectors",
      array($connectors, $isbn, $data)
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
   * @param $dummy First parameter returned by client can be ignored
   * @param array $connectors Array of connector names
   * @param string $isbn The ISBN to search for
   * @param array $data Additional data
   * @return qcl_ui_dialog_Popup
   */
  public function method_iterateConnectors( $dummy, array $connectors, $isbn, array $data )
  {  
    $this->requirePermission("reference.import");

    if ( ! count($connectors) )
    {
      throw new JsonRpcException($this->tr("Could not find any data for ISBN %s.", $isbn));
    }
    
    $connector = $this->getConnectorObject( $connectors[0] );
    return new qcl_ui_dialog_Popup(
      $this->tr("Contacting %s. Please wait...", $connector->getDescription() ),
      $this->serviceName(), "tryConnector",
      array( $connectors, $isbn, $data )
    );
  }

  /**
   * Given a record with BibTeX-conformant field names, return a formatted reference.
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
   * @param $dummy First parameter returned by client can be ignored
   * @param array $connectors Array of connector names
   * @param string $isbn The ISBN to search for
   * @param array $data Additional data
   * @return qcl_ui_dialog_Popup
   */
  public function method_tryConnector( $dummy, $connectors, $isbn, $data )
  {
    $this->requirePermission("reference.import");

    $connector = $this->getConnectorObject( $connectors[0] );
    $records = $connector->getDataByIsbn( $isbn );

    /*
     * if no result, try next connector
     */
    if( count($records) == 0 )
    {
      array_shift($connectors);
      return $this->method_iterateConnectors( null, $connectors, $isbn, $data );
    }

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
      $msg = $this->tr(
        "Found: %s<br><br><b>Possible duplicates:</b><br>%s",
        $this->formatReference( $record ),
        join("<br>", $refs)
      );
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
      $this->serviceName(),"importReferenceData",
      array($record, $data)
    );
  }

  /**
   * Import the found reference data into the database
   * @param null|bool $response
   * @param object $record
   * @param array $data
   * @return qcl_ui_dialog_Prompt|string
   */
  public function method_importReferenceData( $response, $record, array $data )
  {
    // CANCEL button -> exit
    if( ! $response )
    {
      $this->dispatchClientMessage("plugin.isbnscanner.ISBNInputListener.start");
      return "CANCEL";
    }

    list( $datasource, $folderId ) = $data;

    // SKIP
    if ( $response == "skip" )
    {
      return $this->method_enterIsbnDialog( $datasource, $folderId );
    }

    $this->requirePermission("reference.import");

    /*
     * import
     */
    $dsModel = $this->getDatasourceModel($datasource);
    $referenceModel = $dsModel->getInstanceOfType("reference");

    $record = object2array( $record );
    $record['createdBy']= $this->getActiveUser()->namedId();
    $referenceModel->create($record);

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
      return "OK";
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
