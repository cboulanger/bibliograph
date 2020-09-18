/**
 * @require(qx.io.transport.Http)
 */
qx.Class.define("bibliograph.test.services.Access", {
  extend: qx.dev.unit.TestCase,
  include: [
    qx.test.io.MAssert,
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
    },
  
    async "test: try to access method without authentication - should fail"() {
      try {
        // eslint-disable-next-line no-caller
        this.showTestNameInRequest(arguments.callee.name);
        await this.client.request("access.username");
        throw new Error("Unauthenticated access should throw");
      } catch (e) {
        this.assertInstance(e, qx.io.exception.Protocol);
        this.assertContains("Unauthorized", e.message);
      }
    },
    
    async "test: log in anonymously and get username"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.loginAnonymously();
      let username = await this.client.request("access.username");
      console.log(`Username is ${username}.`);
      this.assertString(username);
      await this.logout();
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
      await this.logout();
    },
  
  
    async "test: authenticate with Password and get userdata"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.loginWithPassword("admin", "admin");
      let result = await this.client.request("access.userdata");
      this.assertEquals("admin", result.namedId);
      this.assertInArray("*", result.permissions);
      await this.logout();
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
      await this.logout();
    },
    
    eof() {}
  }
});
