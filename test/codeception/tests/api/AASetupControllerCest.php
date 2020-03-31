<?php

class AASetupControllerCest
{

  protected $version;


  /**
   * Getting application version
   *
   * @param ApiTester $I
   * @return void
   * @throws Exception
   */
  public function tryVersion(ApiTester $I)
  {
    $I->amGoingTo("test the 'setup.version' server method");
    $I->sendJsonRpcRequest('setup','version');
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
   * @throws Exception
   */
  public function tryNormalSetupWithLatestMigrationsApplied(ApiTester $I)
  {
    $I->sendJsonRpcRequest('setup','setup');
    $I->seeServerEvent("bibliograph.setup.done");
  }

  /**
   * This calls the setup action with an empty database and no migrations applied.
   * @param ApiTester $I
   * @env setup
   * @return void
   * @throws Exception
   */
  public function trySetupWithEmptyDatabase(ApiTester $I)
  {
    $I->sendJsonRpcRequest('setup','setup');
    $I->seeServerEvent("bibliograph.setup.done");
    $I->seeResponseContains("Found empty database and applied new migrations for version ' . $this->version . '");
    $I->seeResponseContains("No schema migrations necessary.");
  }

  /**
   * This calls the setup action with preexisting legacy data.
   *
   * @param ApiTester $I
   * @env upgradev2
   * @return void
   * @throws Exception
   */
  public function tryUpgradeFromV2(ApiTester $I)
  {
    $I->sendJsonRpcRequest('setup','setup');
    $I->seeServerEvent("bibliograph.setup.done");
    $I->seeResponseContains("Migrated data from Bibliograph v2 and applied new migrations for version ' . $this->version . '");
    $I->seeResponseContains("Migrated schema(s) bibliograph_datasource.");
  }

  /**
   * This upgrades from 3.0.0-alpha to 3.0.0
   *
   * @param ApiTester $I
   * @env upgradev3
   * @return void
   * @throws Exception
   */
  public function tryUpgradeV3(ApiTester $I)
  {
    $upgrade_from = $I->grabCurrentAppVersion();
    $upgrade_to = '3.0.0';
    $I->sendJsonRpcRequest('setup','setup-version',[$upgrade_to]);
    $I->seeServerEvent("bibliograph.setup.done");
    $I->seeResponseContains("Found data for version '. $upgrade_from .' and applied new migrations for version ' . $upgrade_to . '");
  }
}
