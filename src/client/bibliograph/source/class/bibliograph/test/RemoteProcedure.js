qx.Class.define("bibliograph.test.RemoteProcedure", {
  extend: qx.core.Object,
  type: "singleton",
  include: [qcl.io.jsonrpc.MRemoteProcedure],
  members: {
    receiveNotification: function(data) {
      console.log(`notification method was called with param ${data}`);
      bibliograph.test.notification_received = data;
    }
  }
});
