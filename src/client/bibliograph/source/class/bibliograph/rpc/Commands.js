qx.Class.define("bibliograph.rpc.Commands", {
  extend: qx.core.Object,
  include:  [qcl.io.jsonrpc.MRemoteProcedure],
  type: "singleton",
  members: {
    reload(resetState) {
      if (resetState) {
        location.href = location.protocol + "//" + location.host + location.pathname;
      }
      location.reload(true);
    }
  }
});
