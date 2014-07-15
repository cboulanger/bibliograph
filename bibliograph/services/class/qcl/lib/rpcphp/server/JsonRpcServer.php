<?php

/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2006-2010 Derrell Lipman, Christian Boulanger
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
 * This is a simple JSON-RPC server.  We receive a service name in
 * dot-separated path format and expect to find the class containing the
 * service in a file of the service name (with dots converted to slashes and
 * ".php" appended).
 *
 * Usage:
 *
 * require "/path/to/RpcPhp/services/server/JsonRpcServer.php";
 * $server = new JsonRpcServer;
 * $server->start();
 *
 * Or, with a singleton pattern:
 * require "/path/to/RpcPhp/services/server/JsonRpcServer.php";
 * JsonRpcServer::run();
 *
 */

require_once dirname(__FILE__) . "/AbstractServer.php";

/*
 * include Service introspection class by default
 */
require_once dirname(__FILE__) . "/ServiceIntrospection.php";


/*
 * include JsonRpcError class. If you want to use your own class, include a
 * custom implementation beforehand or use AbstractServer::setErrorBehavior()
 */
if ( ! class_exists("JsonRpcError") )
{
  require_once dirname(__FILE__) . "/error/JsonRpcError.php";
}

/**
 * Constant to indicate whether script transport is used.
 * @var unknown_type
 */
define("ScriptTransport_NotInUse", -1);

/**
 * Switches error handling on or off. Override in global_settings.php
 * default: on
 *
 */
if (! defined("JsonRpcErrorHandling"))
{
  define("JsonRpcErrorHandling", "on");
}

/*
 * Whether to encode and decode Date objects the "qooxdoo way"
 *
 * JSON does not handle dates in any standard way.  For qooxdoo, we have
 * defined a format for encoding and decoding Date objects such that they can
 * be passed reliably from the client to the server and back again,
 * unaltered.  Doing this necessitates custom changes to the encoder and
 * decoder functions, which means that the standard (as of PHP 5.2.2)
 * json_encode() and json_decode() functions can not be used.  Instead we just
 * use the encoder and decoder written in PHP which is, of course, much
 * slower.
 *
 * We here provide the option for an application to specify whether Dates
 * should be handled in the qooxdoo way.  If not, and the functions
 * json_encode() and json_decode() are available, we will use them.  Otherwise
 * we'll use the traditional, PHP, slower but complete for qooxdoo
 * implementation.
 *
 * (This is really broken.  It's not possible to determine, on a system-wide
 * basis, whether Dates will be used.  This should be settable as a pragma on
 * the request so we know whether we can use the built-in decoder, and we
 * provide some way to should keep track of whether any Dates are included in
 * the response, so we can decide whether to use the built-in encoder.)
 *
 * Modification: CB - turn date handling off by default
 */
if ( ! defined("handleQooxdooDates") )
{
  define( "handleQooxdooDates", false );
}


/**
 * JSON RPC server
 */
class JsonRpcServer extends AbstractServer
{

  /**
   * The id of the request if using script transport
   */
  protected $scriptTransportId;

  /**
   * Singleton instance
   * @var JsonRpcServer
   */
  static private $instance;

  /**
   * Return singleton instance of the server
   * @return JsonRpcServer
   */
  public static function getInstance()
  {
    if ( ! self::$instance )
    {
      self::$instance = new self;
    }
    return self::$instance;
  }


  /**
   * Starts a singleton instance of the server. Must be called statically.
   */
  public static function run()
  {
    $_this = JsonRpcServer::getInstance();
    $_this->start();
  }

  /**
   * Starts the server, setting up error handling.
   */
  function start()
  {
    /*
     * Setup error handling to keep PHP from
     * messing up the JSONRPC response if a parsing or runtime error occurs,
     * and to allow the client application to handle those errors nicely
     */
    if ( JsonRpcErrorHandling == "on")
    {
      $this->setupErrorHandling();
    }

    /*
     * normal error handling for the server code,
     * application code will be handled in callServiceMethod()
     */
    try
    {
      parent::start();
    }
    catch( Exception $e )
    {
      $msg = $e->getMessage();

      /*
       * logging the error might produce other exceptions
       */
      try
      {
        $this->logError( $msg );
      }
      catch( Exception $e )
      {
        $msg = $e->getMessage();
      }

      /*
       * JsonRpcException and subclasses know how to format themselves
       * for JSONRPC responses, others will be used by the
       * standard error behavior.
       */
      if( $e instanceof JsonRpcException )
      {
        $jsonRpcError = $e;
      }
      else
      {
        // do not send error code 0, because client thinks that's "no internet connection"
        $error_code = $e->getCode() ? $e->getCode() : 9999;
        $jsonRpcError =  $this->getErrorBehavior();
        $jsonRpcError->setError( $error_code, $msg );
      }

      /*
       * send error data
       */
      $jsonRpcError->sendAndExit();

      // shouldn't get here
      exit;
    }
  }

