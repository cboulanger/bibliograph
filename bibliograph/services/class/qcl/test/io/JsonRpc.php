<?php
require_once "qcl/data/datasource/Manager.php";
require_once "qcl/util/registry/Session.php";

/**
 * Service class containing test methods
 */
class qcl_test_data_jsonrpc
  extends qcl_data_datasource_Manager
{

  function test_testRpc($params)
  {
    return "Hello World! " .  var_export($params,true);
  }

  function test_testRpcFromPHP()
  {
    require_once "qcl/http/JsonRpcRequest.php";
    $request = new qcl_http_JsonRpcRequest();
    $result = $request->call(
      "qcl.jsonrpc.Tests.testRpc",
       array ( 'foo'  => "bar", 'blub' => 1 ),
       "baz",
       true
    );
    $this->info( $result ) ;
  }

}

?>