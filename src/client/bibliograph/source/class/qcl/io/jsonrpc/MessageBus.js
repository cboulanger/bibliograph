qx.Class.define("qcl.io.jsonrpc.MessageBus", {
  extend: qx.core.Object,
  include: [qcl.io.jsonrpc.MRemoteProcedure],
  type: "singleton",
  members: {
    dispatch(message) {
      let {name, data} = message;
      console.debug(`Received '${name}' message from server with data ${qx.lang.Json.stringify(data)}`);
      qx.event.message.Bus.dispatchByName(name, data);
    }
  }
});