  /**
   * Initialize the server.
   * @return void
   */
  function initializeServer()
  {

    /*
     * Create a new instance of the json and error object
     */
    $this->json  = new JsonWrapper();

    /*
     * set error behavior
     */
    $errorBehavior = new JsonRpcError;
    $this->setErrorBehavior( $errorBehavior );

    /*
     * Assume (default) we're not using ScriptTransport
     */
    $this->setScriptTransportId( ScriptTransport_NotInUse );

    /*
     * set method accessibility behavior if not already set
     *
     */
    if ( ! $this->getAccessibilityBehavior() )
    {
      $accessibilityBehavior = new AccessibilityBehavior( $this );
      $this->setAccessibilityBehavior( $accessibilityBehavior );
    }
    $this->debug("Server initialized.");
  }

  /**
   * Setter for script transport id. Sets the id of the
   * error behavior object, too.
   * @param int $id
   * @return void
   */
  function setScriptTransportId ( $id )
  {
    $this->scriptTransportId = $id;
    $errorBehavior = $this->getErrorBehavior();
    $errorBehavior->SetScriptTransportId( $id );
  }

  /**
   * Getter for script transport id
   * @return int
   */
  function getScriptTransportId()
  {
    return $this->scriptTransportId;
  }


  /**
   * Return the input as a php object if a valid
   * request is found, otherwise throw a JsonRpcException
   * @return StdClass
   * @throws JsonRpcException
   */
  function getInput()
  {
    if ( $_SERVER["REQUEST_METHOD"] == "POST" )
    {
      /*
       * For POST data, the only acceptable content type is application/json.
       */
      switch(substr( $_SERVER["CONTENT_TYPE"],
      0,
      strcspn( $_SERVER["CONTENT_TYPE"], ";" ) ) )
      {
        case "application/json":
          /*
           * We found literal POSTed json-rpc data (we hope)
           */
          $input = file_get_contents('php://input');

          /*
           * if we have "new Date" in the input, use the PHP-only
           * json encoder/decoder. This is a bit of a hack since
           * we cannot distinguish between a real json-Date and the
           * occurrence of the string "new Date(" in arbitrary string
           * data.
           */
          if ( handleQooxdooDates and  strstr($input, "new Date(") )
          {
            $this->json->useJsonClass();
          }

          /*
           * decode json data
           */
          $input = $this->json->decode($input);
          break;

        default:
          /*
           * This request was not issued with JSON-RPC so echo the error rather
           * than issuing a JsonRpcError response.
           */
          throw new JsonRpcError("JSON-RPC request expected; unexpected data received");
      }
    }
    else if (
      $_SERVER["REQUEST_METHOD"] == "GET" &&
      isset( $_GET["_ScriptTransport_id"]) &&
      $_GET["_ScriptTransport_id"] != ScriptTransport_NotInUse &&
      isset( $_GET["_ScriptTransport_data"] ) )
    {
      /*
       * We have what looks like a valid ScriptTransport request
       */
      $this->setScriptTransportId($_GET["_ScriptTransport_id"]);
      $input = $_GET["_ScriptTransport_data"];
      $input = $this->json->decode(
        get_magic_quotes_gpc() ? stripslashes($input) : $input
      );
    }
    else
    {
      /*
       * This request was not issued with JSON-RPC so echo the error rather than
       * issuing a JsonRpcError response.
       */
      throw new JsonRpcException( "Services require JSON-RPC" );
    }

    /*
     * Ensure that this was a JSON-RPC service request
     */
    if (! isset($input) ||
    ! isset($input->service) ||
    ! isset($input->method) ||
    ! isset($input->params))
    {
      /*
       * This request was not issued with JSON-RPC so echo the error rather than
       * issuing a JsonRpcError response.
       */
      throw new JsonRpcError( "JSON-RPC request expected; service, method or params missing");
    }
    return $input;
  }

