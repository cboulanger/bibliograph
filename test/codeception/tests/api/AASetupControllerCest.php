<?php

class AASetupControllerCest
{

  protected $version;


  /**
   * Getting application version
   *
   * @param ApiTester $I
   * @return void
   */
  public function tryVersion(ApiTester $I)
  {
    $I->amGoingTo("test the 'setup.version' server method");
    $I->sendJsonRpcRequest('setup','version', ["a","b","c"]);
    $this->version = $I->grabJsonRpcResult();
    $I->amGoingTo("see if '$this->version' is a valid version number");
    $I->assertTrue( version_compare( $this->version, '0.0.1', '>' ), "Result should be a valid version number" );
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
    $I->compareJsonRpcResultWith( json_decode('[
      {
        "name": "ldap.enabled",
        "data": false
      },
      {
        "name": "bibliograph.setup.done",
        "data": null
      }
    ]'),"events");
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
          "name": "bibliograph.setup.done",
          "data": null
        }
      ],
      "data": {
        "errors": [
          
        ],
        "messages": [
          "File permissions ok.",
          "Admininstrator email exists.",
          "Database connection ok.",
          "Found empty database and applied new migrations for version ' . $this->version . '",
          "Configuration values were created.",
          "Created standard schema.",
          "Created datasources.",
          "Installed module \'backup\'.",
          "Installed module \'bibutils\'.",
          "Installed module \'converters\'.",
          "Installed module \'extendedfields\'.",
          "Installed module \'webservices\'.",
          "Installed module \'z3950\'.",
          "No schema migrations necessary.",
          "LDAP authentication is not enabled."
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
          "name": "bibliograph.setup.done",
          "data": null
        }
      ],
      "data": {
        "errors": [
          
        ],
        "messages": [
          "Admininstrator email exists.",
          "Database connection ok.",
          "Migrated data from Bibliograph v2 and applied new migrations for version ' . $this->version . '",
          "Configuration values were created.",
          "Created standard schema.",
          "Created datasources.",
          "Installed module \'backup\'.",
          "Installed module \'bibutils\'.",
          "Installed module \'converters\'.",
          "Installed module \'extendedfields\'.",
          "Installed module \'webservices\'.",
          "Installed module \'z3950\'.",
          "Migrated schema(s) bibliograph_datasource.",
          "LDAP authentication is not enabled."
        ]
      }
    }'));
  }

 /**
   * This upgrades from 3.0.0-alpha to 3.0.0
   *
   * @param ApiTester $I
   * @env upgradev3
   * @return void
   */
  public function tryUpgradeV3(ApiTester $I)
  {
    $upgrade_from = $I->grabCurrentAppVersion();
    $upgrade_to = '3.0.0';
    $I->sendJsonRpcRequest('setup','setup-version',[$upgrade_to]);
    $I->compareJsonRpcResultWith( json_decode('{
      "type": "ServiceResult",
      "events": [
        {
          "name": "bibliograph.setup.done",
          "data": null
        }
      ],
      "data": {
        "errors": [
          
        ],
        "messages": [
          "Admininstrator email exists.",
          "Database connection ok.",
          "Found data for version '. $upgrade_from .' and applied new migrations for version ' . $upgrade_to . '",
          "Configuration values were created.",
          "Standard schema existed.",
          "Created datasources.",
          "Module \'backup\' already installed.",
          "Module \'bibutils\' already installed.",
          "Module \'converters\' already installed.",         
          "Module \'extendedfields\' already installed.",
          "Module \'webservices\' already installed.",
          "Module \'z3950\' already installed.",          
          "No schema migrations necessary.",
          "LDAP authentication is not enabled."
        ]
      }
    }'));
  }
}
