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

/**
 *
 */
class bibliograph_service_Application
  extends qcl_data_controller_Controller
{
  protected $help_topics = array(
    "access-control" => array(
      "de"  => "verwaltung/verwaltung-der-zugangskontrolle",
      "en"  => "administration/access-control"
    )
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
    if( $topic and  isset( $this->help_topics[$topic][$locale] ) )
    {
      $url .= "/" . $this->help_topics[$topic][$locale];
    }
    header("location: $url");
    exit;
  }

  public function method_reportBugDialog()
  {
    if ( ! defined("GITHUB_API_TOKEN") )
    {
      throw new JsonRpcException("No Github API key defined!");
    }    
    
    $message = implode("",array(
      "<p style='font-weight:bold'>",
      _("Thank you for taking time to make Bibliograph better."),
      "</p><p>",
      _("After selecting the type of report (1), please provide a detailed description of the error or the requested feature (2). "), " ",
      _("If the application has reported an error, please copy & paste or write down the error message (3). "), " ",
      _("Finally, If you want to be notified about the progress of your request, please provide your email address (4)."),
      "</p>"
    ));

    qcl_import("qcl_ui_dialog_Form");
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

    $title = 'New ' . $data->reportType;
    $body  = "Problem: \n\n" . $data->problem;
    
    if( trim( $data->error) )
    {
      $body .= "\n\nError message:\n\n" . $data->error;
    }

    if( trim( $data->email) )
    {
      $body .= "\n\nContact: " . $data->email;
    }    
    /*
     * submit to github
     */
    require "qcl/lib/Github//Autoloader.php";
    Github_Autoloader::register();
    $github = new Github_Client();
    $github->authenticate(GITHUB_API_USER, GITHUB_API_TOKEN);
    $info = $github->getIssueApi()->open('cboulanger', 'bibliograph', $title, $body);
    $github->getIssueApi()->addLabel('cboulanger', 'bibliograph', $data->reportType, $info['number']);
    $github->getIssueApi()->addLabel('cboulanger', 'bibliograph', "User-contributed", $info['number']);
    $github->deauthenticate();
    //$url = $info['html_url'];
    
    /*
     * return the alert
     */
    qcl_import("qcl_ui_dialog_Alert");
    return new qcl_ui_dialog_Alert(
      _("Thank you for your report.") .
      ( isset( $data->email )
        ? ( " " . _("You will be notified as soon as it can be considered.") )
        : "" )
    );
  }
}
?>