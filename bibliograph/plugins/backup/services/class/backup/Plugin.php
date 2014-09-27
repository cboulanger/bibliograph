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
 * Plugin initializer for the backup plugin
 */
class backup_plugin
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
  protected $name = "Backup";

  /**
   * The detailed description of the plugin
   * @var string
   */
  protected $description  = "Backup plugin that works with all database backends.";

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
      'part'    => 'plugin_backup'
  );

  /**
   * Installs the plugin. 
   * @throws qcl_application_plugin_Exception if an error occurs
   * @return void
   */
  public function install()
  {
    // Check prerequisites
    $error = array();
    if( ! class_exists("ZipArchive") )
    {
      array_push( $error, "You must install the ZIP extension.");
    }
    if ( ! defined("BIBLIOGRAPH_BACKUP_PATH") )
    {
      array_push( $error, "You must define the BIBLIOGRAPH_BACKUP_PATH constant.");
    }
    
    if ( ! file_exists( BIBLIOGRAPH_BACKUP_PATH ) or ! is_writable( BIBLIOGRAPH_BACKUP_PATH ) )
    {
      array_push( $error, "Directory '" . BIBLIOGRAPH_BACKUP_PATH . "' needs to exist and be writable");
    }
    if ( count($error) == 0 )
    {
      $zip = new ZipArchive();
      $testfile = BIBLIOGRAPH_BACKUP_PATH."/test.zip";
      if ( $zip->open( $testfile, ZIPARCHIVE::CREATE)!==TRUE)
      {
        array_push( $error, "Cannot create backup archive in backup folder - please check file permissions.");
      }
      else
      {
        if ( @unlink( $testfile ) === false )
        {
          array_push( $error, "Cannot delete files in backup folder - please check file permissions.");
        }
      }
    }
    
    if( count($error) )
    {
      throw new qcl_application_plugin_Exception( implode(" ", $error) );
    }
    
    // preferences and permissions
    $app = $this->getApplication();
    $app->addPreference( "backup.daysToKeepBackupFor", 3 );
    $app->addPermission(array("backup.create","backup.restore","backup.delete"));
    foreach( array("admin", "manager" ) as $role )
    {
      $app->giveRolePermission( $role, array("backup.create","backup.restore","backup.delete") );
    }
    
    return $this->tr("Reload application to finish installation.");
  }

  /**
   * Uninstalls the plugin. 
   * @throws qcl_application_plugin_Exception if an error occurs
   */
  public function uninstall()
  {
    $this->getApplication()->removePermission(array("backup.create","backup.restore","backup.delete"));
    return $this->tr("Reload application to finish uninstallation");
  }
}