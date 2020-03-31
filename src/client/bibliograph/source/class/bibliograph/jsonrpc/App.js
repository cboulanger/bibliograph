qx.Class.define("bibliograph.jsonrpc.App", {
  extend: qcl.io.jsonrpc.RemoteProcedure,
  type: "Singleton",
  members: {
    dispatchMessage(name, data) {
      console.debug(`Received '${name}' message from server with data ${qx.lang.Json.stringify(data)}`);
      qx.event.message.Bus.dispatchByName(name, data);
    }
  }
});
