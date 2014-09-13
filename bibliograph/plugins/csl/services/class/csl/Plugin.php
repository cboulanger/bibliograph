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
 * abstract class for classes that implement a plugin
 *
 */
class csl_Plugin
  extends qcl_application_plugin_AbstractPlugin
{
	//-------------------------------------------------------------
  // properties
	//-------------------------------------------------------------

  /**
   * The descriptive name of the plugin
   * @var string
   */
  protected $name = "Citation Style Language (CSL) Plugin";

  /**
   * The detailed description of the plugin
   * @var string
   */
  protected $description = "...";

  /**
   * An associative array containing data on the plugin that is saved when
   * the plugin is installed and that is sent to the client during application 
   * startup.
   * @var array
   */
  protected $data = array(
    'part'      => 'plugin_csl'
  );

  /**
   * Configuration keys to be created if they do not already
   * exists.
   * @var array
   */
  private $configKeys = array(
    "plugin.csl.bibliography.maxfolderrecords" => array(
      "type"      => "number",
      "custom"    => false,
      "default"   => 500,
      "final"     => false
    )
  );

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

 	/**
	 * Installs the plugin. If an error occurs, a qcl_application_plugin_Exception
	 * must be thrown.
	 * @return void
	 * @throws qcl_application_plugin_Exception
	 */
  public function install()
  {
    $this->getApplication()->setupConfigKeys( $this->configKeys );
  }

  /**
   * Uninstalls the plugin. Throws qcl_application_plugin_Exception if something
   * goes wrong
   * @throws qcl_application_plugin_Exception
   */
  public function uninstall()
  {
    // @todo remove config keys?
  }
}
