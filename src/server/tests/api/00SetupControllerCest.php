<?php

class SetupControllerCest
{

  /**
   * Getting application version
   *
   * @param ApiTester $I
   * @return void
   * @env testing
   *
   */
  public function tryVersion(ApiTester $I)
  {
    $I->wantToTest("the 'setup.version' server method");
    $I->sendJsonRpcRequest('setup','version');
    $I->assertTrue( version_compare( $I->grabJsonRpcResult(), '0.0.1', '>' ), "Result should be a valid version number" );
  }

  /**
   * This calls the setup action with the migrations already applied.
   *
   * @param ApiTester $I
   * @env testing
   * @return void
   */
  public function tryNormalSetupWithLatestMigrationsApplied(ApiTester $I)
  {
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
          "Admininstrator email exists.",
          "Database connection ok.",
          "No updates to the databases.",
          "LDAP authentication is not enabled.",
          "Example databases were created."
        ]
      }
    }'));
  }  

  /**
   * This calls the setup action with an empty database and no migrations applied.
   * @param ApiTester $I
   * @env setup
   * @return void
   */
  public function trySetupWithEmptyDatabase(ApiTester $I)
  {
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
          "Admininstrator email exists.",
          "Database connection ok.",
          "Found empty database and applied new migrations for version 3.0.0-alpha",
          "LDAP authentication is not enabled.",
          "Example databases were created."
        ]
      }
    }'));
  }   

  /**
   * This calls the setup action with preexisting legacy data.
   *
   * @param ApiTester $I
   * @env upgradev2
   * @return void
   */
  public function tryUpgradeFromV2(ApiTester $I)
  {
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
          "Admininstrator email exists.",
          "Database connection ok.",
          "Migrated data from Bibliograph v2 and applied new migrations for version 3.0.0-alpha",
          "LDAP authentication is not enabled.",
          "Example databases were created."
        ]
      }
    }'));
  }

 /**
   * This upgrades from 3.0.0-alpha to 3.0.1
   *
   * @param ApiTester $I
   * @env upgradev3
   * @return void
   */
  public function tryUpgradeV3(ApiTester $I)
  {
    $I->sendJsonRpcRequest('setup','setup-version',["3.0.0"]);
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
          "Admininstrator email exists.",
          "Database connection ok.",
          "Found data for version 3.0.0-alpha and applied new migrations for version 3.0.0",
          "LDAP authentication is not enabled.",
          "Example databases were created."
        ]
      }
    }'));
  }       
}
