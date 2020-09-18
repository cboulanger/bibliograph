/**
 * @require(qx.io.transport.Http)
 * @require(qx.test.io.jsonrpc.MAssert)
 * @ignore(bibliograph.test.notification_received)
 */
qx.Class.define("bibliograph.test.jsonrpc.JsonRpc", {
  extend: qx.dev.unit.TestCase,
  include: [
    qx.test.io.MAssert,
    bibliograph.test.MHelpers
  ],
  members: {
  
    setUp () {
      this.createClient("json-rpc-test");
    },
  
    tearDown() {
      this.disposeClient();
    },
    
    async "test: receive a notification from the server"() {
      let value = Math.PI;
      await this.client.request("notify-me", [value]);
      this.wait(100, () => {
        this.assertEquals(value, bibliograph.test.notification_received);
      });
    },
    
    async "test: echo an Array value"() {
      let value = [1, 2, 3];
      let result = await this.client.request("echo-array", [value]);
      this.assertDeepEquals(value, result);
    },
    
    async "test: echo a String value"() {
      let value = "foo";
      let result = await this.client.request("echo", [value]);
      this.assertEquals(value, result);
    },
    
    async "test: echo an Integer value"() {
      let value = 1;
      let result = await this.client.request("echo", [value]);
      this.assertEquals(value, result);
    },
    
    async "test: echo a Float value"() {
      let value = Math.PI;
      let result = await this.client.request("echo", [value]);
      this.assertEquals(value, result);
    },
    
    async "test: echo an Object value"() {
      let value = {"a": "foo", b: 1, c: [1, 2, 3]};
      let result = await this.client.request("echo", [value]);
      this.assertDeepEquals(value, result);
    },
    
    eof() {}
  }
});
