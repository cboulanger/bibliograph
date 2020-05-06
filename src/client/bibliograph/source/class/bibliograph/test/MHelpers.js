/**
 * Mixin with helper methods for the tests
 */
qx.Mixin.define("bibliograph.test.MHelpers",{
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
    
    async loginAnonymously() {
      let result = await this.client.request("access.authenticate");
      this.assertString(result.token);
      this.assertNotEquals("", result.token);
      this.client.setToken(result.token);
    },
  
    async loginWithPassword(username, password) {
      let result = await this.client.request("access.authenticate", [username, password]);
      this.assertString(result.token);
      this.assertNotEquals("", result.token);
      this.client.setToken(result.token);
    }
  }
});
