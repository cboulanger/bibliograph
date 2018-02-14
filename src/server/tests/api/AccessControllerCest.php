<?php

class AccessControllerCest
{
  /**
   * This test uses the public LDAP test server at ldap.forumsys.com
   * @see https://www.forumsys.com/tutorials/integration-how-to/ldap/online-ldap-test-server/
   *
   * @param ApiTester $I
   * @return void
   */
  public function tryAuthenticateViaLdap(ApiTester $I, $scenario)
  {
    $I->sendJsonRpcRequest('access','ldap-support');
    //codecept_debug( $I->grabDataFromResponseByJsonPath('$.result')[0] );
    if( ! $I->grabDataFromResponseByJsonPath('$.result.connection')[0] ){
      $scenario->skip("We don't have an LDAP connection");
    }
    
    $I->loginWithPassword('einstein','password');
    //$I->sendJsonRpcRequest('access','userdata');
    
    // $I->assertSame( $I->grabDataFromResponseByJsonPath('$.result.namedId')[0], 'admin' );
    // $I->assertSame( count( $I->grabDataFromResponseByJsonPath('$.result.permissions')[0] ), 34 );
    // $I->logout();
  }
}
