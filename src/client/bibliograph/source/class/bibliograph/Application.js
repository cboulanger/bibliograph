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
 * This is the main application class of "Bibliograph"
 *
 * @asset(bibliograph/*)
 * @require(qcl.application.ClipboardManager)
 * @require(qcl.io.jsonrpc.MessageBus)
 * @require(qxl.dialog.Dialog)
 */
qx.Class.define("bibliograph.Application", {
  extend : qx.application.Standalone,
  include : [
    bibliograph.MApplicationState,
    qcl.ui.MLoadingPopup
  ],

  statics:{
    /**
     * Widget ids as static constants
     * @todo use or remove
     */
    ids : {
      app : {
        treeview : "app/treeview"
      }
    },

    /**
     * Messages
     */
    messages: {
      TERMINATE : "app.terminate"
    },

    /**
     * Mime types
     */
    mime_types : {
      folder : "x-bibliograph/folderdata"
    }
  },

  members :
  {
    /**
     * This method contains the initial application code and gets called
     * during startup of the application
     */
    main : async function() {
      this.base(arguments);

      this.__clients = {};
      this.__widgets = {};
      this.__dialogs = {};
      this.__blocker = new qx.ui.core.Blocker(this.getRoot());

      if (qx.core.Environment.get("qx.debug")) {
        qx.log.appender.Native;
      }

      // object id for main application
      this.setQxObjectId("app");
      qx.core.Id.getInstance().register(this);
  
      // hide any popup when an jsonrpc error occurs
      qx.event.message.Bus.subscribe("jsonrpc.error", () => this.hidePopup());

      // application startup
      await bibliograph.Setup.getInstance().boot();
      
      // enable object id window during development
      if (qx.core.Environment.get("qx.debug")) {
        qcl.ui.tool.ObjectIds.getInstance();
      }
  
      qx.event.message.Bus.subscribe("jsonrpc.error", async msg => {
        let error = msg.getData();
        console.warn(error.message);
        if (error.message === "Unauthorized" && !this.__loggedOutOnUnauthorized) {
          this.__loggedOutOnUnauthorized = true;
          // silence the other "unauthorized" errors
          Object.values(this.getRpcClients()).forEach(client => client.setErrorBehavior("warning"));
          await this.getAccessManager().logout();
          Object.values(this.getRpcClients()).forEach(client => client.setErrorBehavior("dialog"));
        }
      });
      
      // log to the console to let UI testers know that setup is completed
      let completedMessage = "bibliograph.setup.completed";
      qx.event.message.Bus.dispatchByName(completedMessage);
      console.log(completedMessage);
    },

    /*
    ---------------------------------------------------------------------------
        AUTHOR AND VERSION
    ---------------------------------------------------------------------------
    */

    /**
     * The version of the application. The version will be automatically replaced
     * by the script that creates the distributable zip file. Do not change.
     * @return {String}
     */
    getVersion : function() {
      return qx.core.Environment.get("app.version");
    },

    /**
     * Copyright notice
     * @return {String}
     */
    getCopyright : function() {
      let year = (new Date()).getFullYear();
      return "2003-" + year + " (c) Christian Boulanger";
    },

    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */

    /** @var {qx.bom.storage.Web} */
    __storage : null,
    /** @var {Object} */
    __clients : null,
    /** {qx.ui.core.Blocker} */
    __blocker : null,
    /** @var {String} */
    __url : null,
    /** @var {Object} */
    __widgets : null,
    /** @var {qxl.taskmanager.Manager} */
    __taskMonitor : null,
    /** var {Object} **/
    __dialogs: null,

   /*
    ---------------------------------------------------------------------------
     COMPONENTS
    ---------------------------------------------------------------------------
    */

    /**
     * @return {qcl.access.User}
     */
   getActiveUser : function() {
     return this.getAccessManager().getActiveUser();
   },

    /**
     * @return {bibliograph.AccessManager}
     */
    getAccessManager : function() {
      return bibliograph.AccessManager.getInstance();
    },

    /**
     * @return {qcl.access.PermissionManager}
     */
    getPermissionManager : function() {
      return this.getAccessManager().getPermissionManager();
    },

    /**
     * @return {bibliograph.ConfigManager}
     */
    getConfigManager : function() {
      return bibliograph.ConfigManager.getInstance();
    },

    /**
     * @return {qcl.application.StateManager}
     */
    getStateManager : function() {
      return qcl.application.StateManager.getInstance();
    },

    /**
     * @return {qx.bom.storage.Web}
     */
    getStorage : function() {
      if (!this.__storage) {
        this.__storage = qx.bom.Storage.getSession();
      }
      return this.__storage;
    },

    /**
     * @return {qx.ui.core.Blocker}
     */
    getBlocker : function () {
      return this.__blocker;
    },

    /**
     * @return {bibliograph.Commands}
     */
    getCommands : function() {
      return bibliograph.Commands.getInstance();
    },

    /**
     * @return {bibliograph.store.Datasources}
     */
    getDatasourceStore : function() {
      return bibliograph.store.Datasources.getInstance();
    },

    /**
     * @return {qcl.application.ClipboardManager}
     */
    getClipboardManager: function() {
      return qcl.application.ClipboardManager.getInstance();
    },
  
    /**
     * @return {qxl.taskmanager.Manager}
     */
    getTaskMonitor() {
      if (!this.__taskMonitor) {
        this.__taskMonitor = new qxl.taskmanager.Manager();
      }
      return this.__taskMonitor;
    },
  
    /**
     * Returns a promise for a (cached) dialog
     * @param {String} type
     * @param {Object} config
     * @return {Promise<Boolean>}
     */
    createDialog(type, config) {
      let dialog = this.__dialogs[type];
      if (dialog === undefined) {
        dialog = this.__dialogs[type] = qxl.dialog.Dialog[type]();
        this.addOwnedQxObject(dialog, type);
      }
      if (qx.lang.Type.isObject(config)) {
        dialog.set(config);
      }
      return dialog.promise();
    },
  
    /**
     * Return the promise for a (cached) alert dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    alert(msg, config= {}) {
      config.message = msg;
      return this.createDialog("alert", config);
    },
  
    /**
     * Return the promise for a (cached) warning dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    warning(msg, config= {}) {
      config.message = msg;
      return this.createDialog("warning", config);
    },
  
    /**
     * Return the promise for a (cached) error dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    error(msg, config= {}) {
      config.message = msg;
      return this.createDialog("error", config);
    },
  
    /**
     * Return the promise for a (cached) confirm dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    confirm(msg, config= {}) {
      config.message = msg;
      return this.createDialog("confirm", config);
    },
  
    /**
     * Return the promise for a (cached) prompt dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    prompt(msg, config= {}) {
      config.message = msg;
      return this.createDialog("prompt", config);
    },

    /*
    ---------------------------------------------------------------------------
     COMMANDS
    ---------------------------------------------------------------------------
    */

    /**
     * Run command by dispatching a message
     *
     * @param command
     * @param value
     */
    cmd : function(command, value) {
      qx.event.message.Bus.dispatchByName(`bibliograph.command.${command}`, value);
    },

   /*
    ---------------------------------------------------------------------------
       I/O
    ---------------------------------------------------------------------------
    */

    /**
     * Returns the URL to the JSONRPC server
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
     * Returns a jsonrpc client object with the current auth token already set.
     * The client can be referred to by the object id "application/jsonrpc/<service name>"
     * @param {String} service The name of the service to get the client for
     * @return {qcl.io.jsonrpc.Client}
     */
    getRpcClient : function(service) {
      qx.core.Assert.assert(Boolean(service), "Service parameter cannot be empty");
      qx.util.Validate.checkString(service, "Service parameter must be a string");
      if (!this.__clients[service]) {
        let client = new qcl.io.jsonrpc.Client(this.getServerUrl() + "/json-rpc", service);
        client.setErrorBehavior("dialog");
        this.__clients[service] = client;
      }
      let client = this.__clients[service];
      client.setToken(this.getAccessManager().getToken() || null);
      return client;
    },
  
    /**
     * Returns a map, keys are the service names, values the corresponding
     * {@link qcl.io.jsonrpc.Client}.
     * @return {Object}
     */
    getRpcClients() {
      return this.__clients;
    },

    /**
     * Returns a promise that resolves when a message of that name has
     * been dispatched.
     * @param {String} message The name of the message
     * @return {Promise<true>}
     */
    resolveOnMessage: function(message) {
      return new Promise(resolve => qx.event.message.Bus.subscribeOnce(message, resolve));
    },

    /*
    ---------------------------------------------------------------------------
       WIDGET ID
    ---------------------------------------------------------------------------
    */

    /**
     * Store a reference to a widget linked to its id.
     * @param id {String}
     * @param widget {qx.ui.core.Widget}
     */
    setWidgetById : function(id, widget) {
      this.__widgets[id] = widget;
    },

    /**
     * gets a reference to a widget by its id
     * @param id {String}
     * @return {qx.ui.core.Widget} The widget with the given id
     * @throws {Error}
     */
    getWidgetById : function(id) {
      let widget = this.__widgets[id];
      if (!widget) {
        this.error(`A widget with id '${id}' does not exist.`);
      }
      return widget;
    },

    /**
     * Called when the applicatin shuts down
     */
    terminate : function() {
      qx.event.message.Bus.dispatchByName(bibliograph.Application.messages.TERMINATE);
    }
  }
});
