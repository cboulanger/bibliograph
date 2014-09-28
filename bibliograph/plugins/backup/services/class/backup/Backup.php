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
qcl_import("qcl_ui_dialog_Form");
qcl_import("qcl_ui_dialog_Confirm");
qcl_import("qcl_ui_dialog_Alert");
qcl_import("qcl_ui_dialog_ServerProgress");
qcl_import("bibliograph_service_Access");

class backup_Backup
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
  protected $backup_file_extension = ".backup.zip";
  
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
   * Start the backup
   * @param string $datasource
   * @param string $progressWidgetId
   */
  public function method_createBackup( $datasource, $progressWidgetId  )
  {
    $this->requirePermission("backup.create");
    qcl_assert_valid_string( $datasource );
    $progressBar = new qcl_ui_dialog_ServerProgress($progressWidgetId );
    try
    {
      $zipfile = $this->createBackup( $datasource, $progressBar );  
    }
    catch( Exception $e)
    {
      $progressBar->error( $e->getMessage() );  
    }
    $progressBar->complete("Created Backup ZIP archive '$zipfile'.");
  }
  
  /**
   * Method to create a backup of a datasource
   * @param $datasource
   * @throws JsonRpcException
   * @return string
   *    Returns the name of the ZIP-Archive with the backups
   */
  public function createBackup( $datasource, qcl_ui_dialog_ServerProgress $progressBar=null )
  {
    $dsModel = $this->getDatasourceModel( $datasource );
    $zipfile = 
      BIBLIOGRAPH_BACKUP_PATH . "/" . $datasource . 
      "_" . date("Y-m-d_H-i-s") . $this->backup_file_extension;
      
    $zip = new ZipArchive();
    $zip->open($zipfile, ZIPARCHIVE::CREATE);

    // create temporary file with model data serialized as CSV
    $modelTypes = $dsModel->modelTypes();
    $step1 = 100/count( $modelTypes );
    foreach( $modelTypes  as $index1 => $type )
    {
      $model = $dsModel->getInstanceOfType( $type );
      $tmpFileHandle = tmpfile();
      $metaDatas = stream_get_meta_data($tmpFileHandle);
      $tmpFilename = $metaDatas['uri'];
      
      $step2 = $step1/$model->countRecords();
      
      $model->findAll();
      $index2 = 0;
      while( $model->loadNext() )
      {
        fputcsv( $tmpFileHandle, $model->data() );
        if( $progressBar )
        {
          $progress = $step1*$index1 + $step2*$index2;
          $progressBar->setProgress($progress,"Backing up '$type' ...");          
        }
      }
      $zip->addFile( $tmpFilename, $type . ".csv" );
      fclose( $tmpFileHandle );
    }
    $zip->close();
    return basename( $zipfile );
  }
  

  /**
   * @param $datasource
   * @return qcl_ui_dialog_Confirm
   */
  public function method_dialogRestoreBackup( $datasource )
  {
    $this->requirePermission("backup.restore");
    $msg = $this->tr("Do you really want to restore Database '%s'? All existing data will be lost!", $datasource);
    
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

    $this->requirePermission("backup.restore");
    $backupPath = BIBLIOGRAPH_BACKUP_PATH;
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

    $this->requirePermission("backup.restore");

    $backupPath = BIBLIOGRAPH_BACKUP_PATH;
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
    $this->requirePermission("backup.delete");
    $days = $this->getApplication()->getPreference("backup.daysToKeepBackupFor");
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

    $this->requirePermission("backup.delete");

    $backupPath = BIBLIOGRAPH_BACKUP_PATH;
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

  function method_testProgress()
  {
    
    $progress = new qcl_ui_dialog_ServerProgress("testProgress");
    for ( $i=0; $i<101; $i++)
    {
      $progress->setProgress($i,"Message$i");
      usleep(pow(2,rand(12,18)));
    }
    $progress->complete();
  }
}