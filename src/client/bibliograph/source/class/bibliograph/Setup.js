/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright:
  2018 Christian Boulanger

  License:
  MIT license
  See the LICENSE file in the project's top-level directory for details.

  Authors:
  Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * This is a qooxdoo singleton class
 *
 */
qx.Class.define("bibliograph.Setup", {
  extend: qx.core.Object,
  type: "singleton",
  include : [
    qcl.ui.MLoadingPopup,
    qx.locale.MTranslation
  ],

  statics: {
    // TODO move messages in own class
    messages: {
      /**
       * Exececute an arbitrary jsonrpc call.
       * @param {[service,method,params]}
       */
      EXECUTE_JSONRPC: "jsonrpc.execute",
      /**
       * Shows the login dialog
       */
      SHOW_LOGIN_DIALOG: "loginDialog.show",
      /**
       * Reload the main list
       */
      RELOAD_LISTVIEW: "mainListView.reload",
      /**
       * Loads an URL, replacing the current page
       */
      REPLACE_URL: "window.location.replace",
      /**
       * Sets the current record model
       * @todo
       */
      SET_MODEL: "bibliograph.setModel",
      /**
       * Logout the current user
       */
      LOGOUT: "client.logout",
      /**
       * Logs a message to the console
       * @param {String}
       */
      LOG_TO_CONSOLE: "console.log",
      /**
       * Reloads the application
       */
      RELOAD_APPLICATION: "application.reload"
    }
  },

  members: {

    /**
     * Dummy method to mark dynamically generated messages for translation
     */
    markForTranslation : function() {
      this.tr("No connection to server.");
      this.tr("Loading folder data ...");
    },

    /*
    ---------------------------------------------------------------------------
      BOOT
    ---------------------------------------------------------------------------
    */

    boot : async function() {
      //  Mixes `getApplication()` into all qooxdoo objects
      qx.Class.include(qx.core.Object, qcl.application.MGetApplication);
      // Mixes `widgetId` property into all qooxdoo objects
      qx.Class.include(qx.core.Object, qcl.application.MWidgetId);

      this.setupClipboard();

      // initialize application commands
      bibliograph.Commands.getInstance();

      // datasource store, configures itself and responds to authentication events
      bibliograph.store.Datasources.getInstance();

      let app = this.getApplication();

      // save state from querystring
      this.saveApplicationState();

      // User interface translations
      this.setupUiTranslations();

      // create main UI Layout
      bibliograph.ui.Windows.getInstance().create();
      bibliograph.ui.MainLayout.getInstance().create();
  
      qx.core.Id.getQxObject("toolbar/login-button").setEnabled(false);
      qx.core.Id.getQxObject("toolbar/logout-button").setEnabled(false);

      // show the splash screen
      this.createPopup({
        icon : "bibliograph/icon/bibliograph-logo.png",
        iconPosition : "top",
        width : 550,
        height : 170
      });
      this.showPopup(this.getSplashMessage(), null);
      // application loading popup
      app.createPopup();

      // blocker
      this.createBlocker();

      //  allow incoming server dialogs
      qcl.ui.dialog.ServerDialog.getInstance().setEnabled(true);

      // server setup
      this.showPopup(this.getSplashMessage(this.tr("Setting up application...")));
      qx.event.message.Bus.subscribe("jsonrpc.error", () => {
        this.hidePopup();
      });
      await this.checkServerSetup();

      // initialize managers, they will automatically load after authentication
      app.getConfigManager().init();
      app.getAccessManager().init();
      
      // authenticate
      this.showPopup(this.getSplashMessage(this.tr("Loading user data...")));
      await this.authenticate();

      // load plugins
      this.showPopup(this.getSplashMessage(this.tr("Loading plugins...")));
      this.initializePlugins();

      // initialize application state
      app.getStateManager().setHistorySupport(true);
      app.getStateManager().updateState();

      // reset splash screen
      this.hidePopup();
      this.createPopup();

      // restore app state
      this.restoreApplicationState();

      // initialize subscribers to messages that come from server
      this.initSubscribers();
      
      // enable login/logout buttons
      qx.core.Id.getQxObject("toolbar/login-button").setEnabled(true);
      qx.core.Id.getQxObject("toolbar/logout-button").setEnabled(true);
      
      // message transport
      //this.startPolling();
    },

    /*
    ---------------------------------------------------------------------------
      SETUP METHODS
    ---------------------------------------------------------------------------
    */


    /**
     * Save some intial application states which would otherwise be overwritten
     */
    saveApplicationState : function() {
      let app = this.getApplication();
      this.__itemView = app.getStateManager().getState("itemView");
      this.__folderId = app.getStateManager().getState("folderId");
      this.__query = app.getStateManager().getState("query");
      this.__modelId = app.getStateManager().getState("modelId");
    },

    /**
     * Creates the blocker for modal popupus
     */
    createBlocker : function() {
      let app = this.getApplication();
      let blocker = new qx.ui.core.Blocker(app.getRoot());
      blocker.setOpacity(0.5);
      blocker.setColor("black");
      app.__blocker = blocker;
    },

    /**
     * Sets the locale according to the browser settings.
     * This can be overridden by a config value
     */
    setupUiTranslations : function() {
      let confMgr = this.getApplication().getConfigManager();
      let localeManager = qx.locale.Manager.getInstance();
      let currentLocale = localeManager.getLocale();
      this.info("Browser locale: " + currentLocale);
      // override locale from config
      confMgr.addListenerOnce("change", e => {
        if (e.getData() !== "application.locale") {
         return;
        }
        let localeFromConfig = confMgr.getKey("application.locale");
        if (localeFromConfig && localeFromConfig !== localeManager.getLocale()) {
          this.info(`Switching locale to '${localeFromConfig}' as per user configuration.`);
          localeManager.setLocale(localeFromConfig);
        }
      });
    },

    /**
     * Returns the message displayed below the splash screen icon.
     * By default, return the version and copyright text.
     * @param text {String} Optional text appended to the splash message
     * @return {String}
     */
    getSplashMessage : function(text) {
      let app = this.getApplication();
      return app.getVersion() + "<br />" + app.getCopyright() + "<br />" + (text || "");
    },

    /**
     * Unless we have a token in the session storage, authenticate
     * anomymously with the server.
     * @return {Promise<void>}
     */
    async authenticate() {
      let authManager = bibliograph.AccessManager.getInstance();
      let token = authManager.getToken();
      if (token) {
        this.info("Got access token from session storage");
      } else {
        let {error} = await authManager.guestLogin();
        if (error) {
          this.getApplication().error(error);
          return;
        }
      }
      await authManager.afterAuthentication();
    },

    /**
     * This will initiate server setup. Returned promise resolves when server
     * dispatches a "bibliograph.setup.done" message.
     * @return {Promise<void>}
     */
    async checkServerSetup() {
      // 'await' omitted in the next line, since the message is what we're waiting for
      // this allows the server to interact with the user before setup is completed
      // (i.e. through Wizard or Dialogs)
      const bus = qx.event.message.Bus;
      const client = this.getApplication().getRpcClient("setup");
      bus.subscribe("bibliograph.setup.next", () => {
        this.hidePopup();
        client.request("setup");
      });
      client.request("setup");
      await this.getApplication().resolveOnMessage("bibliograph.setup.done");
      this.info("Server setup done.");
    },
    

    /**
     * Loads the plugins
     */
    initializePlugins : function() {
      for (let pluginNamespace of Object.keys(bibliograph.plugins)) {
        // @todo do not initialize diabled plugins/modules
        //let key = `modules.`;
        //let enabled = this.getApplication().getConfigManager().getKey(key);
        let plugin;
        try {
          plugin = bibliograph.plugins[pluginNamespace].Plugin.getInstance();
        } catch (e) {
          console.error(e);
          this.warn(`Could not instantiate plugin '${pluginNamespace}': ${e}`);
        }
        try {
          let message = plugin.init();
          this.info(message || `Initialized plugin '${plugin.getName()}'`);
        } catch (e) {
          console.error(e);
          this.error(`Could not initialize plugin '${plugin.getName()}': ${e}`);
        }
      }
    },

    /**
     * Initialize  subscribers for server messages
     */
    initSubscribers : function() {
      let bus = qx.event.message.Bus.getInstance();
      let app = this.getApplication();
      let messages = bibliograph.Setup.messages;

      // listen to reload event
      bus.subscribe(messages.RELOAD_APPLICATION, () => window.location.reload());

      // remotely log to the browser console
      bus.subscribe(messages.LOG_TO_CONSOLE, e => console.log(e.getData()));

      // server message to set model type and id
      bus.subscribe(messages.SET_MODEL, e => {
        let data = e.getData();
        if (data.datasource === app.getDatasource()) {
          app.setModelType(data.modelType);
          app.setModelId(data.modelId);
        }
      });

      // used by the bibliograph.export.exportReferencesHandleDialogData
      bus.subscribe(messages.REPLACE_URL, e => {
        let data = e.getData();
        window.location.replace(data.url);
      });

      // reload the main list view
      bus.subscribe(messages.RELOAD_LISTVIEW, e => {
        let data = e.getData();
        if (data.datasource !== app.getDatasource()) {
         return;
        }
        qx.core.Id.getQxObject("table-view").reload();
      });

      // show the login dialog
      bus.subscribe(messages.SHOW_LOGIN_DIALOG, () => {
        app.getWidgetById("app/windows/login").show();
      });

      // execute an arbitrary JSONRPC method
      bus.subscribe(messages.EXECUTE_JSONRPC, async e => {
        let [service, method, params] = e.getData();
        await app.getRpcClient(service).request(method, params);
      });
    },

    /**
     * Setup clipboard synchronization with server
     */
    setupClipboard: function() {
      let bus = qx.event.message.Bus.getInstance();
      let app = this.getApplication();
      let clipboard = app.getClipboardManager();
      this.__updatingClipboardFromServer = false;
      bus.subscribe("clipboard.add", e => {
        this.__updatingClipboardFromServer = true;
        clipboard.addData(e.getData().mime_type, e.getData().data);
        this.__updatingClipboardFromServer = false;
      });
      clipboard.addListener("changeData", e => {
        let mimeType = e.getData();
        if (!this.__updatingClipboardFromServer) {
          rpc.Clipboard.add(mimeType, clipboard.getData(mimeType));
        }
      });
    },

    /**
     * Restores the state of the origininal URL
     */
    restoreApplicationState : function() {
      let app = this.getApplication();
      if (this.__itemView) {
        app.setItemView(this.__itemView);
      }
      if (this.__selectedIds) {
        let selectedIds = [];
        this.__selectedIds.split(",").forEach(function(id) {
          id = parseInt(id);
          if (id && !isNaN(id)) {
           selectedIds.push(id);
          }
        }, this);
        app.setSelectedIds(selectedIds);
      }
      if (this.__folderId && !isNaN(parseInt(this.__folderId))) {
        this.info("Restoring folder id: " + this.__folderId);
        app.setFolderId(parseInt(this.__folderId));
      } else if (this.__query) {
        this.info("Restoring query: " + this.__query);
        app.setQuery(this.__query);
      }
      if (this.__modelId && !isNaN(parseInt(this.__modelId))) {
        this.info("Restoring model id: " + this.__modelId);
        app.setModelId(parseInt(this.__modelId));
      }
    },

    /**
     * Start polling service to get messages when no server action
     * happens
     */
    startPolling : async function() {
      let delayInMs = await this.getApplication().getRpcClient("message").request("getMessages");
      if (delayInMs) {
        qx.lang.Function.delay(this.startPolling, delayInMs, this);
      }
    }
  }
});
