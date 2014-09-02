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
qcl_import("bibliograph_model_import_RegistryModel");
qcl_import("bibliograph_model_export_RegistryModel");

/**
 * The path to the bibutils executables. Must contain a trailing slash
 * since the exeuctable name is appended to the path. If not defined,
 * the bibutils executables must be on the PATH
 */
if( !defined('BIBUTILS_PATH') )
{
  define('BIBUTILS_PATH','');
}


/**
 * Plugin initializer for the bibutils plugin
 */
class bibutils_plugin
  extends qcl_application_plugin_AbstractPlugin
{

  /**
   * The descriptive name of the plugin
   * @var string
   */
  protected $name = "Bibutils Plugin";

  /**
   * The detailed description of the plugin
   * @var string
   */
  protected $description  = "A plugin providing import and export filters using the bibutils binaries";

  /**
   * An array of urls to load with contain client-side plugin
   * code
   * @var array
   */
  protected $data = array();

  /**
   * Installs the plugin. If an error occurs, a qcl_application_plugin_Exception
   * must be thrown.
   * @throws JsonRpcException
   * @return void
   */
  public function install()
  {

    qcl_import("qcl_util_system_Executable");
    $xml2bib = new qcl_util_system_Executable( BIBUTILS_PATH . "xml2bib");
    $xml2bib->exec("-v");
    $stdErr = $xml2bib->getStdErr();
    $this->info($stdErr);
    if ( ! strstr( $stdErr, "bibutils" ) )
    {
      $this->warn( "Error installing plugin: " . $xml2bib->getStdErr() );
      throw new JsonRpcException($this->tr("Could not call the bibutils commands through the shell. Please check your setup."));
    }

    /*
     * install import formats
     */
    $importRegistry  = bibliograph_model_import_RegistryModel::getInstance();
    $importFormatDir = dirname( __FILE__ ) . "/import";
    foreach( scandir( $importFormatDir ) as $file )
    {
      if( $file[0] != "." )
      {
        $class = "bibutils_import_" . substr( $file, 0, -4 );
        $importRegistry->addFromClass($class);
      }
    }

    /*
     * install export formats
     */
    $exportRegistry = bibliograph_model_export_RegistryModel::getInstance();
    $exportFormatDir = dirname( __FILE__ ) . "/export";
    foreach( scandir( $exportFormatDir ) as $file )
    {
      if( $file[0] != "." )
      {
        $class = "bibutils_export_" . substr( $file, 0, -4 );
        $exportRegistry->addFromClass($class);
      }
    }
  }

  /**
   * Uninstalls the plugin. Throws qcl_application_plugin_Exception if something
   * goes wrong
   * @throws qcl_application_plugin_Exception
   */
  public function uninstall()
  {
    $importRegistry = bibliograph_model_import_RegistryModel::getInstance();
    $importRegistry->deleteWhere( array( 'type'  => 'bibutils' ) );
    $exportRegistry = bibliograph_model_export_RegistryModel::getInstance();
    $exportRegistry->deleteWhere( array( 'type'  => 'bibutils' ) );
  }
}