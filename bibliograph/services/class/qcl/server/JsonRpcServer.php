<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_server_Request" );
qcl_import( "qcl_server_Response" );
qcl_import( "qcl_application_Application" );


/**
 * A JSONRPC Server with a few extensions
 */
class qcl_server_JsonRpcServer
  extends JsonRpcServer
{

  /**
   * The called controller object
   * @var qcl_data_controller_Controller
   */
  private $controller = null;

  //-------------------------------------------------------------
  // initialization & startup
  //-------------------------------------------------------------

  /**
   * Constructor, replaces parent constructor
   */
  function __construct()
  {
    /*
     * Initialize the server, including error
     * catching etc.
     */
    $this->initializeServer();
  }

  /**
   * Return singleton instance of the server
   * return JsonRpcServer
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * Starts a singleton instance of the server. Must be called statically.
   */
  public static function run()
  {
    $_this = self::getInstance();
    $_this->start();
  }

  //-------------------------------------------------------------
  // object getters
  //-------------------------------------------------------------

  /**
   * Returns the current controller instance, if any.
   * @return qcl_data_controller_Controller
   */
  public function getController()
  {
    if ( ! $this->controller )
    {
      throw new LogicException("No controller set.");
    }
    return $this->controller;
  }

  /**
   * Returns the current request object
   * @return qcl_server_Request
   */
  public function getRequest()
  {
    return qcl_server_Request::getInstance();
  }

  /**
   * Getter for response object
   * @return qcl_server_Response
   * @todo rename to getResponse()
   */
  public function getResponseObject()
  {
    return qcl_server_Response::getInstance();
  }

  /**
   * Returns the current application or false if no application exists.
   * @return qcl_application_Application|false
   */
  public function getApplication()
  {
    return qcl_application_Application::getInstance();
  }

  /**
   * Returns access controller instance from application. If no
   * application exists, return the AccessibilityBehavior object
   * from the server. Both objects implement IAccessibilityBehavior,
   * however, the access controller throws a qcl_access_AccessDeniedException
   * instead of returning false if authentication fails.
   *
   * @return qcl_access_Controller
   */
  public function getAccessController()
  {
    $app = $this->getApplication();
    if ( $app)
    {
      return $app->getAccessController();
    }
    else
    {
      return $this->getAccessibilityBehavior();
    }
  }


  //-------------------------------------------------------------
  //  overridden methods
  //-------------------------------------------------------------

  /**
   * Starts or joins an existing php session. You can override
   * the cookie-based PHP session id by providing a 'sessionId'
   * key in the server_data.
   */
  public function startSession()
  {
    $serverData = $this->getServerData();
    if ( isset( $serverData->sessionId ) and $serverData->sessionId )
    {
      $sessionId = $serverData->sessionId;
      session_id( $sessionId );
      $this->debug( "Starting session '$sessionId' from server_data.");
    }
    else
    {
      $this->debug( "Starting normal PHP session " . session_id() . ".");
    }
    session_start();
    $this->sessionId = session_id();
  }  
  
  /**
   * Return the input as a php object if a valid
   * request is found, otherwise throw a JsonRpcException. Overridden
   * to populate qcl_server_Request singleton from input and to start
   * the application before the service is called.
   * @return StdClass
   * @throws JsonRpcException
   */
  public function getInput()
  {
    /*
     * parent function does all the work
     */
    $input = parent::getInput();

    /*
     * populate the request
     */
    $request = qcl_server_Request::getInstance();
    $request->set( $input );

    /*
     * return the input
     */
    return $input;
  }

  /**
   * Overridden to allow underscores instead of dots.
   * This a bit of a hack to allow mapping of service
   * names to classes.
   * @param string $service
   * @return array
   */
  public function getServiceComponents( $service )
  {
    if ( strstr( $service,"." ) )
    {
      return explode( ".", $service );
    }
    elseif ( strstr( $service,"_" ) )
    {
      return explode( "_", $service );
    }
    else
    {
      return array($service);
    }
  }

  /**
   * Adds the given path to the PHP include_path
   * @param string $path
   * @return void
   */
  protected function addIncludePath( $path )
  {
    qcl_addIncludePath( $path );
  }

  /**
   * Overridden to allow mapping of services to classes and to load and start  main application
   * @param string $service
   * @return string|false The name of the file if it was found, false if not.
   * @todo rewrite
   */
  public function loadServiceClass( $service )
  {

    // is this a plugin service?
    $serviceNamespace = $this->serviceComponents[0];
    $pluginServicePath = QCL_PLUGIN_DIR . "/$serviceNamespace/services/class";
    if( is_dir( $pluginServicePath ) )
    {
      $servicePath = $pluginServicePath;
    }
    // no, an application service
    else
    {
      $servicePath = APPLICATION_CLASS_PATH;
    }

    // import application class file
    $appFile = "$servicePath/$serviceNamespace/Application.php";
    if( ! is_file( $appFile ) )
    {
      qcl_log_Logger::getInstance()->warn( "Could not find application file for service $service." );
      return false;
    }
    // instantiate application object
    $appClassName = $serviceNamespace . "_Application";
    qcl_import($appClassName);

    if( ! class_exists( $appClassName ) )
    {
      throw new LogicException(
        "Application file '$appFile' does not contain definition of class '$appClassName'"
      );
    }
    $app = new $appClassName;
    if ( ! $app instanceof qcl_application_Application )
    {
      throw new LogicException(
        "Application class '$appClassName' must be a subclass of 'qcl_application_Application'"
      );
    }

    // store application instance and call main method
    qcl_application_Application::setInstance( $app );
    $this->debug("Loaded service application class '$appClassName'. Running main()...");
    $app->main();

    // get class name from route data if exists
    $routes = $app->routes($appFile);
    $this->debug("Routes: " . json_encode($routes) );

    if ( isset( $routes[$service] ) )
    {
      $serviceClass     = $routes[$service];
      $serviceClassFile = $servicePath . "/" . str_replace( "_", "/", $serviceClass ) . ".php";
    }
    // else, use service name as given
    else
    {
      $serviceClass     = JsonRpcClassPrefix . str_replace( ".", "_", $service );
      $serviceClassFile = $servicePath . "/" . str_replace( ".", "/", $service ) . ".php";
    }
    $this->serviceClass = $serviceClass;
    $this->debug( "Loading file '$serviceClassFile', containing class '$serviceClass' for requested service '$service'");

    if( ! file_exists($serviceClassFile) )
    {
      throw new AbstractError( "Service '$service' not found.", JsonRpcError_ServiceNotFound );
    }

    // load service class definition
    require_once $serviceClassFile;
    if( ! class_exists( $serviceClass ) )
    {
      throw new LogicException("File '$serviceClassFile' does not contain definition for class '$serviceClass'");
    }

    return $serviceClassFile;
  }

  /**
   * Returns the real service class name. There are a couple of variants
   * possible
   * @param string $service
   * @param array $classes When overriding this method, an array of
   * possible class name variations can be passed to this as a
   * parent method.
   * @return string|false The name of the class it exists, otherwise false
   */
  public function getServiceClass( $service, $classes = array() )
  {
    return $this->serviceClass;
  }

  /**
   * Overridden to store the current service object as controller
   * object.
   */
  public function getServiceObject( $className )
  {
    /*
     * get service object from parent method
     */
    $serviceObject = parent::getServiceObject( $className );

    /*
     * store service object
     */
    $this->controller = $serviceObject;

    return $serviceObject;
  }

  /**
   * Check the accessibility of service object and service
   * method. Aborts request when access is denied.
   * @param $serviceObject
   * @param $method
   * @return void
   */
  public function checkAccessibility( $serviceObject, $method )
  {
    $this->getAccessController()->checkAccessibility( $serviceObject, $method );
  }

  /**
   * Format the response string, given the service method output.
   * By default, wrap it in a result map and encode it in json.
   * @param mixded $data
   * @internal param \mixded $output
   * @return string
   * @todo rework this
   */
  public function formatOutput( $data )
  {
    /*
     * if requested, skip all jsonrpc-wrapping and output
     * raw data
     * @todo document
     */
    if( $_REQUEST['qcl_output_format'] == "raw" )
    {
      return $this->json->encode( $data );
    }
    
    /*
     * response object
     */
    $response = $this->getResponseObject();

    /*
     * request id
     */
    $requestId = $this->getId();
    $response->setId( $requestId );

    /*
     * events and messages
     */
    $app = $this->getApplication();
    $event_transport = $app->getIniValue("service.event_transport");

    if ( $app and $event_transport )
    {
      $events    = $app->getEventDispatcher()->getClientEvents(); 
      $response->setEvents( $events );
      $sessionId = $app->getAccessController()->getSessionId();
      $messages  = $app->getMessageBus()->getClientMessages( $sessionId );
      $response->setMessages( $messages );
    }

    if( is_a( $data, "qcl_data_Result" ) )
    {
      $data = $data->toArray();
    }

    $response->setData( $data );
    $json = $this->json->encode( $response->toArray() );
    //qcl_log_Logger::getInstance()->info($json);
    return $json;
  }

  /**
   * Hook for subclasses to locally log the error message
   * @param string $msg Error Message
   * @param bool $includeBacktrace Whether a manual backtrace should be printed as well
   * @return void
   */
  public function logError( $msg, $includeBacktrace = false )
  {
    qcl_log_Logger::getInstance()->error( $msg, $includeBacktrace );
  }
}