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
    $activeUser = $this->getAccessController()->getActiveUser();    
    qcl_import("qcl_ui_dialog_Prompt");
    $msg = $this->tr("Please enter the ISBN with a barcode scanner or manually. ") ;
    return new qcl_ui_dialog_Prompt(
      $msg, /*value*/ "",
      $this->serviceName(),"displayIsbn",
      array($datasource, $folderId),
      /*require input*/ true, /*autosubmit after 2 seconds*/ 2
    );    
  }
  
  public function method_displayIsbn( $isbn, $datasource, $folderId=null)
  {
    if ( !$isbn ) return "ABORTED";
    
    $this->requirePermission("reference.import");
    qcl_import("qcl_ui_dialog_Alert");
    $msg = $this->tr("You entered the ISBN %s.", $isbn);
    return new qcl_ui_dialog_Alert(
      $msg, 
      $this->serviceName(),"enterIsbnDialog",
      array($datasource, $folderId)
    );    
  }
  
  


  public function method_confirmEmailAddress($datasource, $folderId=null)
  {
    $this->requirePermission("reference.import");
    $activeUser = $this->getAccessController()->getActiveUser();
    $email = $activeUser->get("email");
    qcl_import("qcl_ui_dialog_Prompt");
    $msg = $this->tr("Bibliograph will send you an email with a link. Open this email on your iOS device and click on the link to get to the Barcode Scanner App. Please enter an email address that you check on your iOS device.") ;
    return new qcl_ui_dialog_Prompt(
      $msg, $email,
      $this->serviceName(),"sendEmailWithLink",
      array($datasource, $folderId));
  }

  public function method_sendEmailWithLink($email, $datasource, $folderId=null )
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

    qcl_import("qcl_ui_dialog_Alert");
    $msg = $this->tr("An email has been sent to '%s' with instructions.", $email);
    return new qcl_ui_dialog_Alert($msg);
  }

  /**
   * Imports a reference into the current database. For the moment, the worldcat XISBN service is used.
   * Returns a message with information on the result.
   * todo support different services.
   * @param $isbn
   * @param $datasource
   * @return String
   */
  public function method_import( $isbn, $datasource, $folderId=null)
  {
    $this->requirePermission("reference.import");

    qcl_assert_valid_string( $isbn, "Missing ISBN" );
    qcl_assert_valid_string( $datasource, "Missing datasource" );
    //qcl_assert_integer( $folderId, "Invalid folder id" );

    $xisbnUrl = sprintf(
      "http://xisbn.worldcat.org/webservices/xid/isbn/%s?method=getMetadata&format=json&fl=*",
      $isbn
    );
    $r = new HttpRequest($xisbnUrl);
    $r->send();
    if( $r->getResponseCode() != 200)
    {
      throw new JsonRpcException( "Could not retrieve data from ISBN service: " . $r->getResponseStatus() );
    }
    $json = json_decode( $r->getResponseBody(), true );
    $records = $json['list'];
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