/**
 * @require(qx.io.jsonrpc.transport.Http)
 * @require(qcl.io.jsonrpc.MessageBus)
 */
qx.Class.define("bibliograph.test.t2.Setup", {
  extend: qx.dev.unit.TestCase,
  include: [
    qx.test.io.jsonrpc.MAssert
  ],
  members: {
    /**
     * @var {qcl.io.jsonrpc.Client}
     */
    client: null,
    
    setUp () {
      let url =`${location.protocol}//${location.host}/${qx.core.Environment.get("app.serverUrl")}/json-rpc`;
      this.client = new qcl.io.jsonrpc.Client(url);
      this.client.setErrorBehavior("debug");
    },
    
    tearDown() {
      this.client.dispose();
    },
    
    //
    // TESTS
    //
  
    async "test: get version"() {
      let version = await this.client.request("setup.version");
      this.assertNotEquals("", version);
    },
    
    async "test: call setup method"() {
      let called = false;
      qx.event.message.Bus.subscribe("bibliograph.setup.done", () => called = true);
      let result = await this.client.request("setup.setup");
      this.assertArrayEquals([], result.errors);
      this.assertTrue(called, "Message \"bibliograph.setup.done\" was not dispatched.");
    },
    
    eof() {}
  }
});
