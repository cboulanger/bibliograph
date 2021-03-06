/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright:
    2003-2020 Christian Boulanger

  License:
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors:
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * A wrapper for a JSONRPC 2.0 client implementation
 * @require(qx.io.transport.Xhr)
 */
qx.Class.define("qcl.io.jsonrpc.Client", {
  extend: qx.core.Object,
  include: [qx.locale.MTranslation],

  /**
   * Create a new instance
   * @param {String} url  The url of the endpoint of the JSON-RPC server
   * @param {String?} service Optional service name which is prepended to the method
   */
  construct: function(url, service) {
    this.__dialog = qxl.dialog.Dialog.error("").hide();
    qx.util.Validate.checkUrl(url);
    let methodPrefix;
    if (service) {
      // the service, can contain "." or "/" as path separators
      this.__service = service.replace(/\//g, ".").replace(/\.$/, "");
      methodPrefix = this.__service + ".";
    }
    const client = this.__client = new qx.io.jsonrpc.Client(url, methodPrefix);
    client.addListener("outgoingRequest", this._configueTransport, this);
    client.addListener("incomingRequest", this._handleIncomingRequest, this);
    qx.event.message.Bus.subscribe("qcl.token.change", e => {
      this.setToken(e.getData() || null);
    });
  },

  properties: {

    /**
     * If the last request has resulted in an error, it is stored here.
     * The error object can be anything, but it has have the native properties `message` and `code`
     */
    error: {
      nullable: true,
      check: "Object",
      event: "changeError"
    },

    /**
     * Set authentication token
     * */
    token: {
      nullable: true,
      init: null,
      check: "String",
      event: "changeToken"
    },

    /**
     * The last response returned by the server
     */
    response: {
      nullable: true,
      event: "changeResponse"
    },
  
    /**
     * How to behave when an error occurs.
     * "error": throw a runtime error which can be handled,
     * "debug": like error, but output additional information about the error to the console,
     * "dialog": show error to the user in a dialog
     * "warning": simply output a warning to the console
     */
    errorBehavior: {
      check: ["error", "dialog", "debug", "warning"],
      init: "error"
    },
  
    /**
     * A map of additional query string parameters to send with the request
     */
    queryParams: {
      check: "Object",
      nullable: true
    },
  
    /**
     * An optional function which is called with the error object. If it returns
     * an error object (usually the same that was passed in), this error is
     * handled according to {@link #setErrorBehavior}. If the function returns
     * false, the error is ignored.
     */
    handleErrorFunc: {
      check: "Function",
      nullable: true
    }
  },

  events: {
    /** Fired when something happens */
    changeSituation: "qx.event.type.Data",
    
    /** Fired when an error occurs */
    error: "qx.event.type.Data"
  },

  members: {
    /**
     * @var {qx.io.jsonrpc.Client}
     */
    __client: null,
  
    /**
     * @var {dialog.Dialog}
     */
    __dialog: null,
  
    /** The url template string */
    __url: null,
    
    /** the name of the service */
    __service: null,
  
    /*
    ---------------------------------------------------------------------------
     API
    ---------------------------------------------------------------------------
    */
  
    /**
     * Return the name of the service
     * @return {String}
     */
    getService() {
      return this.__service;
    },
  
    /**
     * Sends a jsonrpc request to the server. An error will be caught
     * and handled by {@link #_handleJsonRpcError}.
     * @param method {String} The service method
     * @param params {Array} The parameters of the method
     * @return {Promise<*>}
     */
    async request(method, params = []) {
      qx.core.Assert.assertArray(params);
      this.setError(null);
      let result;
      let task;
      let app = qx.core.Init.getApplication();
      if (qx.core.Environment.get("app.taskmonitor.enable")) {
        if (app.getTaskMonitor) {
          task = new qxl.taskmanager.Task(`JSON-RPC request for '${this.__service}.${method}'`, params);
          app.getTaskMonitor().add(task);
        }
      }
      try {
        result = await this.__client.sendRequest(method, params);
        this.setResponse(result);
      } catch (e) {
        let err = e;
        if (e instanceof qx.io.exception.Transport &&
                e.code === qx.io.exception.Transport.INVALID_MSG_DATA &&
                  qx.lang.Type.isObject(e.data.message) &&
                    "message" in e.data.message && "code" in e.data.message) {
          err = e.data.message;
        } else if (e instanceof qx.io.exception.Protocol) {
          try {
            err.message = `${e.message}: ${e.data.response.error.data.human_message}`;
          } catch (e) {}
        }
        this.setError(err);
        this._handleJsonRpcError(method);
        return null;
      } finally {
        if (qx.core.Environment.get("app.taskmonitor.enable")) {
          if (app.getTaskMonitor) {
            app.getTaskMonitor().remove(task).dispose();
          }
        }
      }
      return result;
    },
  
    /**
     * Backward-compatibility
     * @deprecated Use request() instead
     */
    async send() {
      return this.request.apply(this, arguments);
    },
  
    /**
     * Sends a jsonrpc notification to the server. An error will be caught
     * and displayed in a dialog. In this case, the returned promise
     * resolves to null
     * @param method {String} The service method
     * @param params {Array} The parameters of the method
     * @return {Promise<void>}
     */
    async notify(method, params = []) {
      qx.core.Assert.assertArray(params);
      this.setError(null);
      try {
        await this.__client.sendNotification(method, params);
      } catch (e) {
        this.setError(e);
        this._handleJsonRpcError(method);
      }
    },
  
    /**
     * Returns a descriptive message of the last error, if available, truncated to the first 100 characters
     * @return {String}
     */
    getErrorMessage () {
      let e = this.getError();
      if (!e) {
        return "Unknown error";
      }
      if (typeof e.message == "string") {
        // shorten message
        return e.message.substring(0, 100);
      }
      return "Unknown Error";
    },
  
    /**
     * Returns the actual jsonrpc client (this being just a wrapper class)
     * @return {qx.io.jsonrpc.Client}
     */
    getClientImpl() {
      return this.__client;
    },
  
    /*
    ---------------------------------------------------------------------------
     INTERNAL METHODS
    ---------------------------------------------------------------------------
    */
    
    /**
     * configure the transport object before the request is sent
     * @private
     */
    _configueTransport() {
      let token = this.getToken();
      let queryParams = this.getQueryParams() || {};
      let transportImpl = this.__client.getTransport().getTransportImpl();
      if (token) {
        if (qx.core.Environment.get("qcl.io.jsonrpc.token-in-params")) {
          queryParams["access-token"] = token;
        }
        const auth = new qx.io.request.authentication.Bearer(token);
        transportImpl.setAuthentication(auth);
      }
      if (Object.getOwnPropertyNames(queryParams).length) {
        let parsedUri = qx.util.Uri.parseUri(transportImpl.getUrl());
        let existingParams = parsedUri.queryKey;
        let url = [
          parsedUri.protocol,
          "://",
          parsedUri.authority,
          parsedUri.path,
          "?",
          qx.util.Uri.toParameter(Object.assign(existingParams, queryParams))
        ].join("");
        transportImpl.setUrl(url);
      }
    },
    
    /**
     * Handle a message from the server: The method name is split into
     * the name of a singleton class in the bibliograph.jsonrpc namespace
     * @param evt
     * @private
     */
    _handleIncomingRequest (evt) {
      let message = evt.getData();
      if (message instanceof qx.io.jsonrpc.protocol.Notification) {
        let parts = message.getMethod().split(".");
        let method = parts.pop();
        let classname = parts.join(".");
        let clazz = qx.Class.getByName(classname);
        if (!clazz) {
          this.error(`Server notification invokes class '${classname}', which does not exist.`);
        } else if (typeof clazz.getInstance != "function" ||
          !qx.Class.hasMixin(clazz, qcl.io.jsonrpc.MRemoteProcedure)) {
          throw new Error(`Server notification invokes class ${classname}, which does not include qcl.io.jsonrpc.MAbstractProcedure and/or is not a singleton.`);
        } else {
          let instance;
          try {
            instance = clazz.getInstance();
          } catch (e) {
            throw new Error(`'${classname}' is not a singleton class.`);
          }
          if (typeof instance[method] != "function") {
            throw new Error(`Server notification invokes non-existing method '${method}' of singleton class '${classname}'.`);
          }
          let params = message.getParams();
          if (params) {
            if (Array.isArray(params)) {
              // call the method with the given arguments
              instance[method].apply(instance, params);
            } else {
              throw new Error(`Invalid parameters type - must be array, is ${typeof params}.`);
            }
          } else {
            // call the method
            instance[method]();
          }
        }
      } else {
        throw new Error("Incoming JSON-RPC message object must be instance of qx.io.jsonrpc.protocol.Notification.");
      }
    },
    
    /**
     * Handles an JSON_RPC error as configured by the errorBehavior property
     * @param method
     * @private
     */
    _handleJsonRpcError: function (method) {
      let error = this.getError();
      if (this.getHandleErrorFunc()) {
        error = this.getHandleErrorFunc()(error);
        if (error instanceof Error) {
          this.setError(error);
        } else if (error === false) {
          // ignore error
          return;
        } else {
          throw new Error("Invalid return value from error handling function. Must be an instance of Error or false");
        }
      }
      let message = qx.core.Environment.select("qx.debug", {
        "true": this.tr("Error calling remote method '%1': %2.", method, this.getErrorMessage()),
        "false": this.getErrorMessage()
      });
      // inform message subscribers
      qx.event.message.Bus.dispatchByName("jsonrpc.error", error);
      // inform event listeners
      this.fireDataEvent("error", error);
      // log to console for UI test runners, TO DO this should be done by emnvironment variable
      this.error("JsonRpcError: " + message);
      // log to console
      if (qx.core.Environment.get("qx.debug")) {
        try {
          let exception = error.data.response.error.data.exception;
          while (exception.previous) {
            exception = exception.previous.data.exception;
          }
          console.error(JSON.stringify(exception, null, 2));
        } catch (e) {
          console.error(JSON.stringify(error, null, 2));
        }
      }
      // handle error
      switch (this.getErrorBehavior()) {
        case "error":
          throw error;
        case "warning":
          this.warn(error);
          break;
        case "dialog": {
          qcl.ui.dialog.ServerDialog.getInstance().hideServerDialogs();
          this.__dialog.set({message}).show();
        }
      }
    },
  
    /**
     * Shows error dialog when authentication failed
     * @param method
     * @private
     */
    _showAuthErrorMessageAndLogOut: function (method) {
      let app = qx.core.Init.getApplication();
      if (app.__authErrorDialog) {
        this.error(`Authentication failed for method '${method}.'`);
        return;
      }
      let msg =
            app.tr("A login problem occurred, which is usually due to a database upgrade. Press 'OK' to reload the application.") + " " +
            app.tr("If the error persists, contact the administrator.");
      app.__authErrorDialog = qxl.dialog.Dialog.error(msg);
      app.__authErrorDialog.promise()
        .then(() =>
          app.getAccessManager().logout()
            .then(() => window.location.reload())
            .catch(() =>
              app.getAccessManager().logout()
                .then(() => window.location.reload())
            )
        );
      this.warn(`Authentication failed for method '${method}.'`);
    }
  },
  /**
   * Destructor
   */
  destruct: function() {
    this.__client.dispose();
    this.__dialog.hide().dispose();
  }
});
