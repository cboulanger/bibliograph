<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2004-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   *  Christian Boulanger (cboulanger)

************************************************************************ */

qcl_import("qcl_application_plugin_AbstractPlugin");


/**
 * Plugin initializer for the template plugin
 */
class debug_plugin
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
  protected $name = "Debug tools";

  /**
   * The detailed description of the plugin
   * @var string
   */
  protected $description  = "Provides debugging tools, such as backend logfile, selection of log filters, and JSONRPC traffic recording";

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
      'part'    => 'plugin_debug'
  );

  /**
   * Permissions used by this plugin
   * @var array
   */
  protected $permissions = array( "debug.showLogFile","debug.selectFilters","debug.allowDebug" );

  /**
   * Installs the plugin. 
   * @throws qcl_application_plugin_Exception if an error occurs
   * @return void
   */
  public function install()
  {
    $app = $this->getApplication();
    $app->addPreference( "debug.recordJsonRpcTraffic", false );

    $app->addPermission( $this->permissions );
    foreach( array("admin" ) as $role )
    {
      $app->giveRolePermission( $role, $this->permissions );
    }
    return $this->tr("Please reload the application."); // in case of plugin UI additons
  }

  /**
   * Uninstalls the plugin. 
   * @throws qcl_application_plugin_Exception if an error occurs
   */
  public function uninstall()
  {
    $this->getApplication()->removePermission( $this->permissions );
    return $this->tr("Please reload the application."); // in case of plugin UI additons
  }
}