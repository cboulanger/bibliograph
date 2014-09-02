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
qcl_import("fieldsExtensionExmpl_DatasourceModel");

/**
 * abstract class for classes that implement a plugin
 *
 */
class fieldsExtensionExmpl_Plugin
  extends qcl_application_plugin_AbstractPlugin
{
	//-------------------------------------------------------------
  // properties
	//-------------------------------------------------------------


  /**
   * The descriptive name of the plugin
   * @var string
   */
  protected $name = "Field Extension Example Plugin";

  /**
   * The detailed description of the plugin
   * @var string
   */
  protected $description = "This is an example plugin which demonstrates how to extend the reference data schema.";

  /**
   * Flag to indicate whether the plugin is visible to the plugin manager.
   * Set to false since this plugin is only an example and not meant to be used as is.
   * @var bool
   */
  protected $visible = false;

 	/**
	 * Installs the plugin. If an error occurs, a qcl_application_plugin_Exception
	 * must be thrown.
	 * @return void
	 * @throws qcl_application_plugin_Exception
	 */
  public function install()
  {
    fieldsExtensionExmpl_DatasourceModel::getInstance()->registerSchema();
  }

  /**
   * Uninstalls the plugin. Throws qcl_application_plugin_Exception if something
   * goes wrong
   * @throws qcl_application_plugin_Exception
   */
  public function uninstall()
  {
    fieldsExtensionExmpl_DatasourceModel::getInstance()->unregisterSchema();
  }
}
