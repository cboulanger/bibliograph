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
  
  /**
   * The version of this plugin
   * Follows SemVer Versioning http://semver.org/
   */
  protected $version = "1.0.0";
  
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
    $progressBar->complete( $this->tr("Backup has been created.") );
  }
  
  /**
   * Returns all model objects that belong to a datasource, including the 
   * models joining the main models
   * @param string $datasource The name of the datasource
   * @return array An associative array, key are the names of the models and 
   *    relations, values are the corresponding model objects
   */
  protected function getAllModelsForDatasource( $datasource )
  {
    $dsModel = $this->getDatasourceModel( $datasource );
    $models = array();
    foreach( $dsModel->modelTypes() as $type )
    {
      $model = $dsModel->getInstanceOfType( $type );
      $models[$type] = $model;
      foreach( $model->getRelationBehavior()->relations() as $relation )
      {
        $models[$relation] = $model->getRelationBehavior()->getJoinModel( $relation );
      }
    }
    return $models;
  }
  
  /**
   * Method to create a backup of a datasource
   * @param string $datasource
   * @param qcl_ui_dialog_ServerProgress $progressBar
   * @throws Exception
   * @return string The name of the ZIP-Archive with the backups
   */
  public function createBackup( $datasource, qcl_ui_dialog_ServerProgress $progressBar=null )
  {
    $timestamp   = time();
    $zipFileName = "{$datasource}_{$timestamp}{$this->backup_file_extension}";
    $zipFilePath = BIBLIOGRAPH_BACKUP_PATH . "/$zipFileName";
    
    $zip = new ZipArchive();
    $res = $zip->open( $zipFilePath, ZipArchive::CREATE );
    if( $res !== true )
    {
      throw new Exception( "Could not create zip archive: " . $zip->getStatusString() );
    }
    
    $models = $this->getAllModelsForDatasource( $datasource );
    $tmpFiles = array();    
    $step1 = 100/count( $models );
    $index1 = 0;
    
    foreach( $models as $type => $model )
    {
      $tmpFileName  = QCL_TMP_PATH . "/" . md5( microtime() );
      $tmpFileHandle = fopen( $tmpFileName, "w");
      $tmpFiles[] = $tmpFileName;
      
      // header
      $app   = $this->getApplication(); 
      $total = $model->countRecords();
      $header = array( 
        $this->version,
        implode(",",$model->properties()),
        $total
      );
      fputcsv( $tmpFileHandle, $header );
      
      // records
      
      $count = 1;
      $step2 = $step1/$total;
      $index2 = 0;
      
      $model->findAll();
      while( $model->loadNext() )
      {
        fputcsv( $tmpFileHandle, $model->data() );
        if( $progressBar )
        {
          $progress = $step1*$index1 + $step2*$index2;
          $progressBar->setProgress( 
            $progress, 
            sprintf( "Backing up '%s', %d/%d ... ", $type, $count++, $total )
          );          
          $index2++;
        }
      }
      fclose( $tmpFileHandle );
      $zip->addFile( $tmpFileName, "{$datasource}_{$timestamp}_{$type}.csv" );
      $index1++;
    }
    
    $res = $zip->close();
    
    foreach( $tmpFiles as $file )
    {
      @unlink( $file );
    }
    
    if( $res === false )
    {
      throw new Exception( "Failed to create zip archive" );
    }
    
    return $zipFileName;
  }
  

  /**
   * @param $datasource
   * @return qcl_ui_dialog_Confirm
   */
  public function method_dialogRestoreBackup( $datasource, $token )
  {
    $this->requirePermission("backup.restore");
    $msg = $this->tr("Do you really want to restore Database '%s'? All existing data will be lost!", $datasource);
    
    return new qcl_ui_dialog_Confirm(
      $msg,
      null,
      $this->serviceName(), "dialogChooseBackup", 
      array( $datasource, $token )
    );
  }
  
  /**
   * Parses the name of the backup file and returns the elements
   * @return array
   */
  protected function parseBackupFilename( $filename )
  {
    return explode( "_", substr( basename($filename), 0, -strlen( $this->backup_file_extension ) ) );
  }

  /**
   * Service to present the user with a choice of backups
   * @param $form
   * @param $datasource
   * @return qcl_ui_dialog_Form|string
   * @throws JsonRpcException
   */
  public function method_dialogChooseBackup( $form, $datasource, $token )
  {
    if ( $form === false )
    {
      return "ABORTED";
    }

    $this->requirePermission("backup.restore");
    $backupPath = BIBLIOGRAPH_BACKUP_PATH;
    $options = array();
    $ext = $this->backup_file_extension;
    $files = glob("{$backupPath}/{$datasource}_*$ext");
    rsort( $files );
    foreach( $files as $file )
    {
      list($datasource, $timestamp) = $this->parseBackupFilename( $file );
      $datetime = new DateTime();
      $datetime->setTimestamp($timestamp);
      $options[] = array(
        'label'   => $datetime->format('Y-m-d H:i:s'),
        'value'   => basename($file)
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
      $this->serviceName(), "handleDialogChooseBackup", 
      array( $datasource, $token )
    );
  }

  /**
   * @param $data
   * @param $datasource
   * @return string
   */
  public function method_handleDialogChooseBackup( $data, $datasource, $token )
  {
    if ( $data === null )
    {
      return "ABORTED";
    }
    $this->getApplication()->getMessageBus()->dispatchClientMessage(
      null, "backup.restore", 
      array( "datasource" => $datasource, "file" => $data->file, "token" => $token) 
    );
    return "OK";
  }
  
  public function method_restoreBackup( $datasource, $file, $progressWidgetId )
  {
    $this->requirePermission("backup.restore");
    $progressBar = new qcl_ui_dialog_ServerProgress($progressWidgetId );
    try
    {
      $this->restoreBackup( $datasource, $file, $progressBar );  
    }
    catch( Exception $e)
    {
      $progressBar->error( $e->getMessage() );  
    }
    $progressBar->complete( $this->tr( "Backup has been restored." ) );
  }
  
  /**
   * Actual function to restore the backup
   * @param string $datasource
   * @param string $file
   * @param qcl_ui_dialog_ServerProgress $progressBar
   * @throws Exception
   */
  protected function restoreBackup( $datasource, $file, qcl_ui_dialog_ServerProgress $progressBar=null )
  {
    $zipFilePath = BIBLIOGRAPH_BACKUP_PATH . "/$file";
    list($datasource, $timestamp) = $this->parseBackupFilename( $file );
    
    if( ! file_exists( $zipFilePath ) )
    {
      throw new Exception(_("Backup file does not exist."));
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZIPARCHIVE::CHECKCONS )!==TRUE)
    {
      throw new Exception("Cannot open backup archive");
    }

    $zip->extractTo( QCL_TMP_PATH );

    $models = $this->getAllModelsForDatasource( $datasource );
    
    $step1 = 100/count( $models );
    $index1 = 0;
    
    $tmpFiles = array();    
    foreach( $models  as $type => $model )
    {
      $tmpFileName  = QCL_TMP_PATH . "/{$datasource}_{$timestamp}_{$type}.csv";
      if ( ! file_exists( $tmpFileName ) or ! is_readable( $tmpFileName ) )
      {
        throw new Exception("No valid file data for '$type'");
      }
      $tmpFiles[] = $tmpFileName;
      
      $tmpFileHandle = fopen( $tmpFileName, "r");
      
      // header
      $app   = $this->getApplication(); 
      $modelProperties = $model->properties();
      
      list( $version, $properties, $total ) = fgetcsv( $tmpFileHandle );
      if( ! $version or ! $properties )
        throw new Exception("Invalid header");
      list( $maj1, , ) = explode(".", $version );
      list( $maj2, , ) = explode(".", $this->version );
      if ( $maj1 !== $maj2 ) 
        throw new Exception("Backup versions do not match");
      if ( explode(",",$properties) !== $modelProperties  ) 
        throw new Exception("Properties do not match");

      // records
      $count = 1;
      $step2 = $step1/$total;
      $index2 = 0;
      
      $properties = $model->properties();
      $qb  = $model->getQueryBehavior();
      $qb->deleteAll();
      
      while( $values = fgetcsv( $tmpFileHandle ) )
      {
        $data = array_combine( $properties, $values );
        $qb->insertRow( $data ); 

        if( $progressBar )
        {
          $progress = $step1*$index1 + $step2*$index2;
          $progressBar->setProgress( 
            $progress, 
            sprintf( "Restoring '%s', %d/%d ... ", $type, $count++, $total )
          );          
          $index2++;
        }
      }
      fclose( $tmpFileHandle );
      $index1++;
    }
    
    $zip->close();
    
    foreach( $tmpFiles as $file )
    {
      @unlink( $file );
    }

    // reset transaction ids
    foreach( $models as $model )
    {
      $model->resetTransactionId();
    }

    // broacast message
    $this->broadcastClientMessage("backup.restored",array(
      "datasource" => $datasource
    ) );    
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