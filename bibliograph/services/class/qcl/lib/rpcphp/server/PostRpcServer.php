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
 *  * Christian Boulanger (cboulanger) Error-Handling and OO-style rewrite
 */
require_once dirname(__FILE__) . "/JsonRpcServer.php";

/*
 * This is a simple extension to the JsonRpcServer to allow to test
 * the methods with post data instead of Json data. You can also
 * allow GET data.
 *
 * Usage:
 *
 * require "/path/to/RpcPhp/services/server/PostRpcServer.php";
 * $server = new PostRpcServer;
 * $server->start();
 *
 * Or, with a singleton pattern:
 * require "/path/to/RpcPhp/services/server/PostRpcServer.php";
 * PostRpcServer::run();
 */
class PostRpcServer extends JsonRpcServer
{

  /**
   * Whether the server can also use GET parameters for the
   * request.
   * @var boolean
   */
  var $allowGetParams = true;

  /**
   * Return singleton instance of the server
   * return PostRpcServer
   */
  static function getInstance()
  {
    if ( ! is_object( $GLOBALS[__CLASS__] ) )
    {
      $GLOBALS[__CLASS__] = new PostRpcServer;
    }
    return $GLOBALS[__CLASS__];
  }

  /**
   * Starts a singleton instance of the server. Must be called statically.
   */
  static function run()
  {
    $_this = PostRpcServer::getInstance();
    $_this->start();
  }

  /**
   * @override
   * @see JsonRpcServer::getInput()
   */
  function getInput()
  {
    /*
     * whether to allow GET and POST or POST only
     */
    $input = $this->allowGetParams ? (object) $_REQUEST : (object) $_POST;

    /*
     * decode service parameters
     */
    $input->params = $this->json->decode( "[" . stripslashes( $input->params ). "]" );

    /*
     * server data are all parameters that are not "service", "method", and "params"
     */
    $server_data_keys = array_diff(
      array_keys( (array) $input ),
      array( "service","method","params")
    );
    $server_data = array();
    foreach ( $server_data_keys as $key )
    {
      $server_data[$key] = $input->$key;
    }
    $input->server_data = (array) $server_data;

    $this->debug("Getting input from post data: " . print_r($input,true) );
    return $input;
  }

  /**
   * Format the response string. If we get a scalar value, just output it,
   * otherwise jsonify it.
   * @override
   * @param mixded $output
   * @return string
   */
  function formatOutput( $output )
  {
    if ( ! is_scalar($output) )
    {
      $output = $this->json->encode($output);
    }
    return $output;
  }
}
