<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

qcl_import( "qcl_data_file_PersistentObject" );

class bibliograph_ApplicationCache
  extends qcl_data_file_PersistentObject
{
  public $setup = false;
  public $resetCache = false;
  public $dataImported = false;
  public $datasourcesRegistered = false;
  public $datasourcesCreated = false;
  public $registeredBibliographSchema = false;
  public $createdHttpsEnforceConfig = false;
  public $createdImportDatasource = false;
  public $createdExportDatasource = false;
  public $registeredFileStorageDatasources = false;
  public $addedBibtexFormat = false;

  /**
   * Returns singleton instance
   * @return bibliograph_ApplicationCache
   */
  static public function getInstance()
  {
    /*
     * create instance
     */
    $instance = qcl_getInstance( __CLASS__ );

    /*
     * inform about cache file
     */
    if($instance->isNew())
    {
      $msg = "Created application data cache at " .
        $instance->getPersistenceBehavior()->getResourcePath();
      qcl_log_Logger::getInstance()->log($msg, BIBLIOGRAPH_LOG_APPLICATION);
      $instance->isNew = false; //hack
    }

    return $instance;
  }
}
?>