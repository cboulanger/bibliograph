/**
 * Mixin with helper methods for the tests
 */
qx.Mixin.define("bibliograph.test.MHelpers", {
  members: {
    /**
     * @var {qcl.io.jsonrpc.Client}
     */
    client: null,
    
    createClient(service="") {
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
    
    setNewToken(token) {
      let previousToken = this.client.getToken();
      this.assertTrue(token !== "" && typeof token == "string", "Received token is invalid");
      this.assertNotEquals(previousToken, token, "Failed to assert that returned token is different from the previous one.");
      this.client.setToken(token);
    },
  
    async logout() {
      await this.client.request("access.logout");
      this.assertEquals(null, this.client.getToken(), "Failed to assert that client token is null after logout.");
    },
    
    async loginAnonymously() {
      let result = await this.client.request("access.authenticate");
      this.setNewToken(result.token);
    },
  
    async loginWithPassword(username, password) {
      let result = await this.client.request("access.authenticate", [username, password]);
      this.setNewToken(result.token);
    }
  }
});
