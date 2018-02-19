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





class bibliograph_service_Backup
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

  /*
  ---------------------------------------------------------------------------
     INTERNAL METHODS
  ---------------------------------------------------------------------------
  */

  /**
   * Checks if user has access to the given datasource. If not,
   * throws \lib\exceptions\UserErrorException.
   * @param string $datasource
   * @return void
   * @throws \lib\exceptions\UserErrorException
   */
  public function checkDatasourceAccess( $datasource )
  {
    
    bibliograph_service_Access::getInstance()->checkDatasourceAccess( $datasource );
  }

  /**
   * Check if we can make or restore backups
   * @throws \lib\exceptions\UserErrorException
   * @return void
   */
  public function checkBackupPrerequisites()
  {

    /*
     * permission
     */
    $this->requirePermission("backup.create");

    /*
     * zipped file
     */
    if( ! class_exists("ZipArchive") )
    {
      Yii::warning("You must install the ZIP extension in order to create backups");
      throw new \lib\exceptions\UserErrorException("Cannot create backup archive.");
    }

    /*
     * backup path
     */
    if ( ! defined("BIBLIOGRAPH_BACKUP_PATH") )
    {
      Yii::warning("You must define the BIBLIOGRAPH_BACKUP_PATH constant." );
      throw new \lib\exceptions\UserErrorException("Cannot create backup archive.");
    }
    if ( ! file_exists( BIBLIOGRAPH_BACKUP_PATH ) or ! is_writable( BIBLIOGRAPH_BACKUP_PATH ) )
    {
      Yii::warning("Directory '" . BIBLIOGRAPH_BACKUP_PATH . "' needs to exist and be writable" );
      throw new \lib\exceptions\UserErrorException("Cannot create backup archive.");
    }
  }


  /*
  ---------------------------------------------------------------------------
     API
  ---------------------------------------------------------------------------
  */

  /**
   * Method to create a backup of a datasource
   * @param $datasource
   * @throws \lib\exceptions\UserErrorException
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
    $backupPath = BIBLIOGRAPH_BACKUP_PATH;
    $tmpPath    = QCL_TMP_PATH;

    $zipfile = realpath( $backupPath ) . "/" . $datasource . "_" . date("Y-m-d_H-i-s") . ".zip";
    $zip = new ZipArchive();
    if ($zip->open($zipfile, ZIPARCHIVE::CREATE)!==TRUE)
    {
      Yii::warning("Cannot create file '$zipfile' in '$backupPath'");
      throw new \lib\exceptions\UserErrorException("Cannot create backup archive - please check file permissions.");
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
      Yii::warning( $e->getMessage() );
      throw new \lib\exceptions\UserErrorException( "You don't seem to have the necessary MySql Privileges to make a backup. You need at least the global 'SELECT' and 'FILE' privilege" );
    }

    $zip->close();

    /*
     * delete files
     */
    foreach( $files as $file )
    {
      if ( ! @unlink( $file ) )
      {
        Yii::warning("Cannot create delete '$zipfile'");
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
    return \lib\dialog\Confirm::create(
      Yii::t('app',"Do you want to backup Database '%s'", $datasource),
      null,
      Yii::$app->controller->id, "createBackup", array( $datasource )
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

    $this->checkBackupPrerequisites();
    qcl_assert_valid_string( $datasource );

    
    $timer = new qcl_event_Timer();
    $timer->start();

    $this->createBackup( $datasource );

    $timer->stop();


    return \lib\dialog\Alert::create(
      Yii::t('app', "Backup created in %s seconds" , $timer->getElapsedTime() )
    );
  }

  /**
   * @param $datasource
   * @return qcl_ui_dialog_Confirm
   */
  public function method_dialogRestoreBackup( $datasource )
  {
    $this->checkBackupPrerequisites();
    $msg = Yii::t('app',"Do you really want to restore Database '%s'? All existing data will be lost!", $datasource);
    
    return \lib\dialog\Confirm::create(
      $msg,
      null,
      Yii::$app->controller->id, "dialogChooseBackup", array( $datasource )
    );
  }

  /**
   * Service to present the user with a choice of backups
   * @param $form
   * @param $datasource
   * @return qcl_ui_dialog_Form|string
   * @throws \lib\exceptions\UserErrorException
   */
  public function method_dialogChooseBackup( $form, $datasource )
  {
    if ( $form === false )
    {
      return "ABORTED";
    }

    $this->checkBackupPrerequisites();
    $backupPath = BIBLIOGRAPH_BACKUP_PATH;
    $options = array();
    $files = scandir($backupPath);
    rsort( $files );
    foreach( $files as $file )
    {
      if ( $file[0] == "." ) continue;
      if ( substr( $file, -4) != ".zip" ) continue;
      $name = explode( "_", substr( basename($file),0, -4) );
      if ( $name[0] != $datasource ) continue;
      $options[] = array(
        'label'   => $name[1] . ", " . $name[2],
        'value'   => $file
      );
    }

    if( ! count( $options) )
    {
      throw new \lib\exceptions\UserErrorException("No backup sets available.");
    }

    $formData = array(
      'file'  => array(
        'label'   => Yii::t('app', "Backup from "),
        'type'    => "selectbox",
        'options' => $options,
        'width'   => 200,
      )
    );
    
    return \lib\dialog\Form::create(
      Yii::t('app',"Please select the backup set to restore into database '%s'",$datasource),
      $formData,
      true,
      Yii::$app->controller->id, "restoreBackup", array( $datasource )
    );
  }

  /**
   * @param $data
   * @param $datasource
   * @return string
   * @throws \lib\exceptions\UserErrorException
   */
  public function method_restoreBackup( $data, $datasource )
  {
    if ( $data === null )
    {
      return "ABORTED";
    }

    $this->checkBackupPrerequisites();

    $backupPath = BIBLIOGRAPH_BACKUP_PATH;
    $tmpPath    = QCL_TMP_PATH;

    $zipfile = $backupPath . "/" . $data->file;
    if( ! file_exists( $zipfile ) )
    {
      Yii::warning("File '$zipfile' does not exist.");
      throw new \lib\exceptions\UserErrorException(Yii::t('app', "Backup file does not exist."));
    }

    $zip = new ZipArchive();
    if ($zip->open($zipfile, ZIPARCHIVE::CHECKCONS )!==TRUE)
    {
      Yii::warning("Cannot open file '$zipfile'");
      throw new \lib\exceptions\UserErrorException("Cannot open backup archive");
    }

    $lockfile = $backupPath . "/$datasource.lock";
    if( file_exists($lockfile) )
    {
      throw new \lib\exceptions\UserErrorException("Cannot restore backup. Backup is locked.");
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
        Yii::warning("Cannot delete temporary backup file '$file'");
      }
    }

    $zip->close();

    if ( !@unlink( $lockfile ) )
    {
      $problem = true;
      Yii::warning("Cannot delete lockfile file '$lockfile'");
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


    $msg = Yii::t('app', "Backup has been restored." );
    if ($problem ) $msg .= Yii::t('app', "Please check logfile for problems." );

    return \lib\dialog\Alert::create($msg);

  }

  /**
   * Confirmation dialog for deleting backups
   */
  public function method_dialogDeleteBackups($datasource)
  {
    $this->requirePermission("backup.delete");
    return \lib\dialog\Confirm::create(
      Yii::t('app',"All backups older than one day will be deleted. Proceed?"),
      null,
      Yii::$app->controller->id, "deleteBackups", array( $datasource )
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
      if ( substr( $file, -4) != ".zip" ) continue;
      $name = explode( "_", substr( basename($file),0, -4) );
      if ( $name[0] != $datasource ) continue;
      if ( $name[1] != date("Y-m-d") ){
        if ( !@unlink( "$backupPath/$file" ) )
        {
          $problem = true;
          Yii::warning("Cannot delete backup file '$file'");
        }
        else
        {
          $filesDeleted++;
        }
      }
    }
    $msg = Yii::t('app',"%s backups were deleted.", $filesDeleted);
    if( $problem ) $msg .= Yii::t('app',"There was a problem. Please examine the log file.");
    return \lib\dialog\Alert::create($msg);
  }

  function method_testProgress()
  {
    
    $progress = \lib\dialog\ServerProgress::create("testProgress");
    for ( $i=0; $i<101; $i++)
    {
      $progress->setProgress($i,"Message$i");
      usleep(pow(2,rand(12,18)));
    }
    $progress->complete();
  }
}