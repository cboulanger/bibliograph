<?php

class SetupControllerCest
{
  public function trySetup(ApiTester $I)
  {
    $I->sendJsonRpcRequest('setup','setup');
    $expected = '{"type":"ServiceResult","events":[{"name":"ldap.enabled","data":true},{"name":"bibliograph.setup.done","data":null}],"data":{"jsonrpc":"2.0","id":1,"result":{"errors":[],"messages":["Ini file exists.","File permissions ok.","Database connection ok.","Migrated data from Bibliograph v2 and applied new migrations.","Admininstrator email exists"]}}}';
    $I->assertEquals($expected, json_encode($I->grabJsonRpcResult())); 
  }
}
