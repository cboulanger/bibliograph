/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */
/*global bibliograph qx qcl dialog*/

/**
 * The main application class
 * @asset(bibliograph/*)
 * @asset(keypress/*)
 * @require(bibliograph.theme.Assets);
 * @require(qcl.ui.dialog.Dialog)
 * @require(qx.ui.form.RadioGroup)
 * @require(qx.ui.menu.RadioButton)
 */
qx.Class.define("bibliograph.Main",
{
  extend : qx.application.Standalone,
  include : [qcl.application.MAppManagerProvider, qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties :
  {
    /**
     * The name of the current datasource
     */
    datasource :
    {
      check : "String",
      nullable : true,
      apply : "_applyDatasource",
      event : "changeDatasource"
    },

    /**
     * The model of the current datasource
     */
    datasourceModel :
    {
      check : "qx.core.Object",
      nullable : true,
      event : "changeDatasourceModel"
    },

    /**
     * The name of the datasource as it should appear in the UI
     * @todo remove, use datasourceModel instead
     */
    datasourceLabel :
    {
      check : "String",
      nullable : true,
      event : "changeDatasourceLabel",
      apply : "_applyDatasourceLabel"
    },

    /**
     * The id of the currently displayed model record
     */
    modelId :
    {
      check : "Integer",
      nullable : true,
      apply : "_applyModelId",
      event : "changeModelId"
    },

    /**
     * The type of the currently displayed model record
     */
    modelType :
    {
      check : "String",
      nullable : true,
      apply : "_applyModelType",
      event : "changeModelType"
    },

    /**
     * The current folder id
     */
    folderId :
    {
      check : "Integer",
      nullable : true,
      apply : "_applyFolderId",
      event : "changeFolderId"
    },

    /**
     * The current query
     */
    query :
    {
      check : "String",
      nullable : true,
      apply : "_applyQuery",
      event : "changeQuery"
    },

    /**
     * The currently active item view
     */
    itemView :
    {
      check : "String",
      nullable : true,
      event : "changeItemView",
      apply : "_applyItemView"
    },

    /**
     * The ids of the currently selected rows
     */
    selectedIds :
    {
      check : "Array",
      nullable : false,
      event : "changeSelectedIds",
      apply : "_applySelectedIds"
    },

    /**
     * The name of the theme
     * currently not used, because only the modern theme functions
     * correctly with the current UI
     */
    theme :
    {
      check : ["Modern","Simple","Indigo" ],
      nullable : false,
      apply : "_applyTheme"
    },

    /**
     * Target for inserting something from an external source into a
     * TextField or TextArea widget
     */
    insertTarget :
    {
      check : "qx.ui.form.AbstractField",
      nullable : true
    }
  },

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  members :
  {
    /*
    ---------------------------------------------------------------------------
       AUTHOR AND VERSION
    ---------------------------------------------------------------------------
    */
    getVersion : function() {
      return "v2.1 Beta2 (06.05.2014)";
    },
    getCopyright : function() {
      return "2003-2014 (c) Christian Boulanger";
    },

    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */
    __persistentStore : null,
    __datasourceStore : null,
    __itemView : null,
    __selectedIds : null,
    __blocker : null,

    /*
    ---------------------------------------------------------------------------
       MAIN METHOD
    ---------------------------------------------------------------------------
    */

    /**
     * Initialize the application
     *
     * @return {void}
     */
    main : function()
    {
      /*
       * logging
       */
      if ((qx.core.Environment.get("qx.debug"))) {
        qx.log.appender.Native;
      }

      /*
       * call parent class' main method
       */
      this.base(arguments);

      /*
       * Application id and name
       */
      this.setApplicationId("bibliograph");
      this.setApplicationName("Bibliograph Online Bibliographic Data Manager");

      /*
       * creat popup and show splash screen
       */
      this.createPopup(
      {
        icon : "bibliograph/icon/bibliograph-logo.png",
        iconPosition : "top",
        width : 550,
        height : 170
      });
      this.showPopup(this.getSplashMessage(), null);

      /*
       * create central blocker for the application
       */
      var root = qx.core.Init.getApplication().getRoot();
      this.__blocker = new qx.ui.core.Blocker(root);
      this.__blocker.setOpacity( 0.5 );
      this.__blocker.setColor( "black" );

      /*
       * initialize the managers
       */
      this.initializeManagers();

      /*
       * rpc endpoint and timeout
       */
      this.getRpcManager().setServerUrl("../services/server.php");
      this.getRpcManager().getRpcObject().setTimeout(180000);  //3 Minutes

      /*
       * save some intial application states
       * which would otherwise be overwritten
       */
      this.__itemView = this.getStateManager().getState("itemView");
      this.__folderId = this.getStateManager().getState("folderId");
      this.__query = this.getStateManager().getState("query");
      this.__modelId = this.getStateManager().getState("modelId");

      /*
       * Setup and start authentication and configuration
       */
      this.getAccessManager().init();
      this.getConfigManager().init();
      this.getAccessManager().setService("bibliograph.access");
      this.getConfigManager().setService("bibliograph.config");

      /*
       *  allow incoming server dialogs
       */
      qcl.ui.dialog.Dialog.allowServerDialogs(true);

      /*
       * Setup event handler called when the datasource store is
       * reloaded
       */
      this.getDatasourceStore().addListener("loaded", this._on_datasourceStore_loaded, this);

      /*
       * bind application title to datasource title
       */
      this.bind("datasourceModel.title", this, "datasourceLabel");

      /*
       * run setup
       */
      this.info("Setting up application...");
      this._startSetup();
    },

    _startSetup : function()
    {
      this.showPopup(this.getSplashMessage(this.tr("Setting up application...")));
      qx.event.message.Bus.getInstance().subscribe("bibliograph.setup.done", this._setupDone, this);
      this.getRpcManager().execute("bibliograph.setup", "setup", []);
    },

    _setupDone : function()
    {
      qx.event.message.Bus.getInstance().unsubscribe("bibliograph.setup.done", this._setupDone, this);
      this._connect();
    },

    /**
     * Connect to the server: authenticate and laod configuration
     */
    _connect : function()
    {
      this.info("Authenticating ...");

      /*
       * (re-) authenticate
       */
      this.showPopup(this.getSplashMessage(this.tr("Connecting with server...")));
      this.getAccessManager().connect(function()
      {
        /*
         * notify subscribers
         */
        qx.event.message.Bus.dispatch(new qx.event.message.Message("connected"));

        /*
         * when done, load config values and continue with loading datasources
         */
        this.showPopup(this.getSplashMessage(this.tr("Loading configuration ...")));
        this.getConfigManager().load(this._loadDatasources, this);
      }, this);
    },

    /**
     * Load datasource data
     */
    _loadDatasources : function()
    {
      this.info("Loading datasources ...");

      /*
       * now load datasources and update app state to trigger
       * ui changes. Afterwards, continue with state intialization
       */
      this.showPopup(this.getSplashMessage(this.tr("Loading datasources ...")));
      this.getDatasourceStore().load("getDatasourceListData", [], this._initializeState, this);
    },

    /**
     * Initializes the application state
     */
    _initializeState : function()
    {
      this.info("Initializing application state ...");

      /*
       * initialize application state
       */
      this.getStateManager().setHistorySupport(true);
      this.getStateManager().updateState();
      this._loadPlugins();
    },

    /**
     * Loads the plugins
     */
    _loadPlugins : function()
    {
      this.info("Loading plugins...");
      this.showPopup(this.getSplashMessage(this.tr("Loading plugins ...")));
      this.getPluginManager().addListener("loadingPlugin", function(e)
      {
        var data = e.getData();
        this.showPopup(this.getSplashMessage(this.tr("Loaded plugin %1 of %2 : %3 ...", data.count, data.sum, data.name)));
      }, this);

      /*
       * load plugin code
       */
      this.getPluginManager().setPreventCache(true);
      this.getPluginManager().loadPlugins(this._finalize, this);
    },

    /**
     * Finalizes the application
     */
    _finalize : function()
    {
      this.info("Finalizing setup...");

      /*
       * reset popup to remove splash screen
       */
      this.hidePopup();
      this.createPopup();

      /*
       * initialize message subscribers
       */
      this.initSubscribers();

      /*
       * restore application states
        */
      if (this.__itemView) {
        this.setItemView(this.__itemView);
      }
      if (this.__selectedIds)
      {
        var selectedIds = [];
        this.__selectedIds.split(",").forEach(function(id)
        {
          id = parseInt(id);
          if (id && !isNaN(id))selectedIds.push(id);

        }, this);
      }
      if (this.__folderId && !isNaN(parseInt(this.__folderId))) {
        this.info("Restoring folder id: " + this.__folderId);
        this.setFolderId(parseInt(this.__folderId))
      } else if (this.__query) {
        this.info("Restoring query: " + this.__query);
        this.setQuery(this.__query);
      }

      if (this.__modelId && !isNaN(parseInt(this.__modelId))) {
        this.info("Restoring model id: " + this.__modelId);
        this.setModelId(parseInt(this.__modelId))
      }

      /*
       * start polling
       */
      this._pollingService();
    },

    /**
     * Polling service
     */
    _pollingService : function() {
      this.getRpcManager().execute("bibliograph.access", "getMessages", [], function(delayInMs){
        if( delayInMs )
        {
          qx.lang.Function.delay(this._pollingService,delayInMs,this);
        }
      }, this);
    },

    /**
     * Returns the message displayed below the splash screen icon.
     * By default, return the version and copyright text.
     * @param text {String} Optional text appended to the splash message
     * @return {String}
     */
    getSplashMessage : function(text) {
      return this.getVersion() + "<br />" + this.getCopyright() + "<br />" + (text || "");
    },

    /**
     * TODOC
     *
     * @return {void}
     */
    initSubscribers : function()
    {
      var bus = qx.event.message.Bus;

      /*
       * server message to set model type and id
       */
      bus.subscribe("bibliograph.setModel", function(e)
      {
        var data = e.getData();
        if (data.datasource == this.getDatasource())
        {
          this.setModelType(data.modelType);
          this.setModelId(data.modelId);
        }
      }, this);

      /*
       * opens a browser window with the given url
       * currently not used
       */
//      bus.subscribe("openBrowserWindow", function(e)
//      {
//        var data = e.getData();
//        window.open(data.url);
//      }, this);


      /*
       * called after a backup has been restored
       */
      bus.subscribe("backup.restored", function(e)
      {
        var data = e.getData();
        if (data.datasource !== this.getDatasource())return;

        var msg = this.tr("The datasource has just been restored to a previous state and will be reloaded");
        dialog.Dialog.alert(msg, function()
        {
          this.getWidgetById("mainFolderTree").reload();
          this.getWidgetById("mainListView").reload();
          this.setModelId(0);
        }, this);
      }, this);

    },

    /*
    ---------------------------------------------------------------------------
       GETTERS
    ---------------------------------------------------------------------------
    */

    /**
     * Return the jsonrpc store for the datasource list
     *
     * @return {var} qcl.data.store.JsonRpc
     */
    getDatasourceStore : function()
    {
      if (!this.__datasourceStore)
      {
        this.__datasourceStore = new qcl.data.store.JsonRpc(null, "bibliograph.model", null);
        qx.event.message.Bus.subscribe("reloadDatasources", function() {
          this.__datasourceStore.reload();
        }, this);
      }
      return this.__datasourceStore;
    },

    /**
     * Return the persistent store used by this application
     * @return {persist.Store}
     */
    getPersistentStore : function()
    {
      if (!this.__persistentStore) {
        this.__persistentStore = new persist.Store('Bibliograph', 1);
      }
      return this.__persistentStore;
    },

    /**
     * Returns the central blocker for this app.
     * @returns {qx.ui.core.Blocker}
     */
    getBlocker : function()
    {
      return this.__blocker;
    },

    /*
    ---------------------------------------------------------------------------
       APPLY METHODS: synchronize state with property etc.
    ---------------------------------------------------------------------------
    */

    /**
     * TODOC
     *
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     */
    _applyDatasource : function(value, old)
    {
      var stateMgr = this.getStateManager();

      /*
       * reset all states that have been connected
       * with the datasource if a previous datasource
       * has been loaded
       * FIXME hide search box when no datasource is selected
       */
      if (old)
      {
        this.setModelId(0);
        this.setFolderId(0);
        this.setSelectedIds([]);
        this.setQuery(null);
        this.setDatasourceModel(null);
      }
      if (value)
      {
        /*
         * set the application state
         */
        stateMgr.setState("datasource", value);

        /*
         * load datasource model from server
         * @todo use store
         */
        this.showPopup(this.tr("Loading datasource information ..."));
        this.getRpcManager().execute("bibliograph.model", "getDatasourceModelData", [value], function(data)
        {
          this.hidePopup();
          var model = qx.data.marshal.Json.createModel(data);
          this.setDatasourceModel(model);
          this.setModelType(model.getTableModelType());
        }, this);
      } else
      {
        stateMgr.removeState("datasource");
      }
    },

    /**
     * FIXME use font, rename to application title
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     */
    _applyDatasourceLabel : function(value, old)
    {
      if (!value) {
        value = this.getConfigManager().getKey("application.title");
      }
      window.document.title = value;
      this.getWidgetById("applicationTitleLabel").setValue('<span style="font-size:1.2em;font-weight:bold">' + value + '</spsn>');
    },

    /**
     * TODOC
     *
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     */
    _applyFolderId : function(value, old)
    {
      var stmgr = this.getStateManager()
      stmgr.setState("modelId", 0);
      if (value)
      {
        stmgr.setState("folderId", value);
        stmgr.setState("query", "");
        stmgr.removeState("query");
      } else
      {
        stmgr.removeState("folderId");
      }
    },

    /**
     * TODOC
     *
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     * @todo Searchbox widget should observe query state instead of
     * query state binding the searchbox.
     */
    _applyQuery : function(value, old)
    {
      this.getStateManager().setState("query", value);
      if (value && this.getDatasource()) {
        this.getWidgetById("searchBox").setValue(value);
      } else {
        this.getStateManager().removeState("query");
        this.getWidgetById("searchBox").setValue("");
      }
    },

    /**
     * TODOC
     *
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     */
    _applyModelType : function(value, old)
    {
      if (old) {
        this.getStateManager().setState("modelId", 0);
      }
      if (value) {
        this.getStateManager().setState("modelType", value);
      } else {
        this.getStateManager().removeState("modelType");
      }
    },

    /**
     * TODOC
     *
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     */
    _applyModelId : function(value, old) {
      if (value) {
        this.getStateManager().setState("modelId", value);
      } else {
        this.getStateManager().removeState("modelId");
      }
    },

    /**
     * TODOC
     *
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     */
    _applyItemView : function(value, old) {
      if (value) {
        this.getStateManager().setState("itemView", value);
      } else {
        this.getStateManager().removeState("itemView");
      }
    },

    /**
     * TODOC
     *
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     */
    _applySelectedIds : function(value, old) {
      //
    },

    /**
     * TODOC
     *
     * @param value {var} TODOC
     * @param old {var} TODOC
     * @return {void}
     */
    _applyTheme : function(value, old) {
      //qx.theme.manager.Meta.getInstance().setTheme(qx.theme[value]);
    },
    //

    /*
    ---------------------------------------------------------------------------
       LOGIN & LOGOUT
    ---------------------------------------------------------------------------
    */

    /**
     * Called when the user presses the "login" button
     */
    login : function()
    {
      /*
       * check if https login is enforced
       */
      var enforce_https = this.getConfigManager().getKey("access.enforce_https_login");
      if (enforce_https && location.protocol != "https:") {
        dialog.Dialog.alert(this.tr("To log in, you need a secure connection. After you press 'OK', the application will be reloaded in secure mode. After the application finished loading, you can log in again."), function()
        {
          qx.core.Init.getApplication().setConfirmQuit(false);
          location.href = "https://" + location.host + location.pathname + location.hash;
        }, this);
      } else {
        /*
         * check if access is restricted
         */
        if (this.getConfigManager().getKey("bibliograph.access.mode") == "readonly" && !this.__readonlyConfirmed)
        {
          var msg = this.tr("The application is currently in a read-only state. Only the administrator can log in.");
          var explanation = this.getConfigManager().getKey("bibliograph.access.no-access-message");
          if (explanation) {
            msg += "\n" + explanation;
          }
          dialog.Dialog.alert(msg, function() {
            this.__readonlyConfirmed = true;
          }, this);
        } else
        {
          /*
           * else show login dialog
           */
          this.getWidgetById("loginDialog").show();
        }
      }
    },

    /**
     * Callback function that takes the username, password and
     * another callback function as parameters.
     * The passed function is called with a boolean value
     * (true=authenticated, false=authentication failed) and an
     * optional string value which can contain an error message :
     * callback( {Boolean} result, {String} message);
     *
     * @param username {String} TODOC
     * @param password {String} TODOC
     * @param callback {Function} The callback function
     * @return {void}
     */
    checkLogin : function(username, password, callback)
    {
      var app = qx.core.Init.getApplication();
      app.showPopup(app.tr("Authenticating ..."));
      app.getAccessManager().authenticate(username, password, function(data) {
        app.hidePopup();
        if (data.error) {
          callback(false, data.error);
        } else {
          /*
           * login was successful
           */
          callback(true);

          /*
           * load configuration data for this user
           */
          app.getConfigManager().load(function() {
            /*
             * load datasources
             */
            app.getDatasourceStore().reload(function()
            {
              app.hidePopup();

              /*
               * notify subscribers
               */
              qx.event.message.Bus.dispatch(new qx.event.message.Message("authenticated"));
            });
          });
        }
      });
    },


    /**
     * Log out the current user
     *
     * @return {void}
     */
    logout : function()
    {
      /*
       * notify listeners
       */
      qx.event.message.Bus.dispatchByName("logout");

      /*
       * call parent method to log out
       */
      this.showPopup("Logging out ...");
      this.getAccessManager().logout(function() {
        /*
         * reload configuration data for anonymous
         */
        this.getConfigManager().load(function() {
          /*
           * load datasources
           */
          this.getDatasourceStore().reload(function()
          {
            this.hidePopup();

            /*
             * notify subscribers
             */
            qx.event.message.Bus.dispatch(new qx.event.message.Message("loggedOut"));
          }, this);
        }, this);
      }, this);
    },

    /*
    ---------------------------------------------------------------------------
       EVENT LISTENERS
    ---------------------------------------------------------------------------
     */
    _on_datasourceStore_loaded : function()
    {
      var datasourceCount = this.getDatasourceStore().getModel().length;

      /*
       * if we have no datasource loaded, no access
       */
      if (datasourceCount == 0) {
        dialog.Dialog.alert(this.tr("You don't have access to any datasource on the server."));
      }/*
       * if we have access to exactly one datasource, load this one
       */
       else if (datasourceCount == 1)
      {
        var item = this.getDatasourceStore().getModel().getItem(0);
        this.setDatasource(item.getValue());
        this.getStateManager().updateState();
      }/*
       * else, we have a choice of datasource
       */
       else
      {
        /*
         * if there is one saved in the application state, use this
         */
        var datasource = this.getStateManager().getState("datasource");
        if (!datasource)
        {
          this.setDatasourceLabel(this.getConfigManager().getKey("application.title"));
          var dsWin = this.getWidgetById("datasourceWindow");
          dsWin.open();
          dsWin.center();
        } else
        {
          this.setDatasource(datasource);
          this.getStateManager().updateState();
        }
      }

      /*
       * show datasource button depending on whether there is a choice
       */
      this.getWidgetById("datasourceButton").setVisibility(datasourceCount > 1 ? "visible" : "excluded");
    },

    /*
    ---------------------------------------------------------------------------
       Toolbar commands
    ---------------------------------------------------------------------------
    */

    /**
     * opens a window with the online help
     */
    showHelpWindow : function() {
      if (!this.__helpWindow)
      {

        this.__helpWindow = window.open("http://hilfe.bibliograph.org"); //todo: add link for english
        if (!this.__helpWindow) {
          dialog.Dialog.alert(this.tr("Cannot open help window. Please disable the popup-blocker of your browser for this website."));
        } else
        {
          // todo close window on terminate
        }
      } else
      {
        this.__helpWindow.focus();
      }
    },

    /**
     * Opens a server dialog to submit a bug.
     */
    reportBug : function()
    {
      this.showPopup(this.tr("Please wait ..."));
      this.getRpcManager().execute("bibliograph.main", "reportBugDialog", [], function() {
        this.hidePopup();
      }, this);
    },

    /**
     * Shows the "about" window
     */
    showAboutWindow : function() {
      this.getWidgetById("aboutWindow").open();
    },

    /*
    ---------------------------------------------------------------------------
       HELPER METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * Prints the content of the given dom element, by opening up a new window,
     * copying the content of the element to this new window, and starting the
     * print.
     *
     * @param domElement {Element}
     */
    print : function(domElement)
    {
      if (!domElement instanceof Element)
      {
        this.error("print() takes a DOM element as argument");
        return;
      }
      var win = window.open();
      win.document.open();
      win.document.write(domElement.innerHTML);
      win.document.close();
      win.print();
    },

    /**
     * Helper function for converters in list databinding. If a selected element
     * exist, returns its model value, otherwise return null
     *
     * @param selection {Array} TODOC
     * @return {String | null} TODOC
     */
    getSelectionValue : function(selection) {
      return selection.length ? selection[0].getModel().getValue() : null;
    },

    /**
     * Given a value, return the list element that has the
     * matching model value wrapped in an array. If nothing
     * has been found, return an empty array
     *
     * @param value {String} TODOC
     * @return {Array} TODOC
     */
    getModelValueListElement : function(value)
    {
      for (var i = 0, c = this.getChildren(); i < c.length; i++) {
        if (c[i].getModel().getValue() == value) {
          return [c[i]];
        }
      }

      // console.warn( "Did not find " + value );
      return [];
    },
    editUserData : function()
    {
      var activeUser = this.getAccessManager().getActiveUser();
      if (activeUser.getEditable())
      {
        this.showPopup(this.tr("Retrieving user data..."));
        this.getRpcManager().execute("bibliograph.actool", "editElement", ["user", activeUser.getNamedId()], function() {
          this.hidePopup()
        }, this);
      }
    },
    endOfFile : true
  }
});
