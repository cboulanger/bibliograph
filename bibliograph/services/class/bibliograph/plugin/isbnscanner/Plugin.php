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
 * Plugin initializer for z3950 plugin
 */
class isbnscanner_plugin
  extends qcl_application_plugin_AbstractPlugin
{

  /**
   * The descriptive name of the plugin
   * @var string
   */
  protected $name = "ISBN Scanner Plugin";

  /**
   * The detailed description of the plugin
   * @var string
   */
  protected $description  = "A plugin providing the backend for the mobile ISBN scanner application";

  /**
   * An array of urls to load with contain client-side plugin
   * code
   * @var array
   */
  protected $data = array(
    array(
      'name'  => "ISBN Scanner Plugin",
      'url' => 'resource/bibliograph/plugin/isbnscanner/Plugin.js'
    )
  );

  /**
   * Installs the plugin. If an error occurs, a qcl_application_plugin_Exception
   * must be thrown.
   * @return void
   * @throws qcl_application_plugin_Exception
   * @return void|string Can return a message that will be displayed after installation.
   */
  public function install()
  {
    $this->getApplication()
      ->getConfigModel()
      ->createKeyIfNotExists("bibliograph.sortableName.engine",QCL_CONFIG_TYPE_STRING,true,"parser");
    return $this->tr("Please reload the application to finish installing.");
  }

  /**
   * Uninstalls the plugin. Throws qcl_application_plugin_Exception if something
   * goes wrong
   * @throws qcl_application_plugin_Exception
   * @return void|string Can return a message that will be displayed after uninstallation.
   */
  public function uninstall()
  {
    return $this->tr("Please reload the application to finish uninstalling.");
  }
}

