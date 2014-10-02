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

qcl_import("qcl_data_controller_Controller");
qcl_import("qcl_ui_dialog_Confirm");
qcl_import("qcl_ui_dialog_Alert");
qcl_import("bibliograph_service_Access");

class mdbackup_Backup
  extends qcl_data_controller_Controller
{

  /*
  ---------------------------------------------------------------------------
     CLASS PROPERTIES
  ---------------------------------------------------------------------------
  */

  /**
   * Whether datasource access should be restricted according
   * to the current user. The implementation of this behavior is
   * done by the getAccessibleDatasources() and checkDatasourceAccess()
   * methods.
   *
   * @var bool
   */
  protected $controlDatasourceAccess = true;
  
  
  /**
   * The extension of the backup file. Must contain the preceding period.
   * @var string
   */
  protected $backup_file_extension = ".mdbackup.zip";

  /*
  ---------------------------------------------------------------------------
     INTERNAL METHODS
  ---------------------------------------------------------------------------
  */

  /**
   * Checks if user has access to the given datasource. If not,
   * throws JsonRpcException.
   * @param string $datasource
   * @return void
   * @throws JsonRpcException
   */
  public function checkDatasourceAccess( $datasource )
  {
    bibliograph_service_Access::getInstance()->checkDatasourceAccess( $datasource );
  }


  /*
  ---------------------------------------------------------------------------
     API
  ---------------------------------------------------------------------------
  */

  /**
   * Method to create a backup of a datasource
   * @param $datasource
   * @throws JsonRpcException
   * @return string
   *    Returns the name of the ZIP-Archive with the backups
   */
  public function createBackup( $datasource )
  {
    $dsModel = $this->getDatasourceModel( $datasource );
    $tables = array();
    foreach( $dsModel->modelTypes() as $type )
    {
      $model = $dsModel->getInstanceOfType( $type );
      $tables[] = $model->getQueryBehavior()->getTableName();
      foreach( $model->getRelationBehavior()->relations() as $relation )
      {
        switch ( $model->getRelationBehavior()->getRelationType( $relation ) )
        {
          case  QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY:
            $tables[] = $model->getRelationBehavior()->getJoinTableName( $relation, true );
            $targetModel = $model->getRelationBehavior()->getTargetModel( $relation );
            $tables[] = $targetModel->getQueryBehavior()->getTableName();
            break;
          case QCL_RELATIONS_HAS_MANY:
            $targetModel = $model->getRelationBehavior()->getTargetModel( $relation );
            $tables[] = $targetModel->getQueryBehavior()->getTableName();
            break;
        }
      }
    }

    $tables = array_unique( $tables );
    $adapter = $dsModel->getQueryBehavior()->getAdapter();
    $database = $dsModel->getDatabase();

    /*
     * zipfile
     */
    $backupPath = BACKUP_PATH;
    $tmpPath    = QCL_TMP_PATH;

    $zipfile = 
      realpath( $backupPath ) . "/" . $datasource . 
      "_" . date("Y-m-d_H-i-s") . $this->backup_file_extension;
      
    $zip = new ZipArchive();
    if ($zip->open($zipfile, ZIPARCHIVE::CREATE)!==TRUE)
    {
      $this->warn("Cannot create file '$zipfile' in '$backupPath'");
      throw new JsonRpcException("Cannot create backup archive - please check file permissions.");
    }

    /*
     * create zip archive from table dump
     */
    $files=array();
    try
    {
      foreach( $tables as $table )
      {
        $file = qcl_realpath( $tmpPath ) . "/" . $table . "-" . md5( microtime() );
        $adapter->exec( "SELECT * INTO OUTFILE '$file' FROM `$database`.`$table`");
        $zip->addFile( $file, $table . ".txt" );
        $files[] = $file;
      }
    }
    catch ( PDOException $e )
    {
      $zip->close();
      $this->warn( $e->getMessage() );
      throw new JsonRpcException( "You don't seem to have the necessary MySql Privileges to make a backup. You need at least the global 'SELECT' and 'FILE' privilege" );
    }

    $zip->close();

    /*
     * delete files
     */
    foreach( $files as $file )
    {
      if ( ! @unlink( $file ) )
      {
        $this->warn("Cannot create delete '$zipfile'");
      }
    }

    /*
     * return the name of the backup file created
     */
    return basename( $zipfile );
  }

  /**
   * @param $datasource
   * @return qcl_ui_dialog_Confirm
   */
  public function method_dialogCreateBackup( $datasource )
  {
    return new qcl_ui_dialog_Confirm(
      $this->tr("Do you want to backup Database '%s'", $datasource),
      null,
      $this->serviceName(), "createBackup", array( $datasource )
    );
  }

  /**
   * Do the backup
   * @param $go
   * @param $datasource
   * @return qcl_ui_dialog_Alert
   */
  public function method_createBackup( $go, $datasource )
  {
    if ( ! $go )
    {
      return "ABORTED";
    }

    $this->requirePermission("mdbackup.create");
    qcl_assert_valid_string( $datasource );
    $this->createBackup( $datasource );

    return new qcl_ui_dialog_Alert(
      $this->tr( "Backup has been created." )
    );
  }

  /**
   * @param $datasource
   * @return qcl_ui_dialog_Confirm
   */
  public function method_dialogRestoreBackup( $datasource )
  {
    $this->requirePermission("mdbackup.restore");
    $msg = $this->tr("Do you really want to restore Database '%s'? All existing data will be lost!", $datasource);
    qcl_import("qcl_ui_dialog_Confirm");
    return new qcl_ui_dialog_Confirm(
      $msg,
      null,
      $this->serviceName(), "dialogChooseBackup", array( $datasource )
    );
  }

  /**
   * Service to present the user with a choice of backups
   * @param $form
   * @param $datasource
   * @return qcl_ui_dialog_Form|string
   * @throws JsonRpcException
   */
  public function method_dialogChooseBackup( $form, $datasource )
  {
    if ( $form === false )
    {
      return "ABORTED";
    }

    $this->requirePermission("mdbackup.restore");
    $backupPath = BACKUP_PATH;
    $options = array();
    $files = scandir($backupPath);
    rsort( $files );
    foreach( $files as $file )
    {
      if ( $file[0] == "." ) continue;
      if ( substr( $file, -4) != $this->backup_file_extension ) continue;
      $name = explode( "_", substr( basename($file),0, -4) );
      if ( $name[0] != $datasource ) continue;
      $options[] = array(
        'label'   => $name[1] . ", " . $name[2],
        'value'   => $file
      );
    }

    if( ! count( $options) )
    {
      throw new JsonRpcException("No backup sets available.");
    }

    $formData = array(
      'file'  => array(
        'label'   => _("Backup from "),
        'type'    => "selectbox",
        'options' => $options,
        'width'   => 200,
      )
    );
    qcl_import("qcl_ui_dialog_Form");
    return new qcl_ui_dialog_Form(
      $this->tr("Please select the backup set to restore into database '%s'",$datasource),
      $formData,
      true,
      $this->serviceName(), "restoreBackup", array( $datasource )
    );
  }

  /**
   * @param $data
   * @param $datasource
   * @return string
   * @throws JsonRpcException
   */
  public function method_restoreBackup( $data, $datasource )
  {
    if ( $data === null )
    {
      return "ABORTED";
    }

    $this->requirePermission("mdbackup.restore");

    $backupPath = BACKUP_PATH;
    $tmpPath    = QCL_TMP_PATH;

    $zipfile = $backupPath . "/" . $data->file;
    if( ! file_exists( $zipfile ) )
    {
      $this->warn("File '$zipfile' does not exist.");
      throw new JsonRpcException(_("Backup file does not exist."));
    }

    $zip = new ZipArchive();
    if ($zip->open($zipfile, ZIPARCHIVE::CHECKCONS )!==TRUE)
    {
      $this->warn("Cannot open file '$zipfile'");
      throw new JsonRpcException("Cannot open backup archive");
    }

    $lockfile = $backupPath . "/$datasource.lock";
    if( file_exists($lockfile) )
    {
      throw new JsonRpcException("Cannot restore backup. Backup is locked.");
    }
    touch( $lockfile );

    $zip->extractTo( $tmpPath );

    $dsModel = $this->getDatasourceModel( $datasource );
    $adapter = $dsModel->getQueryBehavior()->getAdapter();
    $database = $dsModel->getDatabase();

    $problem = null;
    for ( $i=0; $i < $zip->numFiles; $i++ )
    {
      $name = $zip->getNameIndex( $i );
      $table = substr( $name, 0, -4 );
      $file = "$tmpPath/$table.txt";
      $adapter->exec( "TRUNCATE TABLE `$database`.`$table`");
      $adapter->exec( "LOAD DATA INFILE '$file' INTO TABLE `$database`.`$table`");
      if ( !@unlink( $file ) )
      {
        $problem = true;
        $this->warn("Cannot delete temporary backup file '$file'");
      }
    }

    $zip->close();

    if ( !@unlink( $lockfile ) )
    {
      $problem = true;
      $this->warn("Cannot delete lockfile file '$lockfile'");
    };

    /*
     * reset transaction ids
     */
    foreach( $dsModel->modelTypes() as $type )
    {
      $model = $dsModel->getInstanceOfType($type);
      $model->resetTransactionId();
    }

    /*
     * broacast message
     */
    $this->broadcastClientMessage("backup.restored",array(
      "datasource" => $datasource
    ) );


    $msg = $this->tr( "Backup has been restored." );
    if ($problem ) $msg .= $this->tr( "Please check logfile for problems." );

    return new qcl_ui_dialog_Alert($msg);

  }

  /**
   * Confirmation dialog for deleting backups
   */
  public function method_dialogDeleteBackups( $datasource )
  {
    $this->requirePermission("mdbackup.delete");
    $days = $this->getApplication()->getPreference("mdbackup.daysToKeepBackupFor");
    return new qcl_ui_dialog_Confirm(
      $this->tr("All backups older than %s days will be deleted. Proceed?", $days),
      null,
      $this->serviceName(), "deleteBackups", array( $datasource )
    );
  }

  /**
   * Service to delete all backups of this datasource older than one day
   */
  public function method_deleteBackups( $confirmed, $datasource )
  {
    if( ! $confirmed ) return "CANCELLED";

    $this->requirePermission("mdbackup.delete");

    $backupPath = BACKUP_PATH;
    $filesDeleted = 0;
    $problem = false;
    foreach( scandir($backupPath) as $file )
    {
      if ( $file[0] == "." ) continue;
      if ( substr( $file, -4) != $this->backup_file_extension ) continue;
      $name = explode( "_", substr( basename($file),0, -4) );
      if ( $name[0] != $datasource ) continue;
      if ( $name[1] != date("Y-m-d") ){
        if ( !@unlink( "$backupPath/$file" ) )
        {
          $problem = true;
          $this->warn("Cannot delete backup file '$file'");
        }
        else
        {
          $filesDeleted++;
        }
      }
    }
    $msg = $this->tr("%s backups were deleted.", $filesDeleted);
    if( $problem ) $msg .= $this->tr("There was a problem. Please examine the log file.");
    return new qcl_ui_dialog_Alert($msg);
  }
}