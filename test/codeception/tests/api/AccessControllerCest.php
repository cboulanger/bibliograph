<?php

class AccessControllerCest
{

  public function tryToAccessMethodWithoutAuthentication(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->clearToken();
    $I->sendJsonRpcRequest("access","username", [], "all");
    $I->seeJsonRpcError("Unauthorized");
  }

  public function tryToLoginAnonymously(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginAnonymously();
  }

  public function tryAuthenticateWithPassword(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->loginWithPassword('admin','admin');
    $I->sendJsonRpcRequest('access','username');
    $I->assertSame( $I->grabJsonRpcResult(), "admin" );
    // test session persistence
    $I->sendJsonRpcRequest('access','count');
    $counter = $I->grabJsonRpcResult();
    for ($i=1; $i < 4; $i++) {
      $I->sendJsonRpcRequest('access','count');
      $I->assertSame( $I->grabJsonRpcResult(), $counter+$i );
    }
    $I->sendJsonRpcRequest('access','userdata');
    //codecept_debug($I->grabRequestedResponseByJsonPath('$.result'));
    $namedId = $I->grabRequestedResponseByJsonPath('$.result.namedId')[0];
    $I->assertSame( 'admin', $namedId );
    $permissions = $I->grabRequestedResponseByJsonPath('$.result.permissions')[0];
    $I->assertTrue(in_array("*", $permissions), "Permissions do not contain '*'");
    $I->logout();
  }

  /**
   * This test uses the public LDAP test server at ldap.forumsys.com
   * @see https://www.forumsys.com/tutorials/integration-how-to/ldap/online-ldap-test-server/
   *
   * @param ApiTester $I
   * @env ldap
   */
  public function tryAuthenticateViaLdap(ApiTester $I, \Codeception\Scenario $scenario)
  {
    $I->enableLdapAuthentication();
    $I->sendJsonRpcRequest('access','ldap-support');
    //codecept_debug( $I->grabRequestedResponseByJsonPath('$.result')[0] );
    if( ! $I->grabRequestedResponseByJsonPath('$.result.connection')[0] ){
      $scenario->skip("We don't have an LDAP connection");
    }
    $I->loginWithPassword('einstein','password');
    $I->sendJsonRpcRequest('access','userdata');
    $I->assertSame( $I->grabRequestedResponseByJsonPath('$.result.namedId')[0], 'einstein' );
    $I->assertSame( count( $I->grabRequestedResponseByJsonPath('$.result.permissions')[0] ), 14 );
    $I->logout();
    $I->disableLdapAuthentication();
  }
}
