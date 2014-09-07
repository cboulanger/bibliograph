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

qcl_import("qcl_application_Application");
qcl_import("qcl_locale_Manager");
qcl_import("bibliograph_Cache");
qcl_import("qcl_util_system_Lock");
qcl_import("qcl_application_plugin_Service");

/**
 * Main application class
 */
class bibliograph_Application
  extends qcl_application_Application
{

  /**
   * The id of the application, usually the namespace
   * @var string
   */
  protected $applicationId = "bibliograph";

  /**
   * The descriptive name of the application
   * @var string
   */
  protected $applicationName = "Bibliograph: Online Bibliographic Data Manager";

  /**
   * The version of the application. The version will be automatically replaced
   * by the script that creates the distributable zip file. Do not change.
   * @var string
   */
  protected $applicationVersion = /*begin-version*/"Development version"/*end-version*/;

  /**
   * The path to the application ini-file
   * @var string
   */
  protected $iniPath = "config/bibliograph.ini.php";

  /**
   * The default datasource schema used by the application
   * @var string
   */
  protected $defaultSchema = "bibliograph.schema.bibliograph2";

  /**
   * Returns the singleton instance of this class. Note that
   * qcl_application_Application::getInstance() stores the
   * singleton instance for global access
   * @return bibliograph_Application
   */
  static public function getInstance()
  {
    return parent::getInstance();
  }
  
  /**
   * Returns the application's cache object
   * @return bibliograph_Cache
   */
  protected function getCache()
  {
    return bibliograph_Cache::getInstance();
  }
  
  /**
   * Getter for access controller
   * @return qcl_access_SessionController
   */
  public function getAccessController()
  {
    return qcl_access_SessionController::getInstance();
  }  

  /**
   * If called with a boolean argument, turn the use of 
   * an embedded database on or off. If called with no
   * arguments, return the current state (true if embedded database
   * is used, false if not). Overridden to persist state. 
   * Defaults to false
   */
  public function useEmbeddedDatabase()
  {
    if (func_num_args()==0)
    {
      return (bool) $this->getCache()->getValue("useEmbeddedDatabase");
    }
    $value = func_get_arg(0);
    qcl_assert_boolean( $value );
    return $this->getCache()->setValue("useEmbeddedDatabase",value);
  }
  
  /**
   * Starts the application, performing on-the-fly database setup if necessary.
   */
  public function main()
  {

    parent::main();

    /*
     * log request
     */
    if( $this->getLogger()->isFilterEnabled( BIBLIOGRAPH_LOG_APPLICATION ) )
    {
      $request = qcl_server_Request::getInstance();
      $this->log( sprintf(
        "Starting Bibliograph service: %s.%s( %s ) ...",
        $request->getService(), $request->getMethod(), json_encode($request->getParams())
      ), BIBLIOGRAPH_LOG_APPLICATION );
    }

    /*
     * Clear internal caches. This is only necessary during development
     * as long as you modify the properties of models.
     */
    //qcl_data_model_db_ActiveRecord::resetBehaviors();


    /*
     * Check if this is the first time the application is called, or if
     * the application backend is currently being configured
     */
    if ( ! bibliograph_Cache::getInstance()->getValue("setup") )
    {
      // no, then deny all requests except the one for the "setup" service
      if ( $this->getServerInstance()
                ->getRequest()
                ->getService() != "bibliograph.setup" )
      {
        throw new qcl_server_ServiceException("Server busy. Setup in progress.",null,true);
      }
    }

    /*
     * initialize locale manager
     */
    qcl_locale_Manager::getInstance();

    /*
     * add include paths for the plugins
     */
    qcl_application_plugin_Service::getInstance()->addPluginIncludePaths();

  }


  /**
   * Overridden to create config key
   * @see qcl_application_Application#createDatasource($namedId, $data)
   */
  function createDatasource( $namedId, $data= array() )
  {
    $datasource = parent::createDatasource( $namedId, $data );

    /*
     * create config keys for the datasource
     * @todo generalize this
     * @todo check that setup calls this
     */
    $configModel = $this->getApplication()->getConfigModel();
    $key = "datasource.$namedId.fields.exclude";
    $configModel->createKeyIfNotExists( $key, QCL_CONFIG_TYPE_LIST, false, array() );

    return $datasource;
  }

  /**
   * Overridden to allow anonymous users to access services.
   * @return bool
   */
  public function isAnonymousAccessAllowed()
  {
    //$request = $this->getServerInstance()->getRequest();
    //$service = $request->getService() . "." . $request->getMethod();
    return true;
  }
}
