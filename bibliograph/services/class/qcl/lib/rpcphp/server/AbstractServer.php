<?php

/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2006-2009 Derrell Lipman, Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Derrell Lipman (derrell)
 *  * Christian Boulanger (cboulanger) Error-Handling and OO-style rewrite
 */


/*
 * Include json class, either a wrapper around the native php json de/encoder or
 * the "date hack" JSON encoding class. If you want to use a custom json encoder/decoder,
 * write a custom JsonWrapper class and include it before you include this script.
 */
if ( ! class_exists("JsonWrapper") )
{
  require_once dirname(__FILE__) . "/lib/JsonWrapper.php";
}

/*
 * There may be cases where all services need use of some libraries or
 * system-wide definitions.  Those may be provided by a file named
 * "global_settings.php" in the same directory as the file that includes
 * this file (i.e. the file instantiating the server).
 *
 * The global settings file may provide values for the following manifest
 * constants whose default values are otherwise provided below:
 *
 *   servicePathPrefix
 *   defaultAccessibility
 *
 */
if ( file_exists("global_settings.php") )
{
  require_once "global_settings.php";
}

/**
 * The default accessibility behavior, which
 * serves as a base class for all more specialized
 * behaviors. Use setAccessibilityBehavior() to set
 * your custom accessibility behavior, which must subclass
 * AccessibilityBehavior
 */
require_once dirname(__FILE__) . "/access/AccessibilityBehavior.php";

/**
 * The location of the service class directories.
 * (trailing slash required). Defaults to "class"
 */
if ( ! defined("servicePathPrefix") )
{
  define( "servicePathPrefix", "class/" );
}

/**
 * How to pass the RPC parameters to the service method. Can have
 * three values:
 *
 * 1) 'array': The signature used in RpcPhp 1.0, passing all
 * parameters as an array in the first method argument, and the error
 * object as the second argument.This is the default mode (if the
 * constant is not defined by the user).
 *
 * 2) 'args': Pass the parameters as a normal method call, so that
 * the method definition can use them as named arguments. This also
 * improves the ability to document the source code of the service methods.
 * This will be the standard mode in a future version of the server.
 * The error object does not need to be passed to the service method any
 * longer.
 *
 * 3) 'check': Via method introspection, this checks whether the first
 * argument of the method is called "params". If yes, use the 'array' mode.
 * If not, use the 'args' mode.
 *
 */
if ( ! defined("RpcMethodSignatureMode") )
{
    define( "RpcMethodSignatureMode", "check" );
}

/**
 * Prefixes for RPC classes and methods
 *
 * Since you do not want to expose all classes or all methods that are
 * present in the files accessible to the server, a prefix is needed
 * for classes and methods. By default, this is "class_" for classes
 * and "method_" for methods. You might want to keep those prefixes if
 * you want to share backend class code with others (otherwise, a simple
 * search & replace takes care of quickly, too) - otherwise define the
 * following constants in global_settings.php
 */
if (! defined("JsonRpcClassPrefix"))
{
  define("JsonRpcClassPrefix", "class_");
}

if (! defined("JsonRpcMethodPrefix"))
{
  define("JsonRpcMethodPrefix", "method_");
}

/**
 * Whether the server should issue debug messages. Override the
 * debug() method or change the following constants for custom behavior
 */
if (! defined("JsonRpcDebug"))
{
  define("JsonRpcDebug", false );
}

if (! defined("JsonRpcDebugFile"))
{
  define("JsonRpcDebugFile", "/tmp/phpinfo");
}

/**
 * Abstract server class, needs to be subclassed in
 * order to be used.
 * @author Derrell Lipman
 * @author Christian Boulanger
 */
class AbstractServer
{

  /**
   * Whether the server should issue debug messages
   */
  public $debug = JsonRpcDebug;

  /**
   * The file for debug messages
   * @var string
   */
  public $debugfile = JsonRpcDebugFile;

  /**
   * Json wrapper object, allowing to implement custom json renderers
   * @todo rewrite with behavior design pattern
   * @var JsonWrapper
   */
  public $json;

  /**
   * An array of paths to the services. This will be populated
   * by the servicePathPrefix constant in the constructor, but
   * you can also manually populate it.
   * @var array
   */
  public $servicePaths;

  /**
   * The request of the id.
   * @var int
   */
  public $id;

  /**
   * The input data from the request
   * @var object
   */
  public $input;

  /**
   * The full service path
   * @var string
   */
  public $service;

  /**
   * the components of the service
   * @var array
   */
  public $serviceComponents;

  /**
   * The current service name
   * @var string
   */
  public $serviceName;

