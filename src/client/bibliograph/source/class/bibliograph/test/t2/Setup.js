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
      this.bus = qx.event.message.Bus.getInstance();
    },
  
    tearDown() {
      this.disposeClient();
      this.bus.removeAllSubscriptions();
      this.bus.dispose();
    },
    
    callSetupMethod() {
      let msg_next = 0;
      let msg_done = false;
      this.bus.subscribe("bibliograph.setup.next", async () => {
        msg_next++;
        console.log(`>>> Received message 'bibliograph.setup.next' ${msg_next} times.`);
        let result = await this.client.request("setup.setup");
        console.log(`>>> Received result '${result}'.`);
      });
      this.bus.subscribeOnce("bibliograph.setup.done", e => {
        console.log(">>> Setup messages:\n" + e.getData().join("\n"));
        msg_done = true;
        this.resume();
      });
      const promise = this.client.request("setup.setup")
        .then(result => console.log(`>>> Received result '${result}'.`));
      console.log(">>> Calling wait()");
      this.wait(30000, () => {
        this.assertTrue(msg_done, "Message \"bibliograph.setup.done\" was not received.");
      });
      return promise;
    },
    
    async "test: get version"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      let version = await this.client.request("setup.version");
      this.assertNotEquals("", version);
    },
    
    async "test: reset"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.client.request("setup.reset");
    },
    
    async "test: call setup method for the first time"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.callSetupMethod();
    },
  
    async "test: call setup method for the second time"() {
      // eslint-disable-next-line no-caller
      this.showTestNameInRequest(arguments.callee.name);
      await this.callSetupMethod();
    },
    
    eof() {}
  }
});
