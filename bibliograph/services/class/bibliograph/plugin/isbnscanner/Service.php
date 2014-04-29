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
qcl_import("qcl_ui_dialog_Prompt");
qcl_import("qcl_ui_dialog_Alert");
qcl_import("qcl_ui_dialog_Popup");

/**
 * Class providing methods and services to import bibliographic data from scanned ISBN numbers
 * Requires the HTTP extension (pecl_http).
 */
class class_bibliograph_plugin_isbnscanner_Service
  extends qcl_data_controller_Controller
{

  public function method_enterIsbnDialog( $datasource, $folderId=null)
  {
    $this->requirePermission("reference.import");
    # check data/folderid
    $activeUser = $this->getAccessController()->getActiveUser();    
    qcl_import("qcl_ui_dialog_Prompt");
    $msg = $this->tr("Please enter the ISBN with a barcode scanner or manually. ") ;
    return new qcl_ui_dialog_Prompt(
      $msg, /*value*/ "",
      $this->serviceName(),"getReferenceDataByIsbn",
      array(array($datasource, $folderId)),
      /*require input*/ true, /*autosubmit after 2 seconds*/ 2
    );    
  }
  
  public function method_displayIsbn( $isbn, $data )
  {
    if ( !$isbn ) return "ABORTED";
    qcl_import("qcl_ui_dialog_Alert");
    $msg = $this->tr("You entered the ISBN %s.", $isbn);
    return new qcl_ui_dialog_Alert(
      $msg, 
      $this->serviceName(),"enterIsbnDialog",
      array($datasource, $folderId)
    );    
  }
  

  public function method_confirmEmailAddress($data)
  {
    $this->requirePermission("reference.import");
    $activeUser = $this->getAccessController()->getActiveUser();
    $email = $activeUser->get("email");
    $msg = $this->tr("Bibliograph will send you an email with a link. Open this email on your iOS device and click on the link to get to the Barcode Scanner App. Please enter an email address that you check on your iOS device.") ;
    return new qcl_ui_dialog_Prompt(
      $msg, $email,
      $this->serviceName(),"sendEmailWithLink",
      array($data);
  }

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

  /**
   * Resolves the ISBN to reference data. For the moment, the worldcat XISBN service is used.
   * Returns a message with information on the result.
   * @param $isbn
   * @param $datasource
   * @return String
   */
  public function method_getReferenceDataByIsbn( $isbn, $data )
  {
    $this->requirePermission("reference.import");
    qcl_assert_valid_string( $isbn, "Missing ISBN" );
    
    $connectors = array(
      "Xisbn"
    );
    
    return new qcl_ui_dialog_Popup(
      $this->tr("Contacting webservices to resolve ISBN..."),
      $this->getServiceName(), "tryConnector",
      array($connectors, $isbn, $data)
    );
  }
  
  public function method_tryConnector( $dummy, $connectors, $data )
  {  
    $this->requirePermission("reference.import");
    
    /*
     * import and instantiate connector
     */
    $namespace = "bibliograph_plugin_isbnscanner_connector_";
    $connectorname $namespace . array_pop($connectors);
    qcl_import($connectorname);
    $connector = new $connectorname();
    
throw new JsonRpcException("not implemented");
    
    if( count($records) == 0 )
    {
      throw new JsonRpcException($this->tr("Could not find any data for ISBN %s.", $isbn));
    }

    $data = $records[0];
    $ref = sprintf(
      "%s (%s): %s. %s:%s. %s.",
      $data['author'],
      $data['year'],
      $data['title'],
      $data['city'],
      $data['publisher'],
      $data['ed']
    );

    $message = $this->tr("Found the following data: %s. Import?", $ref);
    
    qcl_import("qcl_ui_dialog_Confirm");
    return new qcl_ui_dialog_Confirm(
      $message, true,
      $this->serviceName(),"import",
      array($isbn,$data,$datasource, $folderId)
    );
    
  }
  
  public function method_import( $confirmed, $data, $datasource, $folderId )
  {
    return "ABORTED";
    
    /////////////////////////////
    return $ref;

    qcl_import("bibliograph_service_Reference");
    qcl_import("bibliograph_service_Folder");

    $targetReferenceModel =
      bibliograph_service_Reference::getInstance()
        ->getReferenceModel($targetDatasource);

    $targetFolderModel =
      bibliograph_service_Folder::getInstance()
        ->getFolderModel( $targetDatasource );

    $targetFolderModel->load( $targetFolderId );

    foreach( $ids as $id )
    {
      $sourceModel->load($id);
      $targetReferenceModel->create();
      $targetReferenceModel->copySharedProperties( $sourceModel );
      $targetReferenceModel->save();
      $targetFolderModel->linkModel( $targetReferenceModel );
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

}