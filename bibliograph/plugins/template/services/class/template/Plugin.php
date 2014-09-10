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


/**
 * Plugin initializer for the template plugin
 */
class template_plugin
  extends qcl_application_plugin_AbstractPlugin
{
  
  /**
   * Flag to indicate whether the plugin is visible to the plugin manager.
   * Change to true to activate plugin.
   * @var bool
   */
  protected $visible = false;

  /**
   * The descriptive name of the plugin
   * @var string
   */
  protected $name = "A plugin skeleton";

  /**
   * The detailed description of the plugin
   * @var string
   */
  protected $description  = "The skeleton of a Bibliograph plugin";

  /**
   * An array of maps of key-value pairs containing data on the plugin that is saved when
   * the plugin is installed and sent to the client. Usually, only one map is needed.
   * The map contains the following keys: 'name' (the descriptive name of the plugin),
   * 'url' (url to load a javascript file from) OR 'part' (name of the part that is loaded
   * during application startup) and 'namespace' (the top namespace of the plugin).
   * If your plugin has no frontend code that needs to be loaded, the data array
   * can be empty.
   * @var array
   */
  protected $data = array(
     array(
      'name'      => "Unfinished Bibliograph Plugin",
      // 'url'       => "",
      'part'      => 'plugin_template', 
      'namespace' => 'template'
    )
  );

  /**
   * Installs the plugin. 
   * @throws qcl_application_plugin_Exception if an error occurs
   * @return void
   */
  public function install()
  {
    // code to install the plugin
    return "Message that is displayed when installation was successful";
  }

  /**
   * Uninstalls the plugin. 
   * @throws qcl_application_plugin_Exception if an error occurs
   */
  public function uninstall()
  {
    // code to uninstall the plugin
    return "Message that is displayed when uninstallation was successful";
  }
}