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

qcl_import( "qcl_server_JsonRpcServer" );
qcl_import( "qcl_server_Request" );

/**
 * This is a server that can be used with REST data, i.e. POST or GET
 * data. The RPC data is passed in the "service", "method" and "params"
 * paramters. The 'params' parameter contains a comma-separated list
 * of arguments, which will all treated as string data (so no quoting is
 * necessary). This behavior will probably be refined in a future version.
 * The server_data object will be assembled from all the REST parameters
 * that are additionally passed.
 */
class qcl_server_JsonRpcRestServer
  extends qcl_server_JsonRpcServer
{

  /**
   * overridden to replace raw json post data with the data from
   * the request
   */
  function getInput()
  {
    try
    {
      qcl_assert_array_keys( $_REQUEST, array(
        "service", "method", "params"
      ) );
    }
    catch( InvalidArgumentException $e )
    {
      echo "Invalid request: service, method or params missing.";
      exit;
    }

    /*
     * service and method
     */
    $input = new stdClass();
    $input->service = $_REQUEST['service'];
    $input->method  = $_REQUEST['method'];

    /*
     * json-rpc parameters
     */
    $params = $_REQUEST['params'];

    if( $params == "[]" or ! $params )
    {
      $input->params = array();
    }
    else
    {
      if ( $params[0] != "[" )
      {
        // backwards compatibility: previously it wasn't necessary to pass a true json array
        $params = '["'. implode('","', explode(",", $_REQUEST['params'] ) ) . '"]';
      }

      // @todo not sure why sometimes the quotes in the json string are escaped
      $input->params  = either( json_decode( $params ), json_decode( stripslashes( $params ) ) );
    }

    /*
     * non-standard server data
     */
    $server_data = array_diff_key(
      $_REQUEST, array( "service"=>"","method"=>"","params"=>"" )
    );
    $input->server_data = count( $server_data )
      ? (object) $server_data
      : null;

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