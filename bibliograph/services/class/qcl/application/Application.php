<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_access_model_User" ); // this imports all the other required models
qcl_import( "qcl_application_ApplicationCache" );

/**
 * Base class for applications. This class mainly provides access to the
 * different application models and to the access controller
 *
 */
abstract class qcl_application_Application
  extends qcl_core_Object
{
  //-------------------------------------------------------------
  // class properties
  //-------------------------------------------------------------

  /**
   * The id of the application, usually the namespace
   * @var string
   */
  protected $applicationId;

  /**
   * The descriptive name of the application
   * @var string
   */
  protected $applicationName;

  /**
   * The version of the application
   * @var string
   */
  protected $applicationVersion;

  /**
   * Whether anoynmous access is allowed
   * @var boolean
   */
  protected $allowAnonymousAccess = true;

  /**
   * Whether authentication should be skipped altogether.
   * This is currently unsupported.
   * @var boolean
   */
  protected $skipAuthentication = false;

  /**
   * The path to the ini file containing initial configuration
   * such as database connectivity etc.
   * @var string
   */
  protected $iniPath = null;

  /**
   * The manager for the initial application configuration
   * @var qcl_config_IniConfigManager
   */
  private $iniManager;

  /**
   * The path to the plugin directory. Must be explicitly set
   * @var string
   */
  protected $pluginPath;

  /**
   * The current application instance
   * @var qcl_application_Application
   */
  static private $application;

  /**
   * The default datasource schema of the application
   * @var string
   */
  protected $defaultSchema;

  //-------------------------------------------------------------
  // authentication
  //-------------------------------------------------------------

  /**
   * Whether anonymous access is allowed or not
   * @return bool
   */
  public function isAnonymousAccessAllowed()
  {
    return $this->allowAnonymousAccess;
  }

  /**
   * Can be used to allow unauthenticated access to selected service
   * methods or, if you set the skipAuthentication property to true, to
   * suppress authentication altogether.
   * @return bool
   */
  public function skipAuthentication()
  {
    return $this->skipAuthentication;
  }

  //-------------------------------------------------------------
  // property getters
  //-------------------------------------------------------------

  /**
   * Getter for application id
   * @return string
   */
  public function id()
  {
    return $this->applicationId;
  }

  /**
   * Getter for application name
   * @return string
   */
  public function name()
  {
    return $this->applicationName;
  }

  /**
   * Getter for application version
   * @return string
   */
  public function version()
  {
    return $this->applicationVersion;
  }

  /**
   * Returns the path to the ini file containing initial configuration
   * such as database connectivity etc. if set by as a class property
   * @return string
   */
  public function iniPath()
  {
    return $this->iniPath;
  }

  /**
   * Returns the path to the folder containing the plugins
   * @throws qcl_application_plugin_Exception
   * @return string
   */
  public function pluginPath()
  {
    if ( ! $this->pluginPath )
    {
      throw new qcl_application_plugin_Exception("No plugin path defined");
    }
    return $this->pluginPath;
  }

  /**
   * Returns the default datasource schema of the application
   * @return string
   */
  public function defaultSchema()
  {
    return $this->defaultSchema;
  }

  //-------------------------------------------------------------
  // object getters
  //-------------------------------------------------------------

  /**
   * Static getter for current application instance.
   * @return qcl_application_Application|false
   */
  static public function getInstance()
  {
    return self::$application;
  }

  /**
   * Static setter for current application instance. Returns false
   * if no application exists
   * @param qcl_application_Application|false
   */
  static public function setInstance( $application )
  {
    self::$application = $application;
  }

  /**
   * Return the current server instance.
   * @return qcl_server_JsonRpc
   */
  public function getServerInstance()
  {
    return qcl_server_Server::getInstance()->getServerInstance();
  }

  /**
   * gets the locale controller and sets the default locale. default is
   * a qcl_locale_Manager (see there). if you want to use a different
   * controller, override this method
   * @return qcl_locale_Manager
   */
  public function getLocaleManager()
  {
    qcl_import( "qcl_locale_Manager" );
    return qcl_locale_Manager::getInstance();
  }

  /**
   * Sborthand getter for access behavior attached
   * @return qcl_access_SessionController
   */
  public function getAccessController()
  {
    qcl_import( "qcl_access_SessionController" );
    return qcl_access_SessionController::getInstance();
  }

  //-------------------------------------------------------------
  // event dispatcher and message bus
  //-------------------------------------------------------------

  /**
   * Getter for event dispatcher
   * @return qcl_event_Dispatcher
   */
  public function getEventDispatcher()
  {
    qcl_import( "qcl_event_Dispatcher" );
    return qcl_event_Dispatcher::getInstance();
  }

  /**
   * Getter for message bus object
   * @return qcl_event_message_Bus
   */
  public function getMessageBus()
  {
    qcl_import( "qcl_event_message_Bus" );
    return qcl_event_message_Bus::getInstance();
  }

  //-------------------------------------------------------------
  // ini values
  //-------------------------------------------------------------

  /**
   * Returns initial configuration data manager
   * @return qcl_config_IniConfigManager
   */
  public function getIniManager()
  {
    if ( ! $this->iniManager )
    {
      qcl_import( "qcl_config_IniConfigManager" );
      $this->iniManager = new  qcl_config_IniConfigManager( $this );
    }
    return $this->iniManager;
  }

  /**
   * Returns a configuration value of the pattern "foo.bar.baz"
   * This retrieves the values set in the service.ini.php file.
   */
  public function getIniValue( $path )
  {
    $value =  $this->getIniManager()->getIniValue( $path );
    if( $value == "on" or $value == "yes" )
    {
      $value = true;
    }
    elseif ( $value == "off" or $value == "no" )
    {
      $value = false;
    }
    return $value;
  }

  /**
   * Returns an array of values corresponding to the given array of keys from the
   * initialization configuration data.
   * @param array $arr
   * @return array
   */
  public function getIniValues( $arr )
  {
    return $this->getIniManager()->getIniValues( $arr );
  }

  //-------------------------------------------------------------
  // initial data
  //-------------------------------------------------------------

  /**
   * Imports initial data
   * @param array $data
   *    Map of model types and paths to the xml data files
   * @param qcl_access_DatasourceModel $accessDatasource
   *    Optional. If not given, qcl_access_DatasourceModel is used.
   *    You can provide a subclass of qcl_access_DatasourceModel which
   *    selectively override the used model types in the init() method by
   *    using the registerModels() method and a map of the models to
   *    override.
   * @throws InvalidArgumentException
   * @see qcl_access_DatasourceModel::init()
   */
  protected function importInitialData( $data, $accessDatasource=null )
  {
    qcl_import( "qcl_data_model_import_Xml" );
    qcl_import( "qcl_io_filesystem_local_File" );
    qcl_import( "qcl_data_datasource_Manager" );

    if ( $accessDatasource === null )
    {
      qcl_import( "qcl_access_DatasourceModel" );
      $accessDatasource = qcl_access_DatasourceModel::getInstance();
    }
    else
    {
      if ( ! $accessDatasource instanceof qcl_access_DatasourceModel )
      {
        throw new InvalidArgumentException( "The accessDatasource parameter must be an instance of a class inheriting from qcl_access_DatasourceModel");
      }
    }

    /*
     * Register the access models as a datasource to make
     * them accessible to client queries
     */
    try
    {
      $this->log( "Registering access datasource schema" , QCL_LOG_APPLICATION );
      $accessDatasource->registerSchema();
    }
    catch( qcl_data_model_RecordExistsException $e ){}

    /*
     * create datasources
     */
    $dsManager = qcl_data_datasource_Manager::getInstance();
    try
    {
      $this->log( "Creating datasource named 'access'." , QCL_LOG_APPLICATION );
      $dsManager->createDatasource(
        "access","qcl.schema.access", array(
          'hidden' => true
        )
      );
    }
    catch( qcl_data_model_RecordExistsException $e ){}

    /*
     * Import data
     */
    foreach( $data as $type => $path )
    {
      $this->log( "Importing '$type' data...'." , QCL_LOG_APPLICATION );

      /*
       * get model from datasource
       */
      $dsModel = $dsManager->getDatasourceModelByName( "access" );
      $model   = $dsModel->getInstanceOfType( $type );

      /*
       * delete all data
       * @todo check overwrite
       */
      $model->deleteAll();

      /*
       * import new data
       */
      $xmlFile = new qcl_io_filesystem_local_File( "file://" . $path );
      $this->log( "     ... from $path" , QCL_LOG_APPLICATION );
      $model->import( new qcl_data_model_import_Xml( $xmlFile ) );
    }
  }

  //-------------------------------------------------------------
  // configuration
  //-------------------------------------------------------------

  /**
   * Returns the config model singleton instance used by the application
   * @return qcl_config_ConfigModel
   */
  public function getConfigModel()
  {
    return $this->getAccessController()->getConfigModel();
  }

  /**
   * Sets up configuration keys if they do not already exist
   * @param array Map of maps with the name of the config key as
   * key and a map of "type","custom", "default", and "final" keys with values
   * as value.
   * @return void
   */
  public function setupConfigKeys( $map )
  {
    qcl_assert_array( $map, "Invalid map argument");
    $configModel = $this->getConfigModel();
    foreach( $map as $key => $data )
    {
      qcl_assert_valid_string( $key, "Invalid key $key");
      qcl_assert_array_keys( $data, array("type","custom","default","final") );
      $configModel->createKeyIfNotExists(
        $key, $data['type'], $data['custom'], $data['default'], $data['final']
      );
    }
  }

  //-------------------------------------------------------------
  // services that belong to this application
  //-------------------------------------------------------------

  /**
   * Registers service names that will be used by this application and
   * maps them to the classes that implement them. Takes an asoociative
   * array. The keys are the service names, the values the implementing
   * classes.
   *
   * @param array $serviceClassMap
   * @return unknown_type
   */
  public function registerServices( $serviceClassMap )
  {
    $server = qcl_server_Server::getInstance()->getServerInstance();
    foreach( $serviceClassMap as $service => $class )
    {
      $server->mapServiceToClass( $service, $class );
    }
  }

  //-------------------------------------------------------------
  // database connectivity
  //-------------------------------------------------------------

  /**
   * Returns the DSN for the user database
   * @throws LogicException
   * @return string
   */
  public function getUserDsn()
  {
    $dsn = $this->getIniValue("macros.dsn_user");
    if ( ! $dsn )
    {
      throw new LogicException("No user DSN supplied in INI file!");
    }
    return str_replace("&",";", $dsn );
  }

  /**
   * Returns the DSN for the admin database
   * @throws LogicException
   * @return string
   */
  public function getAdminDsn()
  {
    $dsn = $this->getIniValue("macros.dsn_admin");
    if ( ! $dsn )
    {
      throw new LogicException("No admin DSN supplied in INI file!");
    }
    return str_replace("&",";", $dsn );
  }

  //-------------------------------------------------------------
  // required main() method
  //-------------------------------------------------------------

  public function main()
  {
    /*
     * set default loggers
     */
    if (defined("APPLICATION_LOG_DEFAULT") )
    {
      $logger = qcl_log_Logger::getInstance();
      foreach( unserialize ( APPLICATION_LOG_DEFAULT ) as $const => $value )
      {
        $logger->setFilterEnabled( constant($const), $value );
      }
    }

  }

  //-------------------------------------------------------------
  // application datasources
  //-------------------------------------------------------------

  /**
   * Creates and returns a dasource with the given name, of the default type that the
   * application supports
   * @param $namedId
   * @param array $data
   * @return \qcl_data_datasource_DbModel @return qcl_data_datasource_DbModel
   */
  public function createDatasource( $namedId, $data= array() )
  {
    qcl_import( "qcl_data_datasource_Manager" );
    $mgr = qcl_data_datasource_Manager::getInstance();
    if ( ! isset( $data['dsn'] ) )
    {
      $data['dsn'] = $this->getUserDsn();
    }
    return $mgr->createDatasource( $namedId, $this->defaultSchema(), $data );
  }

  //-------------------------------------------------------------
  // etc
  //-------------------------------------------------------------

  /**
   * Returns the url of the client application's build directory
   * @return string
   */
  public function getClientUrl()
  {
    return "http://" . $_SERVER["HTTP_HOST"] .
      dirname( dirname( $_SERVER["SCRIPT_NAME"] ) ) .
      "/build";
  }

  /**
   * Alias of qcl_server_Server::getUrl()
   * @return string
   */
  public function getServerUrl()
  {
    return qcl_server_Server::getUrl();
  }
}