  /**
   * The current service class
   * @var string
   */
  public $serviceClass;

  /**
   * The current service object
   * @var object
   */
  public $serviceObject;

  /**
   * The current service method
   * @var string
   */
  public $method;

  /**
   * The parameters of the request
   * @var array
   */
  public $params;

  /**
   * The server data sent with the request
   * @var object
   */
  public $serverData;

  /**
   * The php session id
   */
  public $sessionId;

  /**
   * Error behavior object
   * @var AbstractError
   */
  private $errorBehavior;

  /**
   * The accessibility behavior object
   * @var AccessibilityBehavior
   */
  private $accessibilityBehavior;

  /**
   * Constructor
   */
  function __construct()
  {
    if ( get_class( $this ) == __CLASS__ )
    {
      trigger_error("You must subclass AbstractServer in order to use it.");
    }

    /*
     * Use servicePathPrefix constant for backwards compatibility
     */
    $this->servicePaths = array (
      dirname(__FILE__) . "/services",
      servicePathPrefix
    );

    /**
     * Hook for subclasses to do initialization stuff.
     */
    $this->initializeServer();
  }

  /**
   * Initialize the server. This serves as a hook for subclassing
   * servers.
   * @return void
   */
  public function initializeServer()
  {
    trigger_error("Not implemented");
  }

  /**
   * Setter for request id
   * @param int $id
   * @return void
   */
  public function setId( $id )
  {
    $this->id = $id;
  }

  /**
   * Getter for request id
   * @return int
   */
  public function getId()
  {
    return $this->id;
  }

  /**
   * Setter for service name
   * @param string $service
   * @return void
   */
  public function setService( $service )
  {
    $this->service = $service;
  }

  /**
   * Getter for service name
   * @return string
   */
  public function getService()
  {
    return $this->service;
  }

  /**
   * Setter for service method
   * @param string $method
   * @return void
   */
  public function setMethod( $method )
  {
    $this->method = $method;
  }

  /**
   * Getter for method name
   * @return string
   */
  public function getMethod()
  {
    return $this->method;
  }

  /**
   * Setter for service parameters
   * @param array $params
   * @return void
   */
  public function setParams( $params )
  {
    $this->params = $params;
  }

  /**
   * Getter for service parameters
   * @return string
   */
  public function getParams()
  {
    return $this->params;
  }


  /**
   * Setter for server data
   * @param stdClass $serverData
   * @return void
   */
  public function setServerData( $serverData )
  {
    $this->serverData = $serverData;
  }

  /**
   * Returns the client-sent server data.
   * @param string[optional] $key If provided, get only a key, otherwise return the map
   * @return mixed Either the value of the given key, or the whole map
   */
  public function getServerData( $key=null )
  {
    if ( is_null( $key ) )
    {
      return $this->serverData;
    }
    elseif ( is_object( $this->serverData ) )
    {
      if ( isset( $this->serverData->$key ) )
      {
        return $this->serverData->$key;
      }
    }
    return null;
  }

  /**
   * Setter for service paths
   * @param array|string $servicePaths
   */
  public function setServicePaths( $servicePaths )
  {
    $sp = array();
    foreach ( (array) $servicePaths as $path )
    {
      if ( ! is_dir( $path ) )
      {
        trigger_error( "'$path' is not a directory." );
      }
      $sp[] = $path;
    }
    $this->servicePaths = $sp;
  }

  /**
   * Getter for service paths
   */
  public function getServicePaths()
  {
    return $this->servicePaths;
  }

  /**
   * Setter for accessibility behavior object
   * @param AccessibilityBehavior $object
   * @return void
   */
  public function setAccessibilityBehavior( $object )
  {
    if ( ! is_a( $object, "AccessibilityBehavior") )
    {
      trigger_error("Accessibility behavior object must subclass AccessibilityBehavior");
    }
    $this->accessibilityBehavior = $object;
  }

  /**
   * Getter for accessibility behavior object
   * @return AccessibilityBehavior
   */
  public function getAccessibilityBehavior()
  {
    return $this->accessibilityBehavior;
  }


  /**
   * Setter for error behavior object
   * @param AbstractErrorBehavior $object
   * @return void
   */
  public function setErrorBehavior( $object )
  {
    if ( ! is_a( $object, "AbstractError") )
    {
      trigger_error("The error behavior object must subclass AbstractErrorBehvior");
    }
    $this->errorBehavior = $object;
  }

  /**
   * Getter for error behavior object
   * @return AbstractError
   */
  public function getErrorBehavior()
  {
    return $this->errorBehavior;
  }

