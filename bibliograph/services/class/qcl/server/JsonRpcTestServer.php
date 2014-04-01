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
 *  * Christian Boulanger (cboulanger) Error-Handling and OO-style rewrite
 */

qcl_import( "qcl_server_JsonRpcServer" );
qcl_import( "qcl_server_Request" );

/**
 * This is a server that can be used to test the service classes by using
 * fake JSON-RPC data requests without requiring an actual http request.
 * You can use this class, for example, with a debugger.
 */
class qcl_server_JsonRpcTestServer
  extends qcl_server_JsonRpcServer
{
  /**
   * The test data
   * @var string|array
   */
  private $jsonrpcRequest;

  /**
   * Constructor. Takes the jsonrpc request string as argument
   * @param $jsonrpcRequest
   * @return \qcl_server_JsonRpcTestServer
   */
  function __construct( $jsonrpcRequest )
  {
    $this->jsonrpcRequest = $jsonrpcRequest;
    parent::__construct();
  }

  /**
   * overridden to replace http data with test data
   */
  function getInput()
  {
    $input ="";

    if ( is_array( $this->jsonrpcRequest ) )
    {
      /*
       * convert array into object
       */
      $input = (object) $input;
    }
    elseif ( is_string( $this->jsonrpcRequest ) )
    {
      /*
       * decode json data
       */
      $input = $this->json->decode( $this->jsonrpcRequest );
    }
    else
    {
      throw new JsonRpcException("Invalid request data");
    }

    /*
     * Ensure that this was a valid JSON-RPC service request
     */
    if (!(isset ($input->service) && isset ($input->method) && isset ($input->params))) {
      /*
       * This request was not issued with JSON-RPC so echo the error rather than
       * issuing a JsonRpcError response.
       */
       throw new JsonRpcError("JSON-RPC request expected; service, method or params missing");
    }

    /*
     * populate request object
     */
    $request = qcl_server_Request::getInstance();
    $request->set( $input );

    /*
     * call the application so that the service->class mapping
     * is setup before the services are called.
     */
    $this->getApplication();

    /*
     * return data
     */
    return $input;
  }
}
?>