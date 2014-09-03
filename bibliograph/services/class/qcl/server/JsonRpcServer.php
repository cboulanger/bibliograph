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


  /**
   * An array mapping services to service classes
   */
  private $serviceClassMap = array();

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
   * @throws qcl_InvalidClassException
   * @return qcl_application_Application|false
   */
  public function getApplication()
  {

    if ( qcl_application_Application::getInstance() === null )
    {
      /*
       * determine application class name
       */
      $request = qcl_server_Request::getInstance();
      $service = new String( $request->getService() );
      $appClass = (string) $service
        ->substr( 0, $service->lastIndexOf(".") )
        ->replace("/\./","_")
        ->concat( "_Application" );

      try
      {
        /*
         * import class file
         */
        qcl_import( $appClass );

        /*
         * instantiate new application object
         */
        $app = new $appClass;
        if ( ! $app instanceof qcl_application_Application )
        {
          throw new qcl_InvalidClassException(
            "Application class '$appClass' must be a subclass of 'qcl_application_Application'"
          );
        }

        /*
         * store application instance
         */
        qcl_application_Application::setInstance( $app );

        /*
         * call main() method to start application
         */
        $app->main();

      }
      catch( qcl_FileNotFoundException $e )
      {
        qcl_log_Logger::getInstance()->warn( "No or unfunctional application: " . $e->getMessage() );
        qcl_application_Application::setInstance( false );
      }
    }
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

  /**
   * Maps a service to a class
   * @param $service
   * @param $class
   * @return void
   */
  public function mapServiceToClass( $service, $class )
  {
    if ( ! is_string( $service ) or ! is_string( $class ) )
    {
      trigger_error("Involid arguments");
    }
    $this->debug("Mapping service 'service' to class '$class'");
    $this->serviceClassMap[$service] = $class;
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
     * call the application so that the service->class mapping
     * is setup before the services are called.
     */
    $this->getApplication();

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
   * Overridden to allow mapping of services to classes
   * @param string $service
   * @param array $classes
   * @return string|false The name of the class it exists, otherwise false
   */
  function getServiceClass( $service, $classes = array() )
  {
    if ( isset( $this->serviceClassMap[$service] ) )
    {
      $class = $this->serviceClassMap[$service];
      $this->debug( "Service '$service' is implemented by class/service '$class'");
      return parent::getServiceClass( $class, array( $class ) );
    }
    else
    {
      return parent::getServiceClass( $service, array() );
    }
  }

  /**
   * Overridden to allow mapping of services to classes
   * @param string $service
   * @return string|false The name of the file if it was found, false if not.
   */
  public function loadServiceClass( $service )
  {
    if ( isset( $this->serviceClassMap[$service] ) )
    {
      $class = $this->serviceClassMap[$service];
      $this->debug( "Loading class/service '$class' for requested service '$service'");
      return parent::loadServiceClass( $class );
    }
    else
    {
      return parent::loadServiceClass( $service );
    }
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