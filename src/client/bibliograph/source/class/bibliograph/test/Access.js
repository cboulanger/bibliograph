/**
 * @require(qx.io.jsonrpc.transport.Http)
 */
qx.Class.define("bibliograph.test.Access", {
  extend: qx.dev.unit.TestCase,
  include: [
    qx.test.io.jsonrpc.MAssert
  ],
  members: {
    /**
     * @var {qcl.io.jsonrpc.Client}
     */
    __client: null,
    
    setUp () {
      let url =`${location.protocol}//${location.host}/${qx.core.Environment.get("app.serverUrl")}/json-rpc`;
      this.__client = new qcl.io.jsonrpc.Client(url, "access");
      this.__client.setErrorBehavior("debug");
    },
    
    tearDown() {
      this.__client.dispose();
    },
    
    async loginAnonymously() {
      let result = await this.__client.request("authenticate", []);
      this.assertString(result.token);
      this.assertNotEquals("", result.token);
      this.__client.setToken(result.token);
    },
    
    async "test: try to access method without authentication - should fail"() {
      try {
        await this.__client.request("username", []);
        throw new Error("Unauthenticated access should throw");
      } catch (e) {
        this.assertInstance(e, qx.io.jsonrpc.exception.JsonRpc);
        this.assertEquals("Unauthorized: Your request was made with invalid credentials.", e.message);
      }
    },
    
    async "test: log in anonymously and get an authentication token"() {
      await this.loginAnonymously();
    },
    
/*
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
 */
    
    eof() {}
  }
});