  /**
   * Start the server.
   */
  public function start()
  {

    /**
     * error behavior
     * @todo this could be rewritten using interfaces
     */
    $errorBehavior = $this->getErrorBehavior();
    if ( ! is_a( $errorBehavior, "AbstractError" ) )
    {
      throw new AbstractError("No valid error behavior instance!");
    }

    /**
     * accessibility behavior
     * @todo this could be rewritten using interfaces
     */
    $accessibilityBehavior = $this->getAccessibilityBehavior();
    if ( ! $accessibilityBehavior )
    {
      throw new AbstractError("No accessibility behavior!");
    }

    /*
     * Check request and content type and get the
     * input data. If no data, abort with error.
     */
    $input = $this->getInput();
    if ( ! $input )
    {
      throw new AbstractError("No input");
    }

    $this->input = $input;

    /*
     * request data
     */
    $service    = $input->service;
    $method     = $input->method;
    $params     = $input->params;
    $id         = $input->id;
    $serverData = isset( $input->server_data ) ? $input->server_data : null;

    /*
     * configure this service request properties
     */
    $this->setId( $id );
    $this->setService( $service );
    $this->setMethod( $method );
    $this->setParams( $params );
    $this->setServerData( $serverData );

    /*
     * Ok, it looks like a valid request, so we'll return an
     * error object if we encounter errors from here on out.
     */
    $errorBehavior->setId( $this->id );

    $this->debug("Service request: $service.$method");
    $this->debug("Parameters: " . var_export($params,true) );
    $this->debug("Server Data: " . var_export($serverData,true) );

    /*
     * service components
     */
    $this->serviceComponents = $this->getServiceComponents( $service );

    /*
     * check service request
     */
    $this->debug("Checking service $service ..." );
    $validService = $this->checkService( $service );
    if ( ! $validService )
    {
      $this->sendErrorAndExit();
    }

    /*
     * load service class file
     */
    $this->debug("Loading service $service ..." );
    $classFile = $this->loadServiceClass( $service );
    if ( ! $classFile )
    {
      $this->sendErrorAndExit();
    }
    $this->debug("Loaded file '$classFile'");

    /*
     * check if class is defined in this file
     */
    $this->serviceClass = $this->getServiceClass( $service );
    if ( ! $this->serviceClass )
    {
      $this->sendErrorAndExit();
    }

    /*
     * now, start php session
     */
    $this->startSession();

    /*
     * instantiate service
     */
    $this->debug("Instantiating service class '{$this->serviceClass}'.");
    $serviceObject = $this->getServiceObject( $this->serviceClass );
    $this->serviceObject = $serviceObject;

    /*
     * check accessibility and abort if access is denied
     */
    $this->checkAccessibility( $serviceObject, $method );

    /*
     * Now that we've instantiated thes service, we should find the
     * requested method by prefixing the method prefix
     */
    $method = JsonRpcMethodPrefix . $method;
    $this->debug("Checking service method '$method'.");
    $validMethod = $this->checkServiceMethod( $serviceObject, $method );
    if ( ! $validMethod )
    {
      $this->sendErrorAndExit();
    }

    /*
     * Errors from here on out will be Application-generated
     */
    $this->getErrorBehavior()->setOrigin( JsonRpcError_Origin_Application );

    /*
     * start the service method and get its output
     */
    $this->debug("Starting Service method {$this->serviceClass}.$method");
    $result = $this->callServiceMethod( $serviceObject, $method, $params );
    $this->debug("Done. " );

    /*
     * See if the result of the function was actually an error
     */
    if ( $result instanceof AbstractError )
    {
      /*
       * Yup, it was. Set the origin to application and return the error
       * @todo this is totally broken
       */
      $code    = either( $result->getCode(), JsonRpcError_ScriptError);
      $message = either( $result->getMessage(), "Unknown Error");

      $result->setId( $this->getId() );
      $result->setOrigin( JsonRpcError_Origin_Application );
      $result->setError( $code, $message );

      $this->debug("### Error: $message (#$code)." );

      $result->sendAndExit();
      /* never gets here */
    }

    /*
     * Give 'em what they came for!
     */
    $this->output = $this->formatOutput( $result );

    /*
     * send reply
     */
    $this->debug("Sending output to client ...");
    $this->sendReply( $this->output );
  }

  /**
   * Explode the service name into its dot-separated parts
   * @param string $service
   */
  public function getServiceComponents( $service )
  {
    return explode( ".", $service );
  }

