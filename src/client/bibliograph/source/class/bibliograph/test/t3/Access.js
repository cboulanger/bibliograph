/**
 * @require(qx.io.jsonrpc.transport.Http)
 */
qx.Class.define("bibliograph.test.t3.Access", {
  extend: qx.dev.unit.TestCase,
  include: [
    qx.test.io.jsonrpc.MAssert,
    bibliograph.test.MHelpers
  ],
  members: {
  
    setUp () {
      this.createClient();
    },
  
    tearDown() {
      this.disposeClient();
    },
  
    async "test: try to access method without authentication - should fail"() {
      try {
        await this.client.request("access.username");
        throw new Error("Unauthenticated access should throw");
      } catch (e) {
        this.assertInstance(e, qx.io.jsonrpc.exception.JsonRpc);
        this.assertEquals("Unauthorized: Your request was made with invalid credentials.", e.message);
      }
    },
    
    async "test: log in anonymously and get username"() {
      await this.loginAnonymously();
      let username = await this.client.request("access.username");
      console.log(`Username is ${username}.`);
      this.assertString(username);
    },
  
    async "test: log in anonymously and test persistence"() {
      await this.loginAnonymously();
      for (let i=1; i<4; i++) {
        this.assertEquals(i, await this.client.request("access.count"));
      }
    },
    
    async "test: authenticate with Password"() {
      await this.loginWithPassword("admin", "admin");
      for (let i=1; i<4; i++) {
        this.assertEquals(i, await this.client.request("access.count"));
      }
      let result = await this.client.request("access.userdata");
      this.assertEquals("admin", result.namedId);
      this.assertInArray("*", result.permissions);
    },
    
    eof() {}
  }
});
