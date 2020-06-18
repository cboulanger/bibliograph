/**
 * Mixin with helper methods for the tests
 */
qx.Mixin.define("bibliograph.test.MHelpers", {
  members: {
    /**
     * @var {qcl.io.jsonrpc.Client}
     */
    client: null,
    
    createClient(service) {
      let url =`${location.protocol}//${location.host}/${qx.core.Environment.get("app.serverUrl")}/json-rpc`;
      this.client = new qcl.io.jsonrpc.Client(url, service);
      //this.client.setErrorBehavior("debug");
    },
  
    disposeClient() {
      this.client.dispose();
    },
  
    showTestNameInRequest(testName) {
      testName = testName.replace(/test: /, "").replace(/[^a-zA-Z0-9]/g, "-");
      this.client.setQueryParams({t: testName});
    },
    
    _validateToken(previous, received) {
      qx.lang.Type.isString(previous);
      this.assertTrue(received !== "" && typeof received == "string", "Received token is invalid");
      this.assertNotEquals(previous, received, "Returned token should be different from previous one but is not.");
      this.assertEquals(received, this.client.getToken(), "Returned token should be set to client via message.");
    },
  
    async logout() {
      let previousToken = this.client.getToken();
      await this.client.request("access.logout");
      this.assertEquals(null, this.client.getToken(), "Client token should be null.");
    },
    
    async loginAnonymously() {
      let previousToken = this.client.getToken();
      let result = await this.client.request("access.authenticate");
      this._validateToken(previousToken, result.token);
    },
  
    async loginWithPassword(username, password) {
      let previousToken = this.client.getToken();
      let result = await this.client.request("access.authenticate", [username, password]);
      this._validateToken(previousToken, result.token);
    }
  }
});
