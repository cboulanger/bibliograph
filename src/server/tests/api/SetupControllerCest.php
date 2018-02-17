<?php

class SetupControllerCest
{

  public function tryVersion(ApiTester $I)
  {
    $I->wantToTest("the 'setup.version' server method");
    $I->sendJsonRpcRequest('setup','version');
    $I->assertTrue( version_compare( $I->grabJsonRpcResult(), '0.0.1', '>' ), "Result should be a valid version number" );
  }

  public function trySetup(ApiTester $I)
  {
    $I->wantToTest("the server setup process.");
    $I->sendJsonRpcRequest('setup','setup');
    $I->compareJsonRpcResultWith( json_decode('{
      "type": "ServiceResult",
      "events": [
        {
          "name": "ldap.enabled",
          "data": false
        },
        {
          "name": "bibliograph.setup.done",
          "data": null
        }
      ],
      "data": {
        "errors": [
          
        ],
        "messages": [
          "Ini file exists.",
          "File permissions ok.",
          "Database connection ok.",
          "No updates to the databases.",
          "LDAP authentication is not enabled.",
          "Admininstrator email exists.",
          "Example databases were created."
        ]
      }
    }'));
  }  
}