  /**
   * Ensure the requested service name is kosher.  A service name should be:
   *
   *   - a dot-separated sequences of strings; no adjacent dots
   *   - first character of each string is in [a-zA-Z]
   *   - other characters are in [_a-zA-Z0-9]
   * @param string $service
   * @return bool True if legal, false if illegal service name
   */
  public function checkService( $service )
  {
    /*
     * First check for legal characters
     */
    if (preg_match("#^[_.a-zA-Z0-9]+$#", $service) === false)
    {
      /*
       * There's some illegal character in the service name
       */
      throw new AbstractError(
        "Illegal character found in service name.",
        JsonRpcError_IllegalService
      );
    }

    /* Now ensure there are no double dots */
    if (strstr($service, "..") !== false)
    {
      throw new AbstractError(
        "Illegal use of two consecutive dots in service name",
      JsonRpcError_IllegalService
      );
    }

    /*
     * Ensure that each component begins with a letter
     */
    $serviceComponents = $this->getServiceComponents( $service );
    for ($i = 0; $i < count($serviceComponents); $i++)
    {
      if (preg_match("#^[a-zA-Z]#", $serviceComponents[$i]) === false)
      {
        throw new AbstractError(
          "A service name component does not begin with a letter",
          JsonRpcError_IllegalService
        );
      }
    }

    /*
     * service name is legal
     */
    return true;
  }

  /**
   * Loads the file containing the service class definition
   * @param string $service
   * @return string|false The name of the file if it was found, false if not.
   */
  public function loadServiceClass( $service )
  {
    /*
     * check service paths
     */
    if ( ! is_array( $this->servicePaths ) )
    {
      trigger_error("servicePaths property must be set with an array of paths");
    }

    /*
     * Replace all dots with slashes in the service name so we can
     * locate the service script.
     */
    $serviceComponents = $this->getServiceComponents( $service );
    $path = implode( "/", $serviceComponents );
    $packagePath = implode( "/", array_slice( $serviceComponents, 0, -1 ) );

    /*
     * Try to load the requested service
     */
    foreach( $this->servicePaths as $prefix )
    {
      $classFile = "$prefix/$path.php";
      if ( file_exists( $classFile ) )
      {
        $this->debug("Loading class file '$classFile'...");
        require_once $classFile;
        return $classFile;
      }
    }

    /*
     * Couldn't find the requested service
     */
    $serviceName = implode( ".", $serviceComponents );
    throw new AbstractError(
      "Service `$serviceName` not found.",
      JsonRpcError_ServiceNotFound
    );
  }

  /**
   * Returns the name of the service class as requested by the client
   * @param string $service
   * @return string
   */
  public function getServiceName( $service )
  {
    $serviceComponents = $this->getServiceComponents( $service );
    return $serviceComponents[count($serviceComponents) - 1];
  }

