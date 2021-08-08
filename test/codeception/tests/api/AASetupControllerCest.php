<?php

class AASetupControllerCest
{

  protected $version;

  /**
   * @param ApiTester $I
   */
  public function tryTestSuitePhpVersion(ApiTester $I)
  {
    $expected_php_version = $_SERVER['PHP_VERSION'];
    $I->expectTo("find PHP version $expected_php_version");
    $I->comment(json_encode($_SERVER, JSON_PRETTY_PRINT));
    $actual_php_version = PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;
    $I->assertTrue($expected_php_version === $actual_php_version, "Wrong PHP version $actual_php_version, expected $expected_php_version");
  }

  /**
   * @param ApiTester $I
   */
  public function tryServerPhpVersion(ApiTester $I)
  {
    $expected_php_version = $_SERVER['PHP_VERSION'];
    $expected_port = "80" . str_replace(".", "", $expected_php_version);
    $I->expectTo("find port $expected_port in URLs");
    $I->assertStringContainsString($expected_port, $_SERVER['APP_URL'], "APP_URL contains wrong port");
    $I->assertStringContainsString($expected_port, $_SERVER['SERVER_URL'], "SERVER_URL contains wrong port");
    $I->amGoingTo("query the 'setup.php-version' server method");
    $I->sendJsonRpcRequest('setup','php-version');
    $actual_php_server_version = $I->grabJsonRpcResult();
    $I->expectTo("find PHP version $expected_php_version");
    $I->assertTrue($expected_php_version === $actual_php_server_version,"Wrong PHP version $actual_php_server_version on the server, expected $expected_php_version");
  }

  /**
   * Getting application version
   *
   * @param ApiTester $I
   * @return void
   * @throws Exception
   */
  public function tryAppVersion(ApiTester $I)
  {
    $I->amGoingTo("test the 'setup.version' server method");
    $I->sendJsonRpcRequest('setup','version');
    $this->version = $I->grabJsonRpcResult();
    $I->amGoingTo("see if '$this->version' is a valid version number");
    $I->assertTrue( version_compare( $this->version, '0.0.1', '>' ), "Result should be a valid version number" );
  }

  /**
   * @param ApiTester $I
   */
  protected function runSetup($I) {
    $I->amGoingTo("Setup the application");
    $done = false;
    do {
      $I->sendJsonRpcRequest('setup','setup');
      try {
        $I->seeServerEvent("bibliograph.setup.next");
      } catch (\PHPUnit\Framework\AssertionFailedError $e) {
        $I->seeServerEvent("bibliograph.setup.done");
        $done = true;
      }

    } while (!$done);
  }

  /**
   * @param ApiTester $I
   */
  public function tryReset(ApiTester $I) {
    $I->amGoingTo("Reset the setup cache");
    $I->sendJsonRpcRequest('setup','reset');
  }

  /**
   * This calls the setup action with the migrations already applied.
   *
   * @param ApiTester $I
   * @env with-data
   * @return void
   * @throws Exception
   */
  public function tryNormalSetup(ApiTester $I)
  {
    $this->runSetup($I);
  }

  /**
   * This calls the setup action with an empty database and no migrations applied.
   * @param ApiTester $I
   * @env empty-database
   * @return void
   * @throws Exception
   */
  public function trySetupWithEmptyDatabase(ApiTester $I)
  {
    $this->runSetup($I);
    $I->seeResponseContains("Found empty database and applied new migrations for version $this->version");
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
    $this->runSetup($I);
    $I->seeResponseContains("Migrated data from Bibliograph v2 and applied new migrations for version $this->version");
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
    $this->runSetup($I);
    $I->seeResponseContains("Found data for version $upgrade_from and applied new migrations for version $upgrade_to");
  }
}
