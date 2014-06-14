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

qcl_import("qcl_data_controller_ProgressController");
qcl_import("qcl_util_system_Lock");
qcl_import("qcl_ui_dialog_Alert");
qcl_import("qcl_ui_dialog_Popup");
qcl_import("bibliograph_Cache" );
qcl_import("bibliograph_model_BibliographicDatasourceModel");

/**
 * Setup class. This class is called on application startup, i.e.
 * when the "main" method of the application is executed.
 *
 */
class bibliograph_service_Setup
  extends qcl_data_controller_ProgressController
{

  /**
   * The properties of the progress dialog to show on the client
   * @var array
   */
  protected $dialogProperties = array(
    'showLog' => true,
    'hideWhenCompleted' => false,
    'okButtonText' => "OK"
  );

  /**
   * Allow unauthenticated access for all methods.
   * @param null|string $method
   * @return bool
   */
  public function skipAuthentication( $method )
  {
    return true;
  }

  /**
   * Returns the cache object
   * @return bibliograph_Cache
   */
  protected function getCache()
  {
    return bibliograph_Cache::getInstance();
  }

  /**
   * The entry method. If the application is already setup, do nothing. Otherwise,
   * display progress dialog on the client and start setup service
   * @return qcl_ui_dialog_Dialog|string
   * @throws Exception
   */
  public function method_setup()
  {
    /*
     * If the app hasn't been set up, start progressive task
     */
    if ( ! $this->getCache()->getValue("setup") )
    {
      $this->importInitialData();
      $this->getAccessController()->createUserSession();
      return $this->method_start($this->tr("Starting setup ..."));
    }

    /*
     * if we're already set up, cleanup sessions and users and inform client about application mode
     */
    $this->getAccessController()->createUserSession();
    $this->getAccessController()->cleanup();
    $this->dispatchClientMessage("bibliograph.setup.done");
    $this->broadcastClientMessage("application.setMode",QCL_APPLICATION_MODE,false);
    return "OK";
  }

  /**
   * A list of methods that are executed in order
   * @return array
   */
  protected function getStepMethods()
  {
    return array(
      "checkAdminEmail",
      "createConfig",
      "registerDatasourceSchemas",
      "createExampleDatasources",
      "createInternalDatasources",
    );
  }

  protected function importInitialData()
  {
    // Check for setup user
    $userModel = qcl_access_model_User::getInstance();
    try
    {
      $userModel->load("setup");
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      $dataPaths =  array(
        'user'        => "bibliograph/data/User.xml",
        'role'        => "bibliograph/data/Role.xml",
        'permission'  => "bibliograph/data/Permission.xml",
        'config'      => "bibliograph/data/Config.xml"
      );
      $this->log("Importing initial user data ....", QCL_LOG_SETUP );
      $this->getApplication()->importInitialData($dataPaths);
    }
  }
  /**
   * make sure an administrator email is specified and set admin
   * email in the user model.
   */  
  protected function checkAdminEmail()
  {
    $app = $this->getApplication();
    $adminEmail = $app->getIniValue("email.admin");
    if ( ! $adminEmail )
    {
      $this->addLogText($this->tr("You haven't entered the administrator email address in the application.ini.php file (email.admin). The application won't be able to send you messages." ));
    }
    else
    {
      $userModel = $app->getAccessController()->getUserModel();
      $userModel->load( "admin" ); // will throw an error if user setup hasn't worked
      $userModel->set( "email", $adminEmail );
      $userModel->save();
      $this->addLogText($this->tr("Administrator email has been set."));
    }
    // next
    $this->setMessage($this->tr("Setting up configuration keys ..."));    
  }

  protected function createConfig()
  {
    $this->log("Adding configuration keys ...", QCL_LOG_SETUP );
    $app = $this->getApplication();
    $app->setupConfigKeys( include( APPLICATION_CLASS_PATH . "/bibliograph/config.php" ) );

    // access.enforce_https_login
    $enforce_https = $app->getIniValue("access.enforce_https_login" );
    $app->getConfigModel()->createKeyIfNotExists("access.enforce_https_login","boolean");
    $app->getConfigModel()->setKeyDefault("access.enforce_https_login", $enforce_https );

    // result
    $this->addLogText($this->tr("Configuration keys added."));
    // next
    $this->setMessage($this->tr("Registering datasource information ..."));
  }

  protected function registerDatasourceSchemas()
  {
    $this->log("Registering bibliograph datasource schema ....", QCL_LOG_SETUP );
    $model = bibliograph_model_BibliographicDatasourceModel::getInstance();
    try
    {
      $model->registerSchema();
    }
    catch( qcl_data_model_RecordExistsException $e )
    {
      $this->log("Bibliograph datasource schema already exists", QCL_LOG_SETUP );
    }
    // result
    $this->addLogText($this->tr("Added datasource schemas."));
    // next
    $this->setMessage($this->tr("Creating example datasources ..."));
  }

  protected function createExampleDatasources()
  {
    $dsModel = qcl_data_datasource_DbModel::getInstance();
    try
    {
      $dsModel->load("setup");
      // result
      $msg = "Not adding example datasources.";
      $this->log($msg, QCL_LOG_SETUP );
      $this->addLogText($this->tr($msg));
      return;
    }
    catch( qcl_data_model_RecordNotFoundException $e){}

    // create example datasources and link them to roles
    $app = $this->getApplication();
    $this->log("Creating example datasources ...", QCL_LOG_SETUP );
    try
    {
      $dsModel1 = $app->createDatasource( "database1", array( 'title' => "Database 1" ) );
      $dsModel2 = $app->createDatasource( "database2", array( 'title' => "Database 2" ) );
      $ac = $this->getAccessController();
      $dsModel1
        ->linkModel($ac->getRoleModel("anonymous"))
        ->linkModel($ac->getRoleModel("user"))
        ->linkModel($ac->getRoleModel("admin"));
      $dsModel2
        ->linkModel($ac->getRoleModel("user"))
        ->linkModel($ac->getRoleModel("admin"));
    }
    catch(qcl_data_model_RecordExistsException $e)
    {
      $this->log("Example datasources already exist.", QCL_LOG_SETUP );
    }

    $this->log("Linking datasources to roles...", QCL_LOG_SETUP );
    try
    {
      $ac = $this->getAccessController();
      $ac->getDatasourceModel("database1")
        ->linkModel($ac->getRoleModel("anonymous"))
        ->linkModel($ac->getRoleModel("user"))
        ->linkModel($ac->getRoleModel("admin"));
      $ac->getDatasourceModel("database2")
        ->linkModel($ac->getRoleModel("user"))
        ->linkModel($ac->getRoleModel("admin"));
    }
    catch(qcl_data_model_RecordExistsException $e)
    {
      $this->log("Datasources already linked to roles.", QCL_LOG_SETUP );
    }
    catch(Exception $e)
    {
      $this->log("Problem linking datasources to roles: $e.", QCL_LOG_SETUP );
    }

    // create marker record to prevent recreation of removed example datasources
    $dsModel->create("setup", array(
      "schema" => "none",
      "type"   => "dummy",
      "active" => false,
      "hidden" => true
    ));

    // result
    $this->addLogText($this->tr("Created example datasources."));
    // next
    $this->setMessage($this->tr("Creating internal datasources ..."));
  }


  protected function createInternalDatasources()
  {
    /*
     * remote and local file storage datasources
     */
    $this->log("Registering file storage datasources ....", QCL_LOG_SETUP );
    try
    {
      qcl_import("qcl_io_filesystem_local_Datasource");
      qcl_io_filesystem_local_Datasource::getInstance()->registerSchema();
    }
    catch( qcl_data_model_RecordExistsException $e){}

    try
    {
      //qcl_import("qcl_io_filesystem_remote_Datasource");
      //qcl_io_filesystem_remote_Datasource::getInstance()->registerSchema();
    }
    catch( qcl_data_model_RecordExistsException $e){}

    /*
     * create datasource for importing records from
     * text files
     */
    $this->log("Creating datasource for importing data ....", QCL_LOG_SETUP );
    $manager = qcl_data_datasource_Manager::getInstance();
    try
    {
      $dsn = str_replace("&",";", $this->getApplication()->getIniValue("macros.dsn_tmp")); // ini-file data cannot contain ";"
      $manager->createDatasource(
        "bibliograph_import",
        "bibliograph.schema.bibliograph2",
        array(
          'dsn'    => $dsn,
          'hidden' => true
        )
      );
    }
    catch( qcl_data_model_RecordExistsException $e )
    {
      $this->log("Import datasource already exists.", QCL_LOG_SETUP );
    }

    /*
     * create datasource for exporting records into
     * text files, located in the temporary folder
     */
    $this->log("Creating datasource for exporting data ....", QCL_LOG_SETUP );

    $manager = qcl_data_datasource_Manager::getInstance();
    try
    {
      $dsModel = $manager->createDatasource(
        "bibliograph_export",
        "qcl.schema.filesystem.local",
         array(
          'dsn'    => str_replace("&",";",$this->getApplication()->getIniValue("macros.dsn_tmp")), // ini-file data cannot contain ";"
          'hidden' => true
        )
      );
      $dsModel->setResourcepath( QCL_TMP_PATH );
      $dsModel->save();
    }
    catch( qcl_data_model_RecordExistsException $e )
    {
      $this->log("Export datasource already exists.", QCL_LOG_SETUP );
    }

    /*
     * create datasource for exporting records into
     * text files, located in the temporary folder
     */
    $this->log("Creating datasource for importing and exporting BibTeX data ....", QCL_LOG_SETUP );
    qcl_import("bibliograph_model_import_RegistryModel");
    $importRegistry = bibliograph_model_import_RegistryModel::getInstance();
    $importRegistry->addFromClass("bibliograph_model_import_Bibtex");

    qcl_import("bibliograph_model_export_RegistryModel");
    $exportRegistry = bibliograph_model_export_RegistryModel::getInstance();
    $exportRegistry->addFromClass("bibliograph_model_export_Bibtex");

    // result
    $this->addLogText($this->tr("Created internal datasources."));
    // next
    $this->setMessage($this->tr("Done ..."));
    $this->addLogText("\n" . $this->tr("Setup finished. Please reload the application"));
    $this->getCache()->setValue("setup",true);
    $this->getCache()->savePersistenceData();
  }

  protected function finish()
  {
    $this->log("Setup completed.", QCL_LOG_SETUP );
  }
}
?>