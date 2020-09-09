/**
 * @require(qx.io.transport.Http)
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
    
    async "test: logout to destroy existing session"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.logout();
      this.assertEquals(null, this.client.getToken(), "Client token should be null");
    },
  
    async "test: try to access method without authentication - should fail"() {
      try {
        // eslint-disable-next-line no-caller
        this.showTestNameInRequest(arguments.callee.name);
        await this.client.request("access.username");
        throw new Error("Unauthenticated access should throw");
      } catch (e) {
        this.assertInstance(e, qx.io.exception.Protocol);
        this.assertEquals("Unauthorized: Your request was made with invalid credentials.", e.message);
      }
    },
    
    async "test: log in anonymously and get username"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.loginAnonymously();
      let username = await this.client.request("access.username");
      console.log(`Username is ${username}.`);
      this.assertString(username);
    },
  
    async "test: log in anonymously and test persistence"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.loginAnonymously();
      let counter;
      for (let i=1; i<5; i++) {
        let c = await this.client.request("test.count");
        this.assertEquals(counter || c, c);
        counter = c+1;
      }
    },
  
  
    async "test: authenticate with Password and get userdata"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.loginWithPassword("admin", "admin");
      let result = await this.client.request("access.userdata");
      this.assertEquals("admin", result.namedId);
      this.assertInArray("*", result.permissions);
    },

    async "test: authenticate with Password and test persistence"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.loginWithPassword("admin", "admin");
      let counter;
      for (let i=1; i<5; i++) {
        let c = await this.client.request("test.count");
        this.assertEquals(counter || c, c);
        counter = c+1;
      }
    },
    
    eof() {}
  }
});
