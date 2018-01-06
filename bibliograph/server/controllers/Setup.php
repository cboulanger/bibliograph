<?php
/* ************************************************************************

   Bibliograph: Collaborative Online Reference Management

   http://www.bibliograph.org

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

namespace app\controllers;

use app\models\User;
use app\controllers\dto\ServiceResult;

/**
 * Setup class. This class is called on application startup, i.e.
 * when the "main" method of the application is executed.
 *
 */
class Setup extends \app\controllers\AppController
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
   * The entry method. If the application is already setup, do nothing. Otherwise,
   * display progress dialog on the client and start setup service
   * @return \app\controllers\dto\ServiceResult
   * @throws Exception
   */
  public function actionSetup()
  {
    $result = new ServiceResult();

    if ( ! User::findOne(['namedId'=>'setup']) ){
      return $this->actionStart( $this->tr("Starting setup ...") );
    }

    // client messages
    $result->addEvent("ldap.enabled", (bool) $app->getIniValue("ldap.enabled") );
    $result->addEvent("bibliograph.setup.done");
    
    return "OK";
  }

  /**
   * A list of methods that are executed in order
   * @return array
   */
  protected function getStepMethods()
  {
    return array(
      //"importInitialData",
      "checkConfiguration",
      "createConfig",
      "registerDatasourceSchemas",
      "createExampleDatasources",
      "createInternalDatasources",
      "autoInstallPlugins"
      
    );
  }
  
  /**
   * not yet used
   */
  protected function useEmbeddedDatabase($value)
  {
    if ( QCL_USE_EMBEDDED_DB )
    {
      qcl_data_model_db_ActiveRecord::resetBehaviors();
      $this->getApplication()->useEmbeddedDatabase($value);
    }
  }

  protected function importInitialData()
  {
    // hack, this is neccessary because of the "setup" user stuff that breaks
    // the sequence on database/model initiatization steps
    // see qcl_application_Application::importInitialData 
    qcl_import("qcl_data_datasource_DbModel");
    qcl_data_datasource_DbModel::getInstance()->createIfNotExists( "access", array(
      "schema" => "qcl.schema.access",
      "type"   => "mysql",
      "hidden" => true
    ) );
    qcl_import("qcl_access_DatasourceModel");
    $ds_model  = qcl_access_DatasourceModel::getInstance();
    $ds_model->registerSchema();
    
    /*
     * check if "setup" user exists, if not, import user data
     */
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

      // hash passwords
      $userModel->findAll();
      while($userModel->loadNext()){
        $password = $userModel->getPassword();
        $hashed = $this->getApplication()->getAccessController()->generateHash( $password );
        $userModel->setPassword($hashed)->save(); 
      }
    }
    
    // result
    $this->addLogText($this->tr("Initial user data imported."));
    
    // next
    $this->setMessage($this->tr("Checking configuration ..."));  
    
    //$this->useEmbeddedDatabase(true);
  }
  
  
  /**
   * Check configuration
   */  
  protected function checkConfiguration()
  {
    $this->log("Checking configuration ...", QCL_LOG_SETUP );
    
    $app = $this->getApplication();
    $acl = $app->getAccessController();
    
    //$this->useEmbeddedDatabase(false);
    
    $adminEmail = $app->getIniValue("email.admin");
    if ( ! $adminEmail )
    {
      $this->addLogText(">>> " .$this->tr("Please enter the administrator email address in the application.ini.php file (email.admin)." ));
    }
    else
    {
      $userModel = $acl->getUserModel();
      // encrypt default users' passwords
      foreach( array("admin","manager","user") as $username )
      {
        try
        {
          $userModel->load( $username );
          // set administrator's email
          if( ! $userModel->getEmail() ) 
          {
            $userModel->setEmail( $adminEmail );
          }
          // encrypt default password
          if( $userModel->getPassword() == $username )
          {
            $userModel->setPassword($acl->generateHash( $username ));
          }
          $userModel->save();
        }
        catch( qcl_data_model_RecordNotFoundException $e)
        {
          $this->log("User $username does not exist.", QCL_LOG_SETUP );
        }
      }
      $this->addLogText($this->tr("Set up default users."));
    }
    
    // next
    $this->setMessage($this->tr("Setting up configuration keys ..."));    
    
    //$this->useEmbeddedDatabase(true);
  }

  protected function createConfig()
  {
    $this->log("Adding configuration keys ...", QCL_LOG_SETUP );
    
    $app = $this->getApplication();
    //$this->useEmbeddedDatabase(false);
    
    $app->setupConfigKeys( include( APPLICATION_CLASS_PATH . "/bibliograph/config.php" ) );

    // access.enforce_https_login
    $enforce_https = $app->getIniValue("access.enforce_https_login" );
    $app->getConfigModel()->createKeyIfNotExists("access.enforce_https_login","boolean");
    $app->getConfigModel()->setKeyDefault("access.enforce_https_login", $enforce_https );
    
    // other preferences
    $app->addPreference( "application.locale", "", true );
    $app->addPreference( "authentication.method", "hashed" );

    // result
    $this->addLogText($this->tr("Configuration keys added."));
    // next
    $this->setMessage($this->tr("Registering datasource information ..."));
    
    //$this->useEmbeddedDatabase(true);
  }

  protected function registerDatasourceSchemas()
  {
    $this->log("Registering bibliograph datasource schema ....", QCL_LOG_SETUP );
    
    $app = $this->getApplication();
    //$this->useEmbeddedDatabase(false);
    
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
    
    //$this->useEmbeddedDatabase(true);
  }

  protected function createExampleDatasources()
  {
    $app = $this->getApplication();
    //$this->useEmbeddedDatabase(false);
    
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
    $this->log("Creating example datasources ...", QCL_LOG_SETUP );
    try
    {
      $dsModel1 = $app->createDatasource( "database1", array( 'title' => "Database 1" ) );
      $dsModel2 = $app->createDatasource( "database2", array( 'title' => "Database 2" ) );
      $ac = $this->getAccessController();
      $dsModel1
        ->linkModel($ac->getRoleModel()->load("anonymous"))
        ->linkModel($ac->getRoleModel()->load("user"))
        ->linkModel($ac->getRoleModel()->load("admin"));
      $dsModel1->getInstanceOfType("folder")->load(1)->setPublic(true)->save();
      
      $dsModel2
        ->linkModel($ac->getRoleModel()->load("user"))
        ->linkModel($ac->getRoleModel()->load("admin"));
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
        ->linkModel($ac->getRoleModel()->load("anonymous"))
        ->linkModel($ac->getRoleModel()->load("user"))
        ->linkModel($ac->getRoleModel()->load("admin"));
      $ac->getDatasourceModel("database2")
        ->linkModel($ac->getRoleModel()->load("user"))
        ->linkModel($ac->getRoleModel()->load("admin"));
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
    
    //$this->useEmbeddedDatabase(true);
  }


  protected function createInternalDatasources()
  {
    $app = $this->getApplication();
    //$this->useEmbeddedDatabase(false);    
    
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

//    try
//    {
//      qcl_import("qcl_io_filesystem_remote_Datasource");
//      qcl_io_filesystem_remote_Datasource::getInstance()->registerSchema();
//    }
//    catch( qcl_data_model_RecordExistsException $e){}

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
        "qcl.schema.filesystem.local"
      );
      $dsModel->setHidden(true);
      $dsModel->setType("file");
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
    $importRegistry->addFromClass("bibliograph_model_import_Csv");
    
    qcl_import("bibliograph_model_export_RegistryModel");
    $exportRegistry = bibliograph_model_export_RegistryModel::getInstance();
    $exportRegistry->addFromClass("bibliograph_model_export_Bibtex");
    $exportRegistry->addFromClass("bibliograph_model_export_Csv");
    
    // next
    $this->setMessage($this->tr("Installing plugins ..."));

    // result
    $this->addLogText($this->tr("Created internal datasources."));
    
  }
  

  /**
   * Auto-install plugins if text file named "plugins.txt" exists at the top
   * with either the word "all" or a space separated list of plugin ids to install
   */
  protected function autoInstallPlugins()
  {
    $manager = qcl_application_plugin_Manager::getInstance(); 
    $plugins_auto_install_file = "../plugins.txt";
    $logText = array();
    if( file_exists($plugins_auto_install_file) )
    {
      $this->getLogger()->log("Plugin autoinstall file found.", QCL_LOG_SETUP );
      $plugins_to_install = explode(" ", file_get_contents($plugins_auto_install_file) ); 
      
      foreach ( $manager->getPluginList() as $namedId )
      {
        $plugin = $manager->getSetupInstance( $namedId );
        if( ! in_array( $namedId, $plugins_to_install) and trim($plugins_to_install[0]) != "all" )
        {
          continue;
        }
        
        if( $manager->isInstalled( $namedId) )
        {
          $msg = $this->tr("Plugin '%s' is already installed.", $plugin->getName() ) ;
          $this->getLogger()->log( $msg, QCL_LOG_SETUP );
          $logText[] = $msg;
          continue;
        }
        
        $msg = sprintf( "Installing plugin '%s'", $plugin->getName() );
        $this->getLogger()->log( $msg, QCL_LOG_SETUP );
        try
        {
          $installMsg = $plugin->install();
          $this->getLogger()->log( $installMsg, QCL_LOG_SETUP );
          $manager->register( $namedId, $plugin );
          $msg = $this->tr("Installed plugin '%s'",$plugin->getName() );
          $logText[] = $msg;
        }
        catch( qcl_application_plugin_Exception $e )
        {
          $msg = $this->tr("Installation of plugin '%s' failed: %s", $plugin->getName(), $e->getMessage());
          $logText[] = $msg;
          $this->getLogger()->log( $msg, QCL_LOG_SETUP );
        }
      }
    }

    $logText[] = "\n" . $this->tr("Setup finished. Please reload the application");
        
    // next
    $this->setMessage($this->tr("Done ..."));
    $this->addLogText(implode("\n",$logText));
    
    // done!
    //$this->useEmbeddedDatabase(false); // now the external database can be used. 
    $app = $this->getApplication();
    $app->getCache()->setValue("setup",true);
    $app->getCache()->savePersistenceData(); // todo: shouldn't be neccessary, but is - BUG?    
  }

  protected function finish()
  {
    $this->log("Setup completed.", QCL_LOG_SETUP );
    $this->dispatchClientMessage("application.reload");
    return "OK";
  }
}