/* ************************************************************************

   Copyright: 2018 Christian Boulanger

   License: MIT license

   Authors: Christian Boulanger (cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * This is the main application class of "Bibliograph"
 *
 * @asset(bibliograph/*)
 */
qx.Class.define("bibliograph.Application",
{
  extend : qx.application.Standalone,



  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */

  members :
  {
    /**
     * This method contains the initial application code and gets called 
     * during startup of the application
     * 
     * @lint ignoreDeprecated(alert)
     */
    main : function()
    {
      this.base(arguments);
      if (qx.core.Environment.get("qx.debug"))
      {
        qx.log.appender.Native;
      }

      //  Mixes `getApplication()` into all qooxdoo objects
      qx.Class.include( qx.core.Object, qcl.application.MGetApplication );

      let setup = bibliograph.Setup.getInstance();
      let am = this.getAccessManager();
      let cm = this.getConfigManager();
      
      var doc = this.getRoot();

      var button1 = new qx.ui.form.Button("Log in as Admin", "bibliograph/test.png");
      button1.setEnabled(false);
      doc.add(button1, {left: 100, top: 50});
      button1.addListener("execute", async () => {
        // auth as admin
        let { token } = await this.getRpcClient("access").send('authenticate',['admin','admin']);
        console.info( "token " + token );
        am.setToken(token);
        await am.load();
        await cm.load();
      });
      //am.getPermission( 'config.value.edit' ).bind( "state", button1, "enabled" );
      am.getUserManager().bind( 'activeUser.anonymous', button1, "enabled" );

      var button2= new qx.ui.form.Button("Change application title", "bibliograph/test.png");
      button2.setEnabled(false);
      doc.add(button2, {left: 100, top: 100 });
      button2.addListener("execute", async () => {
        cm.setKey(
          "application.title",
          await dialog.Dialog.prompt("Enter application title").promise()
        ); 
      });
      am.getPermission( 'config.default.edit' ).bind("state", button2, "enabled");     
      
      var button3= new qx.ui.form.Button("Logout", "bibliograph/test.png");
      button3.setEnabled(false);
      doc.add(button3, {left: 100, top: 150});
      button3.addListener("execute", async () => {
        am.logout();
      });
      am.getUserManager().bind( 'activeUser.anonymous', button3, "enabled", {
        converter : value => ! value
      });
      am.getUserManager().bind( 'activeUser.username', button3, "label", {
        converter : value => `Logout ${value||""}`
      });      

      var label1 = new qx.ui.basic.Label("Loading...");
      doc.add(label1,  {left: 300, top: 90});
      cm.addListener("ready", () =>{
        cm.bindKey("application.title",label1,"value", true);
      }); 



      /*
       * application startup
       */
      (async ()=>{
        
        await setup.checkServerSetup();
        await setup.authenticate();
        await setup.loadConfig();
        await setup.loadUserdata();
      })();
    },

    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */

    /** @var {qx.bom.storage.Web} */
    __storage : null,

    /** @var {Object} */
    __clients : {},

    __url : null,

    __persistentStore : null,
    __datasourceStore : null,
    __itemView : null,
    __selectedIds : null,
    __blocker : null,

   /*
    ---------------------------------------------------------------------------
     COMPONENTS
    ---------------------------------------------------------------------------
    */

    getAccessManager : function(){
      return bibliograph.AccessManager.getInstance();
    },

    getPermissionManager : function(){
      return this.getAccessManager().getPermissionManager();
    },

    getConfigManager : function(){
      return bibliograph.ConfigManager.getInstance();
    },

    /**
     * Returns a session storage object
     * @return {qx.bom.storage.Web}
     */
    getStorage : function(){
      if ( ! this.__storage ){
        this.__storage = new qx.bom.Storage.getSession();
      }
      return this.__storage;  
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
      if( this.__url ) return this.__url;

      let serverUrl = qx.core.Environment.get("bibliograph.serverUrl");
      if (!serverUrl) {
        dialog.Dialog.error(
          this.tr("Missing server address. Please contact administrator.")
        );
        throw new Error("No server address set.");
      }
      if( ! serverUrl.startsWith("http") ){
        // assume relative path 
        let href = document.location.href;
        serverUrl = qx.util.Uri.getAbsolute( serverUrl );
      }
      this.info("Server Url is " + serverUrl);
      this.__url = serverUrl;
      return serverUrl;
    },    
    
    /**
     * Returns a jsonrpc client object with the current auth token already set
     * @param {String} service The name of the service to get the client for
     * @return {bibliograph.io.JsonRpcClient}
     */
    getRpcClient : function(service){
      qx.core.Assert.assert(!!service, "Service parameter cannot be empty");
      qx.util.Validate.checkString(service, "Service parameter must be a string");
      if( ! this.__clients[service] ){
        this.__clients[service] = new bibliograph.io.JsonRpcClient(this.getServerUrl() + service );
      }
      let client = this.__clients[service];
      client.setToken( this.getAccessManager().getToken() );
      return client;
    },

    /**
     * Returns a promise that resolves when a message of that name has
     * been dispatched.
     * @param {String} message The name of the message
     * @return {Promise<true>}
     */
    resolveOnMessage: function( message ){
      let bus = qx.event.message.Bus;
      return new Promise((resolve,reject)=>{
        bus.subscribe(message,function(){
          bus.unsubscribe(message);
          resolve();
        });
      }) 
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
      return qx.core.Environment.get("bibliograph.version");
    },
    
    /**
     * Copyright notice
     * @return {String}
     */
    getCopyright : function() {
      var year = (new Date).getFullYear();
      return "2003-" + year + " (c) Christian Boulanger";
    },        

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
    oldmain : function()
    {
 
     

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
      this.setPluginManager( new bibliograph.PluginManager() );


      /*
       * rpc endpoint and timeout
       */
      this.getRpcManager().setServerUrl(this.getServerUrl());
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
      this.info("Start setup...");
      qx.event.message.Bus.getInstance().subscribe("application.reload", function(e)
      {
        window.location.reload();
      }, this); 
      this.showPopup(this.getSplashMessage(this.tr("Setting up application...")));
      qx.event.message.Bus.getInstance().subscribe("bibliograph.setup.done", this._setupDone, this);
      this.getRpcManager().execute("bibliograph.setup", "setup", []);
    },

    _setupDone : function()
    {
      this.info("Setup done.");
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
      var bus = qx.event.message.Bus.getInstance();

      /*
       * remotely log to the browser console
       */
      bus.subscribe("console.log", function(e)
      {
        console.log(e.getData());
      }, this);

      /*
       * server message to force logout the user
       */
      bus.subscribe("client.logout", function(e)
      {
        this.logout();
      }, this);

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
       * used by the bibliograph.export.exportReferencesHandleDialogData
       */
      bus.subscribe("window.location.replace", function(e)
      {
        var data = e.getData();
        window.location.replace(data.url);
      }, this);
      
      /*
       * reload the main list view
       */
      bus.subscribe("mainListView.reload", function(e)
      {
        var data = e.getData();
        if (data.datasource !== this.getDatasource())return;
        this.getWidgetById("bibliograph/mainListView").reload();
      }, this);

      /*
       * show the login dialog
       */
      bus.subscribe("loginDialog.show", function()
      {
        this.getWidgetById("bibliograph/loginDialog").show();
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
        //this.__persistentStore = new persist.Store('Bibliograph', 1);
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
     * Applies the datasource property
     */
    _applyDatasource : function(value, old)
    {
      var stateMgr = this.getStateManager();

      /*
       * reset all states that have been connected
       * with the datasource if a previous datasource
       * has been loaded
       * @todo hide search box when no datasource is selected
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
        // set the application state
        stateMgr.setState("datasource", value);

        // load datasource model from server
        this.showPopup(this.tr("Loading datasource information ..."));
        this.getRpcManager().execute(
            "bibliograph.model", "getDatasourceModelData", [value],
            function(data) {
              this.hidePopup();
              var model = qx.data.marshal.Json.createModel(data);
              this.setDatasourceModel(model);
              this.setModelType(model.getTableModelType());
            }, this);
      }
      else
      {
        stateMgr.removeState("datasource");
      }
    },

    /**
     * @todo rename to application title
     */
    _applyDatasourceLabel : function(value, old)
    {
      if (!value) {
        value = this.getConfigManager().getKey("application.title");
      }
      window.document.title = value;
      this.getWidgetById("bibliograph/datasource-name").setValue('<span style="font-size:1.2em;font-weight:bold">' + value + '</spsn>');
    },

    /**
     * Applies the folderId property
     */
    _applyFolderId : function(value, old)
    {
      var stmgr = this.getStateManager()
      stmgr.setState("modelId", 0);
      if (parseInt(value))
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
     * Applies the query property
     * @todo Searchbox widget should observe query state instead of
     * query state binding the searchbox.
     */
    _applyQuery : function(value, old)
    {
      this.getStateManager().setState("query", value);
      if (value && this.getDatasource()) {
        this.getWidgetById("bibliograph/searchbox").setValue(value);
      } else {
        this.getStateManager().removeState("query");
        this.getWidgetById("bibliograph/searchbox").setValue("");
      }
    },

    /**
     * Applies the modelType property
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
     * Applies the modelId property
     */
    _applyModelId : function(value, old) {
      if (parseInt(value)) {
        this.getStateManager().setState("modelId", value);
      } else {
        this.getStateManager().removeState("modelId");
      }
    },

    /**
     * Applies the itemView property
     */
    _applyItemView : function(value, old) {
      if (value) {
        this.getStateManager().setState("itemView", value);
      } else {
        this.getStateManager().removeState("itemView");
      }
    },

    /**
     * Applies the selectedIds property. Does nothing.
     */
    _applySelectedIds : function(value, old) {
      //
    },

    /**
     * Applies the theme property. Does nothing.
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
          this.getWidgetById("bibliograph/loginDialog").show();
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
     * called when user clicks on the "forgot password?" button
     */
    forgotPassword : function()
    {
      this.showPopup(this.tr("Please wait ..."));
      this.getRpcManager().execute("bibliograph.actool", "resetPasswordDialog", [], function() {
        this.hidePopup();
      }, this);
    },


    /**
     * Log out the current user
     *
     * @return {void}
     */
    logout : function()
    {
      // notify listeners
      qx.event.message.Bus.dispatchByName("logout");

      // remove state
      this.setFolderId(null);
      this.setModelId(null);

      // log out on server
      this.showPopup( this.tr("Logging out ...") );
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
      
try{      
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
          var dsWin = this.getWidgetById("bibliograph/datasourceWindow");
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
      this.getWidgetById("bibliograph/datasourceButton").setVisibility(datasourceCount > 1 ? "visible" : "excluded");
}
catch(e)
{
  console.log(e);
}
    },

    /*
    ---------------------------------------------------------------------------
       Toolbar commands
    ---------------------------------------------------------------------------
    */

    /**
     * opens a window with the online help
     */
    showHelpWindow : function(topic) {
      var url = this.getRpcManager().getServerUrl() +
          "?sessionId=" + this.getSessionManager().getSessionId() +
          "&service=bibliograph.main&method=getOnlineHelpUrl&params=" + (topic||"home");
      this.__helpWindow = window.open(url,"bibliograph-help-window");
      if (!this.__helpWindow) {
        dialog.Dialog.alert(this.tr("Cannot open window. Please disable the popup-blocker of your browser for this website."));
      }
      this.__helpWindow.focus();
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
      this.getWidgetById("bibliograph/aboutWindow").open();
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