  /**
   * Overridden to provide jsonrpc error handling.
   * @param object $serviceObject
   * @param string $method
   * @param array $params
   * @return mixed
   */
  function callServiceMethod( $serviceObject, $method, $params )
  {

    $result = parent::callServiceMethod( $serviceObject, $method, $params );

    /*
     * See if the result of the function was an error object thrown by the service method
     * and if yes, add the script transport id.
     */
    if ( $result instanceof JsonRpcError )
    {
      $result->setScriptTransportId($this->getErrorBehavior()->getScriptTransportId() );
    }

    return $result;
  }

  /**
   * Format the response string, given the service method output.
   * By default, wrap it in a result map and encode it in json.
   * @param mixded $output
   * @return string
   */
  function formatOutput( $output )
  {
    $ret = array(
      "result"  => $output,
       "id"     => $this->getId()
    );

    return $this->json->encode($ret);
  }

  /**
   * Setup error handling to prevent PHP from messing up the json
   * response. This is only necessary if display_errors = ON.
   */
  function setupErrorHandling()
  {

    /*
     * This will only work if the php error contains no double quotation marks and no
     * characters that need to be escaped (e.g., newline).
     */
    ini_set('error_prepend_string', '{"phperror":"');
    ini_set('error_append_string', '",' .
        '  "error":' .
        '  {' .
        '    "origin":' . JsonRpcError_Origin_Server . ',' .
        '    "code":' .  JsonRpcError_ScriptError . ',' .
        '    "message":"Fatal PHP Error. See response content for error description ' .
        ' "}' .
        '}'
    );

    /*
     * error handler function for php jsonrpc
     */
    set_exception_handler( array( $this, "jsonRpcExceptionHandler" ) );

    set_error_handler( array( $this,"jsonRpcErrorHandler" ) );
//    set_error_handler(
//      create_function(
//        '$severity, $message, $file, $line',
//        'throw new ErrorException($message, $severity, $severity, $file, $line);'
//      )
//    );

  }

  /**
   *
   * @param Exception $exception
   * @return void
   */
  function jsonRpcExceptionHandler( Exception $e )
  {
    $errtype = "Uncaught Exception";
    $errstr  = $e->getMessage();
    $errfile = $e->getFile();
    $errline = $e->getLine();

    /*
     * Error message
     */
    $errmsg = "$errtype: $errstr in $errfile, line $errline ";

    /*
     * log error message
     */
    $this->logError( $errmsg, true );

    /*
     * return jsonified error message
     */
    $this->getErrorBehavior()->setError(null, $errmsg);
    $this->getErrorBehavior()->sendAndExit();
  }

  /**
   * Jsonrpc error handler to output json error response messages
   * @param int $errno
   * @param string $errstr
   * @param string $errfile
   * @param string $errline
   * @return void
   */
  function jsonRpcErrorHandler($errno, $errstr, $errfile, $errline)
  {

    /*
     * Determine error type
     * @todo: remove those which are not captured by set_error_handler()
     */
    $includeBacktrace = false;
    switch($errno)
    {
      case E_ERROR:
        $errtype= "Error";
        $includeBacktrace = true;
        break;

      case E_WARNING:
        $errtype= "Warning";
        break;

      case E_NOTICE:
        $errtype= "Notice";
        break;

      case E_USER_ERROR:
        $errtype= "User Error";
        $includeBacktrace = true;
        break;

      case E_USER_WARNING:
        $errtype= "User Warning";
        break;

      case E_USER_NOTICE:
        $errtype= "User Notice";
        $includeBacktrace = true;
        break;

      case E_STRICT:
        $errtype= "Strict Notice";
        break;

      case E_RECOVERABLE_ERROR:
        $errtype= "Recoverable Error";
        break;

      case E_DEPRECATED:
        $errtype= "Deprecated Error";
        break;

      default:
        $errtype= "Unknown error ($errno)";
        break;
    }

    /*
     * respect error_reporting level
     */
    $errno = $errno & error_reporting();
    if ($errno == 0) return true;

    /*
     * Error message
     */
    $errmsg = "$errtype: $errstr in $errfile, line $errline ";

    /*
     * log error message
     */
    $this->logError( $errmsg, $includeBacktrace );

    /*
     * return jsonified error message
     */
    $this->getErrorBehavior()->setError($errno, $errmsg);
    $this->getErrorBehavior()->sendAndExit();

    // never gets here
    exit;
  }
}
?>