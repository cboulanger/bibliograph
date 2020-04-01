/**
 * @require(qx.io.jsonrpc.transport.Http)
 */
qx.Class.define("bibliograph.test.JsonRpc", {
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
      this.__client = new qcl.io.jsonrpc.Client(url, "json-rpc-test");
    },
    
    tearDown() {
      this.__client.dispose();
    },
  
    async "test: receive a notification from the server"() {
      let value = Math.PI;
      await this.__client.request("notify-me", [value]);
      this.wait(100, () => {
        this.assertEquals(value, bibliograph.test.notification_received);
      });
    },
    
    async "test: echo an Array value"() {
      let value = [1, 2, 3];
      let result = await this.__client.request("echo-array", [value]);
      this.assertDeepEquals(value, result);
    },
    
    async "test: echo a String value"() {
      let value = "foo";
      let result = await this.__client.request("echo", [value]);
      this.assertEquals(value, result);
    },
    
    async "test: echo an Integer value"() {
      let value = 1;
      let result = await this.__client.request("echo", [value]);
      this.assertEquals(value, result);
    },
    
    async "test: echo a Float value"() {
      let value = Math.PI;
      let result = await this.__client.request("echo", [value]);
      this.assertEquals(value, result);
    },
    
    async "test: echo an Object value"() {
      let value = {"a": "foo", b: 1, c: [1, 2, 3]};
      let result = await this.__client.request("echo", [value]);
      this.assertDeepEquals(value, result);
    },
    
    eof() {}
  }
});
