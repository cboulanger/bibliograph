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
    qcl_import("qcl_ui_dialog_Prompt");
    $msg = $this->tr("Please enter the ISBN with a barcode scanner or manually. ") ;
    return new qcl_ui_dialog_Prompt(
      $msg, /*value*/ "",
      $this->serviceName(),"getReferenceDataByIsbn",
      array(array($datasource, $folderId)),
      /*require input*/ true, /*autosubmit after 2 seconds*/ 2
    );    
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
      $this->serviceName(), "iterateConnectors",
      array($connectors, $isbn, $data)
    );
  }
  
  /**
   * Private function to return the connector object for a given name
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
   */
  public function method_iterateConnectors( $dummy, $connectors, $isbn, $data )
  {  
    $this->requirePermission("reference.import");
    
    if ( ! count($connectors) )
    {
      throw new JsonRpcException($this->tr("Could not find any data for ISBN %s.", $isbn));
    }
    
    $connector = $this->getConnectorObject( $connectors[0] );
    return new qcl_ui_dialog_Popup(
      $this->tr("Contacting %s...", $connector->getDescription() ),
      $this->serviceName(), "tryConnector",
      array( $connectors, $isbn, $data )
    );
  }
  
  /**
   * Service to try the first of the connector names in the given array
   */
  public function method_tryConnector( $dummy, $connectors, $isbn, $data )
  {

    $connector = $this->getConnectorObject( $connectors[0] );
    $records = $connector->getDataByIsbn( $isbn );
    
    if( count($records) == 0 )
    {
      array_shif($connectors);
      return $this->method_iterateConnectors( null, $connectors, $isbn, $data );
    }

    $d = $records[0];
    $ref = sprintf(
      "%s (%s): %s. %s:%s. %s.",
      $d['author'],
      $d['year'],
      $d['title'],
      $d['address'],
      $d['publisher'],
      $d['editor']
    );
    
    $message = $this->tr("Found the following data: %s. Import?", $ref);
    qcl_import("qcl_ui_dialog_Confirm");
    return new qcl_ui_dialog_Confirm(
      $message, true,
      $this->serviceName(),"import",
      array($d, $data)
    );
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