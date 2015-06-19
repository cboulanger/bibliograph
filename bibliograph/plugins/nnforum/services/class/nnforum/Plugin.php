<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

qcl_import("qcl_application_plugin_AbstractPlugin");
define ('NNFORUM_USERS_DIR', QCL_VAR_DIR . "/nnforum_users" );

/**
 * Plugin initializer for the nnforum plugin
 */
class nnforum_plugin
  extends qcl_application_plugin_AbstractPlugin
{
  
  /**
   * Flag to indicate whether the plugin is visible to the plugin manager.
   * Change to true to activate plugin.
   * @var bool
   */
  protected $visible = true;

  /**
   * The descriptive name of the plugin
   * @var string
   */
  protected $name = "No-Nonsense Forum Plugin";

  /**
   * The detailed description of the plugin
   * @var string
   */
  protected $description  = "This integrates a forum for user questions";

  /**
   * An associative array containing data on the plugin that is saved when
   * the plugin is installed and that is also sent to the client during application 
   * startup.
   * 
   * The array contains the following keys and values: 
   * 'source'     - (string) url to load a javascript file from
   * 'part'       - (string) name of the part to load at application startup
   * 
   * @var array
   */
  protected $data = array(
      // 'source'  => "https://code.jquery.com/jquery-2.1.1.min.js",
      'part'    => 'plugin_nnforum'
  );

  /**
   * Installs the plugin. 
   * @throws qcl_application_plugin_Exception if an error occurs
   * @return void
   */
  public function install()
  {
    // create user folder
    @mkdir(NNFORUM_USERS_DIR);
    
    $app = $this->getApplication();
    
    // google search domain pref
    $app->addPreference( "nnforum.searchdomain", $_SERVER['SERVER_NAME'], false, true );
    $app->addPreference( "nnforum.readposts", 0, true );
    
    // permissions
    $app->addPermission( array(
      "nnforum.view"
    ) );
    $app->giveRolePermission( "user", array(
      "nnforum.view"
    ) );
    
    
    return;
  }

  /**
   * Uninstalls the plugin. 
   * @throws qcl_application_plugin_Exception if an error occurs
   */
  public function uninstall()
  {
    // remove permissions
    $this->getApplication()->removePermission(array(
      "nnforum.view"
    ));
    // remove user files
    $files = glob( NNFORUM_USERS_DIR . '/*.txt'); 
    foreach($files as $file){ 
    if(is_file($file))
        unlink($file); 
    }
    return;
  }
}