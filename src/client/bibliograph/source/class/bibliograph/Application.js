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
 * @require(bibliograph.rpc.Commands)
 */
qx.Class.define("bibliograph.Application", {
  extend : qx.application.Standalone,
  include : [
    bibliograph.MApplicationState,
    qcl.ui.MLoadingPopup,
    qcl.ui.dialog.MDialog,
    qcl.io.jsonrpc.MClientCache
  ],

  statics:{
    /**
     * Widget ids as static constants
     * @todo use or remove
     */
    ids : {
    
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
      this.__widgets = {};
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
      if (qx.core.Environment.get("qcl.ui.tool.ObjectIds.enable")) {
        qcl.ui.tool.ObjectIds.getInstance();
      }
      
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
    /** {qx.ui.core.Blocker} */
    __blocker : null,
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