  /**
   * Returns the real service class name. There are a couple of variants
   * possible
   * @param string $service
   * @param array $classes When overriding this method, an array of
   * possible class name variations can be passed to this as a
   * parent method.
   * @return string|false The name of the class it exists, otherwise false
   * @author Derrell Lipman
   * @author Christian Boulanger
   */
  public function getServiceClass( $service, $classes = array() )
  {

    /*
     * Try the last component of the service
     */
    $serviceName = $this->getServiceName( $service );
    $classes[] = JsonRpcClassPrefix . $serviceName;

    /*
     * or the fully qualified service name, if the class name
     * mirrors the directory structure, i.e. foo_bar_Baz if the
     * service name is foo.bar.Baz
     */
    $classes[] = JsonRpcClassPrefix . implode( "_", $this->getServiceComponents( $service ) );

    /*
     * Ensure that the class exists.  First try the short class name.
     */
    foreach ( $classes as $className )
    {
      /*
       * return true if class exists
       */
      if ( class_exists($className) ) return $className;
    }

    /*
     * class name was not found
     */
    $this->debug("Service class '$serviceName' does not exist.");
    throw new AbstractError(
      "Service class `" . $serviceName . "` not found.",
      JsonRpcError_ClassNotFound
    );
  }

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
      $this->debug( "Starting session '{$serverData->sessionId}' from server_data.");
      session_id( $serverData->sessionId );
    }
    else
    {
      $this->debug( "Starting normal PHP session " . session_id() . ".");
    }
    session_start();
    $this->sessionId = session_id();
  }

  /**
   * Returns the actual service object, based on the class name.
   * Instantiates the object and returns it.
   * Override this if you want to pass additional data to the
   * service class' constructor.
   * @param string $className
   * @return Object
   */
  function getServiceObject( $className )
  {
    $serviceObject = new $className();
    return $serviceObject;
  }

  /**
   * Check the accessibility of service object and service
   * method. Aborts request when Access is denied.
   * @param $serviceObject
   * @param $method
   * @return void
   */
  public function checkAccessibility( $serviceObject, $method )
  {
    if ( $this->accessibilityBehavior )
    {
      $this->debug("Checking accessibility...");
      if ( ! $this->accessibilityBehavior->checkAccessibility( $serviceObject, $method ) )
      {
        throw new AbstractError(
          $this->accessibilityBehavior->getErrorMessage(),
          $this->accessibilityBehavior->getErrorNumber()
        );
      }
    }
  }

  /**
   * Checks whether we have a valid service method
   * @param $serviceObject
   * @param $method
   * @return unknown_type
   */
  public function checkServiceMethod( $serviceObject, $method )
  {
    if ( ! method_exists( $serviceObject, $method ) )
    {
      throw new AbstractError(
        "Method `" . $method . "` not found in service class `" . $this->getService() . "`.",
        JsonRpcError_MethodNotFound
      );
    }
    return true;
  }

  /**
   * Call the requested method. Override this method for a different behavior.
   * @param object $serviceObject
   * @param string $method
   * @param array $params
   * @return mixed
   */
  public function callServiceMethod( $serviceObject, $method, $params )
  {

    // call the method
    try
    {
       $result = call_user_func_array( array( $serviceObject, $method), $params );
    }

    /*
     * catch exceptions caused by invalid data supplied by the client which are not
     * runtime errors and do not require a stack trace
     * FIXME THIS IS A MESS
     */
    catch ( JsonRpcException $e )
    {
      $result = $this->getErrorBehavior();
      $result->SetError( either($e->getCode(),JsonRpcError_ScriptError), $e->getMessage() );
      $result->setId( $this->getId() );
      $result->sendAndExit();
    }

    /*
     * catch runtime errors and log backtrace
     */
    catch ( Exception $e )
    {
      $result = $this->getErrorBehavior();
      $result->SetError( either($e->getCode(),JsonRpcError_ScriptError), $e->getMessage() );
      $result->setId( $this->getId() );
      $this->logError( $e->getMessage() ."\n" . $e->getTraceAsString() );
      $result->sendAndExit();
    }
    return $result;
  }

  /**
   * Format the response string, given the service method output.
   * By default, return it as it is.
   * @param mixded $output
   * @return string
   */
  public function formatOutput( $output )
  {
    return $output;
  }

  /**
   * Hook for subclasses to locally save log messages. By default,
   * log to path in JsonRpcDebugFile constant, if it exists, otherwise
   * to system log.
   * @param string $msg Message
   * @return void
   */
  public function log( $msg )
  {
    if ( ( file_exists( JsonRpcDebugFile )
         && is_writable( JsonRpcDebugFile ) )
         || is_writable( dirname( JsonRpcDebugFile ) ) )
    {
      error_log( $msg . "\n", 3, JsonRpcDebugFile );
    }
    else
    {
      error_log( $msg . "\n" );
    }
  }

  /**
   * Logs an error message.
   * @param string $msg Error Message
   * @param bool $includeBacktrace Not implemented.
   * @return void
   */
  public function logError( $msg, $includeBacktrace = false )
  {
    $this->log("$msg");
  }

  /**
   * Debug function. Define your own function if you want
   * to do something else with the debug output
   * @param string $str
   */
  public function debug( $msg )
  {
    if ( $this->debug )
    {
      $this->log( $msg );
    }
  }

  /**
   * Returns the jsonrpc server response
   * @param $reply
   * @return unknown_type
   */
  public function sendReply( $reply )
  {
    $scriptTransportId = $this->scriptTransportId;

    /*
     * If not using ScriptTransport...
     */
    if ( ! $scriptTransportId or $scriptTransportId == ScriptTransport_NotInUse )
    {
      /*
       *  ... then just output the reply.
       */
      print $reply;
    }
    else
    {
      /*
       * Otherwise, we need to add a call to a qooxdoo-specific function
       */
      header("Content-Type: application/javascript");
      $reply =
        "qx.io.remote.transport.Script._requestFinished(" .
        $scriptTransportId . ", " . $reply . ");";
      print $reply;
    }

    /*
     * exit script
     */
    exit;
  }

  /**
   * Returns the url of the server
   * @return string
   */
  public function getUrl()
  {
    return "http://" . getenv ( HTTP_HOST ) . $_SERVER['PHP_SELF'];
  }

  /**
   * Returns the IP of the remote hosts
   * @return string
   */
  public function getRemoteIp()
  {
    return $_SERVER['REMOTE_ADDR'];
  }

}
?>