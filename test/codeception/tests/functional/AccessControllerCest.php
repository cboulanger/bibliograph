<?php

class AccessControllerCest
{

  public function _fixtures()
  {
    return require APP_TESTS_DIR . '/tests/fixtures/_access_models.php';
  }

  /**
   * @param FunctionalTester $I
   * @throws Exception
   */
  public function tryToAccessMethodWithoutAuthentication(FunctionalTester $I)
  {
    $I->sendJsonRpcRequest("access","username");
    // @todo we don't get a proper error yet, so we have to check that the result is null
    $I->assertSame( [null], $I->grabDataFromResponseByJsonPath('$.result'));
  }

  public function tryToLoginAnonymously(FunctionalTester $I)
  {
    $I->loginAnonymously();
  }

  /**
   * @param FunctionalTester $I
   * @throws Exception
   */
  public function tryToAuthenticateAdminWithPassword(FunctionalTester $I)
  {
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('access','username');
    $I->assertSame( $I->grabJsonRpcResult(), "admin" );
    // test session persistence
    for ($i=1; $i < 4; $i++) {
      $I->sendJsonRpcRequest('access','count');
      $I->assertSame( $I->grabJsonRpcResult(), $i );
    }
    $I->sendJsonRpcRequest('access','userdata');
    //codecept_debug($I->grabDataFromResponseByJsonPath('$.result'));
    $I->assertSame('admin', $I->grabDataFromResponseByJsonPath('$.result.namedId')[0] );
    $I->assertSame(1, count( $I->grabDataFromResponseByJsonPath('$.result.permissions')[0] ));
    $I->logout();
  }

  public function testDatasourcePermissions(FunctionalTester $I)
  {
    $I->amGoingTo("login as Sarah.");
    $I->loginWithPassword('sarah_manning','sarah_manning');
    $I->amGoingTo("update permissions for database1 and expect those for normal user.");
    $I->sendJsonRpcRequest('access', 'update-permissions', ['database1']);
    $I->assertEquals(14, count($I->grabRpcData()));
    $I->amGoingTo("update permissions for database2 and expect those for manager.");
    $I->sendJsonRpcRequest('access', 'update-permissions', ['database2']);
    $I->assertEquals(25, count($I->grabRpcData()));
    $I->logout();

    $I->amGoingTo("login as Jessica.");
    $I->loginWithPassword('jessica_jones','jessica_jones');
    $I->amGoingTo("update permissions for database1 and expect those for normal user.");
    $I->sendJsonRpcRequest('access', 'update-permissions', ['database1']);
    $I->assertEquals(14, count($I->grabRpcData()));
    $I->amGoingTo("update permissions for database3 and expect to be denied access.");
    $I->sendJsonRpcRequest('access', 'update-permissions', ['database3'], true);
    $I->seeUserError();
    $I->amGoingTo("update permissions for user database and expect those for manager.");
    $I->sendJsonRpcRequest('access', 'update-permissions', ['jessica']);
    $I->assertEquals(25, count($I->grabRpcData()));
    $I->logout();

    $I->amGoingTo("login as Frank.");
    $I->loginWithPassword('frank_underwood','frank_underwood');
    $I->amGoingTo("update permissions for database1 and expect those for manager (only).");
    $I->sendJsonRpcRequest('access', 'update-permissions', ['database1']);
    $I->assertEquals(12, count($I->grabRpcData()));
    $I->amGoingTo("update permissions for jessica's database and expect access denied.");
    $I->sendJsonRpcRequest('access', 'update-permissions', ['jessica'], true);
    $I->seeUserError();
    $I->logout();

  }

}
