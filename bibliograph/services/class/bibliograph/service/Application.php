<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2010 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_controller_Controller" );
qcl_import("qcl_ui_dialog_Alert");
qcl_import("qcl_ui_dialog_Form");
qcl_import("qcl_util_system_Mail");

/**
 *
 */
class bibliograph_service_Application
  extends qcl_data_controller_Controller
{
  protected $help_topics = array(
    "access-control" =>  "administration/access-control"
  );


  /*
  ---------------------------------------------------------------------------
     API METHODS
  ---------------------------------------------------------------------------
  */

  public function method_getOnlineHelpUrl($topic=null)
  {
    $locale = $this->getApplication()->getLocaleManager()->getLocale();
    $url = "https://sites.google.com/a/bibliograph.org/docs-v2-" . $locale;
    if( $topic and  isset( $this->help_topics[$topic] ) )
    {
      $url .= "/" . $this->help_topics[$topic];
    }
    elseif ( $topic )
    {
      $url .= "/" . $topic;
    }
    header("location: $url");
    exit;
  }

  public function method_reportBugDialog()
  {

    $message = implode("",array(
      "<p style='font-weight:bold'>",
      _("Thank you for taking time to make Bibliograph better."),
      "</p><p>",
      _("After selecting the type of report (1), please provide a detailed description of the error or the requested feature (2). "), " ",
      _("If the application has reported an error, please copy & paste or write down the error message (3). "), " ",
      _("Finally, If you want to be notified about the progress of your request, please provide your email address (4)."),
      "</p>"
    ));

    return new qcl_ui_dialog_Form(
      $message,
      array(
        'reportType' => array(
          'type'        => "selectbox",
          'label'       => "1. " . _("Type of report"),
          'options'     => array(
            array( 'value' => 'Bug',     				 'label' => _('Problem/Bug report') ),
            array( 'value' => 'Feature Request', 'label' => _('Feature request') )
          )
        ),
        'problem'  => array(
          'label'       => "2. " . _("Problem"),
          'type'        => "textarea",
          'lines'       => 5,
          'required'    => true
        ),
        'error'  => array(
          'label'       => "3. " . _("Error Message"),
          'type'        => "textarea",
          'lines'       => 3
        ),
        'email'  => array(
          'label'       => "4. " . _("Email"),
          'type'        => "textfield"
        )
      ),
      /* allow cancel */ true,
      $this->serviceName(),
      "sendBugReport"
    );
  }

  public function method_sendBugReport( $data )
  {
    /*
     * user pressed "cancel" button
     */
    if ( $data === null )
    {
      return "ABORTED";
    }
    
    $this->requirePermission("application.reportBug");
    $app = $this->getApplication();
    $configModel = $app->getConfigModel();
    $applicationTitle = $configModel->keyExists("application.title")
      ? $configModel->getKey("application.title")
      : $app->name();
    $adminEmail  = $app->getIniValue("email.admin");
    $subject = $applicationTitle . ': New ' . $data->reportType;
    $body  = "Problem: \n\n" . $data->problem;
    $body .= "\n\nError message:\n\n" . $data->error;

    // send email
    $mail = new qcl_util_system_Mail( array(
      'senderEmail'     => $adminEmail,
      'replyTo'         => either( trim($data->email), $adminEmail ),
      'recipient'       => "Bibliograph Developer",
      'recipientEmail'  => $adminEmail,
      'subject'         => $subject,
      'body'            => $body
    ) );
    $mail->send();
    
    /*
     * return the alert
     */
    return new qcl_ui_dialog_Alert(
      _("Thank you for your report.") .
      ( isset( $data->email )
        ? ( " " . _("You will be notified as soon as it can be considered.") )
        : "" )
    );
  }
  
  /**
   * Reset the application
   */
  public function method_reset()
  {
    $this->requirePermission("application.reset");
    
    // Clear internal caches. 
    qcl_data_model_db_ActiveRecord::resetBehaviors();
    
    // Clear application cache
    $this->getApplication()->getCache()->disposePersistenceData();
  }
}
