qx.Mixin.define("qcl.io.jsonrpc.MClientCache", {
  
  members: {
    /**
     * Returns the URL to the JSONRPC server
     * TO DO: move into own protocol-indipendent mixin
     * @return {String}
     */
    getServerUrl: function() {
      // cache
      if (this.__url) {
        return this.__url;
      }
    
      let serverUrl = qx.core.Environment.get("app.serverUrl");
      if (!serverUrl) {
        this.getApplication().error(this.tr("Missing server address. Please contact administrator."));
        throw new Error("No server address set.");
      }
      if (!serverUrl.startsWith("http")) {
        // assume relative path
        serverUrl = qx.util.Uri.getAbsolute(serverUrl);
      }
      this.info("Server Url is " + serverUrl);
      this.__url = serverUrl;
      return serverUrl;
    },
  
    /**
     * Format parameters so that they are correctly appended to the Server URL
     * @param {Object} params The parameters map
     * @param {Boolean} addNoCache Whether to apped a string that prevents caching of the response (defaults to true)
     * @return {string}
     */
    formatParams(params, addNoCache=true) {
      let uriParams = qx.util.Uri.toParameter(params);
      let joinSymbol = this.getServerUrl().includes("?") ? "&" : "?";
      return joinSymbol + uriParams + (addNoCache ? `&nocache=${Math.random()}` : "");
    },
  
    /**
     * Returns a jsonrpc client object with the current auth token already set.
     * The client can be referred to by the object id "application/jsonrpc/<service name>"
     * @param {String} service The name of the service to get the client for
     * @return {qcl.io.jsonrpc.Client}
     */
    getRpcClient : function(service) {
      if (!this.__clients) {
        this.__clients = {};
      }
      qx.core.Assert.assert(Boolean(service), "Service parameter cannot be empty");
      qx.util.Validate.checkString(service, "Service parameter must be a string");
      if (!this.__clients[service]) {
        let client = new qcl.io.jsonrpc.Client(this.getServerUrl() + "/json-rpc", service);
        client.setErrorBehavior("dialog");
        client.setHandleErrorFunc(this.__handleErrorFunc.bind(this));
        this.__clients[service] = client;
      }
      let client = this.__clients[service];
      client.setToken(this.getAccessManager().getToken() || null);
      return client;
    },
  
    /**
     * Handle a situation where the token is invalid and we have several requests going
     * @param error
     * @return {boolean|*}
     * @private
     */
    __handleErrorFunc(error) {
      if (error.message === "Unauthorized") {
        if (!this.__loggingOut) {
          this.__loggingOut = true;
          this.getAccessManager().logout().then(() => {
            delete this.__loggingOut;
          });
        }
        return false;
      }
      return error;
    },
  
    /**
     * Returns a map, keys are the service names, values the corresponding
     * {@link qcl.io.jsonrpc.Client}.
     * @return {Object}
     */
    getRpcClients() {
      return this.__clients;
    }
  }
});
