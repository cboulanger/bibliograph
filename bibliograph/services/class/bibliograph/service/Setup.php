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
qcl_import( "bibliograph_ApplicationCache" );
qcl_import("qcl_util_system_Lock");

/**
 * Setup class. This class is called on application startup, i.e.
 * when the "main" method of the application is executed.
 *
 */
class bibliograph_service_Setup
  extends qcl_data_controller_Controller
{

  /**
   * Configuration keys to be created if they do not already
   * exists.
   * @var array
   */
  private $configKeys = array(
    "application.title" => array(
      "type"      => "string",
      "custom"    => false,
      "default"   => "Bibliograph Online Bibliographic Data Manager",
      "final"     => false
    ),
    "application.logo" => array(
      "type"      => "string",
      "custom"    => false,
      "default"   => "bibliograph/icon/bibliograph-logo.png",
      "final"     => false
    ),
    "bibliograph.access.mode" => array(
      "type"      => "string",
      "custom"    => false,
      "default"   => "normal",
      "final"     => false
    ),
    "bibliograph.access.no-access-message" => array(
      "type"      => "string",
      "custom"    => false,
      "default"   => "",
      "final"     => false
    ),
    // TODO: remove this
    "plugin.csl.bibliography.maxfolderrecords" => array(
      "type"      => "number",
      "custom"    => false,
      "default"   => 500,
      "final"     => false
    )
  );


  public function method_setup()
  {

    /*
     * check whether the application has been set up
     */
    $cache = bibliograph_ApplicationCache::getInstance();

    /*
     * if we're already set up, cleanup sessions and users
     */
    if ( $cache->get("setup") )
    {
      $this->getAccessController()->cleanup();
      return;
    }

    /*
     * do the setup
     */
    try
    {
      $this->setup();
      $cache->set("setup", true);
      $cache->savePersistenceData();
      throw new JsonRpcException( "Setup has finished. Please reload the application" );
    }
    catch( Exception $e )
    {
      throw $e;
    }

  }

  /**
   * Setup the application
   */
  public function setup()
  {

    /*
     * get the app object and the persistent cache object
     */
    $app = $this->getApplication();
    $cache = bibliograph_ApplicationCache::getInstance();

    /*
     * create config keys
     */
    $this->log("Setting up configuration keys ....", QCL_LOG_SETUP );
    $app->setupConfigKeys( $this->configKeys );

    /**
     * register bibliograph datasource schema and create example datasource
     */
    if ( ! $cache->get("registeredBibliographSchema") )
    {
      $this->log("Registering bibliograph datasource schema ....", QCL_LOG_SETUP );
      qcl_import( "bibliograph_model_BibliographicDatasourceModel");
      $model = bibliograph_model_BibliographicDatasourceModel::getInstance();
      try
      {
        $model->registerSchema();
      }
      catch( qcl_data_model_RecordExistsException $e )
      {
        $this->log("Bibliograph datasource schema already exists", QCL_LOG_SETUP );
      }
      $cache->set( "registeredBibliographSchema", true );
    }

    /*
     * create example datasources and link them to roles
     */
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
    catch(Exception $e)
    {
      $this->log("Problem linking datasources to roles: $e.", QCL_LOG_SETUP );
    }

    /*
     * create config value for https authentication
     * TODO: move into config key definition
     */
    if (  ! $cache->get("createdHttpsEnforceConfig") )
    {
      $this->log("Adding config value for https authentication ....", QCL_LOG_SETUP );
      $enforce_https = $app->getIniValue("access.enforce_https_login" );
      $app->getConfigModel()->createKeyIfNotExists("access.enforce_https_login","boolean");
      $app->getConfigModel()->setKeyDefault("access.enforce_https_login", $enforce_https );
      $cache->set( "createdHttpsEnforceConfig", true );
    }

    /*
     * remote and local file storage datasources
     */
    if (  ! $cache->get("registeredFileStorageDatasources") )
    {
      $this->log("Registering remote and local file storage datasources ....", QCL_LOG_SETUP );
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

      $cache->set( "registeredFileStorageDatasources", true );
    }

    /*
     * create datasource for importing records from
     * text files
     */
    if ( ! $cache->get("createdImportDatasource") )
    {
      $this->log("Creating datasource for importing data ....", QCL_LOG_SETUP );
      $manager = qcl_data_datasource_Manager::getInstance();
      try
      {
        $manager->createDatasource(
          "bibliograph_import",
          "bibliograph.schema.bibliograph2",
          array(
            'dsn'    => str_replace("&",";",$app->getIniValue("macros.dsn_tmp")), // ini-file data cannot contain ";"
            'hidden' => true
          )
        );
      }
      catch( qcl_data_model_RecordExistsException $e )
      {
        $this->log("Import datasource already exists.", QCL_LOG_SETUP );
      }
      $cache->set( "createdImportDatasource", true );
    }

    /*
     * create datasource for exporting records into
     * text files, located in the temporary folder
     */
    if ( ! $cache->get("createdExportDatasource") )
    {
      $this->log("Creating datasource for exporting data ....", QCL_LOG_SETUP );

      $manager = qcl_data_datasource_Manager::getInstance();
      try
      {
        $dsModel = $manager->createDatasource(
          "bibliograph_export",
          "qcl.schema.filesystem.local",
           array(
            'dsn'    => str_replace("&",";",$app->getIniValue("macros.dsn_tmp")), // ini-file data cannot contain ";"
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
      $cache->set( "createdExportDatasource", true );
    }

    /*
     * create datasource for exporting records into
     * text files, located in the temporary folder
     */
    if ( ! $cache->get("addedBibtexFormat") )
    {
      qcl_import("bibliograph_model_import_RegistryModel");
      $importRegistry = bibliograph_model_import_RegistryModel::getInstance();
      $importRegistry->addFromClass("bibliograph_model_import_Bibtex");

      qcl_import("bibliograph_model_export_RegistryModel");
      $exportRegistry = bibliograph_model_export_RegistryModel::getInstance();
      $exportRegistry->addFromClass("bibliograph_model_export_Bibtex");

      $cache->set( "addedBibtexFormat", true );
    }

    /*
     * make sure an administrator email is specified and set admin
     * email in the user model.
     */
    $adminEmail = $app->getIniValue("email.admin");
    if ( ! $adminEmail )
    {
      throw new JsonRpcException( "You need to set an admin email in the application.ini.php file (email.admin)" );
    }
    else
    {
      $userModel = $app->getAccessController()->getUserModel();
      $userModel->load( "admin" ); // will throw an error if user setup hasn't worked
      $userModel->set( "email", $adminEmail );
      $userModel->save();
    }
  }
}
?>