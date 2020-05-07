/**
 * @require(qx.io.jsonrpc.transport.Http)
 * @require(qcl.io.jsonrpc.MessageBus)
 */
qx.Class.define("bibliograph.test.t2.Setup", {
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
    
    async callSetupMethod() {
      let msg_next = 0;
      let msg_done = false;
      const bus = qx.event.message.Bus;
      bus.subscribe("bibliograph.setup.next", async () => {
        msg_next++;
        console.log(`>>> Received message 'bibliograph.setup.next' ${msg_next} times.`);
        let result = await this.client.request("setup.setup");
        console.log(`>>> Received result '${result}'.`);
      });
      bus.subscribe("bibliograph.setup.error", e => {
        throw new Error("Setup errors!" + e.getData());
      });
      bus.subscribe("bibliograph.setup.done", e => {
        console.log(">>> Setup messages:\n" + e.getData().join("\n"));
        msg_done = true;
        bus.unsubscribe("bibliograph.*");
        this.resume();
      });
      this.client.request("setup.setup").then(result => console.log(`>>> Received result '${result}'.`));
      this.wait(30000, () => {
        this.assertTrue(msg_done, "Message \"bibliograph.setup.done\" was not received.");
      });
    },
    
    async "test: get version"() {
      let version = await this.client.request("setup.version");
      this.assertNotEquals("", version);
    },
    
    async "test: reset"() {
      await this.client.request("setup.reset");
    },
    
    async "test: call setup method for the first time"() {
      await this.callSetupMethod();
    },
  
    async "test: call setup method for the second time"() {
      await this.callSetupMethod();
    },
    
    eof() {}
  }
});
