/**
 * @require(qx.io.jsonrpc.transport.Http)
 */
qx.Class.define("bibliograph.test.JsonRpc", {
  extend: qx.dev.unit.TestCase,
  members: {
    /**
     * @var {qcl.io.jsonrpc.Client}
     */
    __client: null,
    
    setUp () {
      let url =`${location.protocol}//${location.host}/${qx.core.Environment.get("app.serverUrl")}/json-rpc`;
      this.__client = new qcl.io.jsonrpc.Client(url);
    },
    
    tearDown() {
      this.__client.dispose();
    },
    
    /**
     * Here are some simple tests
     */
    async testEcho() {
      let result = await this.__client.request("test.echo", ["foo"]);
      this.assertEquals("foo", result);
    },
    
    /**
     * Here are some more advanced tests
     */
    testAdvanced: function () {
      var a = 3;
      var b = a;
      this.assertIdentical(a, b, "A rose by any other name is still a rose");
      this.assertInRange(3, 1, 10, "You must be kidding, 3 can never be outside [1,10]!");
    }
    
  }
});
