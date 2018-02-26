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
  include : [qcl.ui.MLoadingPopup, qx.locale.MTranslation],

  members: {

    /**
     * Dummy method to mark dynamically generated messages for translation
     */
    markForTranslation : function()
    {
      this.tr("No connection to server.");
      this.tr("Loading folder data ...");
    },

    /*
    ---------------------------------------------------------------------------
      BOOT
    ---------------------------------------------------------------------------
    */

    boot : async function(){

      //  Mixes `getApplication()` into all qooxdoo objects
      qx.Class.include( qx.core.Object, qcl.application.MGetApplication );
      // Mixes `widgetId` property into all qooxdoo objects
      qx.Class.include( qx.core.Object, qcl.application.MWidgetId );

      // initialize application commands
      bibliograph.Commands.getInstance();

      // datasource store, configures itself and responds to authentication events
      bibliograph.store.Datasources.getInstance();
      
      let app = this.getApplication();
      
      // save state from querystring
      this.saveApplicationState();

      // create main UI Layout
      bibliograph.ui.Windows.getInstance().create();
      bibliograph.ui.MainLayout.getInstance().create();
      
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
      qcl.ui.dialog.Dialog.allowServerDialogs(true);

      // server setup
      this.showPopup(this.getSplashMessage(this.tr("Setting up application...")));
      await this.checkServerSetup();
      
      // authenticate
      this.showPopup(this.getSplashMessage(this.tr("Connecting with server...")));    
      await this.authenticate();
      qx.event.message.Bus.dispatch(new qx.event.message.Message("connected"));

      // load config & permissions
      this.showPopup(this.getSplashMessage(this.tr("Loading configuration ...")));
      await this.loadConfig();
      await this.loadUserdata();

      // notify about user login 
      qx.event.message.Bus.dispatchByName(
        "user.loggedin", 
        // @todo - we need a shortcut for this
        this.getApplication().getAccessManager().getUserManager().getActiveUser()
      );

      // load plugins
      this.loadPlugins();

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
      
      // message transport
      //this.startPolling();

      qx.event.message.Bus.dispatchByName("bibliograph.setup.completed");
    },

    /*
    ---------------------------------------------------------------------------
      SETUP METHODS
    ---------------------------------------------------------------------------
    */  
    
    
    /** 
     * save some intial application states which would otherwise be overwritten
     */
    saveApplicationState : function(){
      let app = this.getApplication();
      this.__itemView = app.getStateManager().getState("itemView");
      this.__folderId = app.getStateManager().getState("folderId");
      this.__query    = app.getStateManager().getState("query");
      this.__modelId  = app.getStateManager().getState("modelId");
    },

    createBlocker : function(){
      let app = this.getApplication();
      let blocker = new qx.ui.core.Blocker(app.getRoot());
      blocker.setOpacity( 0.5 );
      blocker.setColor( "black" );
      app.__blocker = blocker;
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
     * This will initiate server setup. When done, server will send a 
     * "bibliograph.setup.done" message.
     */
    checkServerSetup : async function(){
      // 'await' omitted in the next line, since the message is what we're waiting for
      // this allows the server to interact with the user before setup is completed
      // (i.e. through Wizard or Dialogs)
      this.getApplication().getRpcClient("setup").send("setup");
      await this.getApplication().resolveOnMessage("bibliograph.setup.done");
      this.info("Server setup done.");
    },

    /**
     * Unless we have a token in the session storage, authenticate
     * anomymously with the server.
     */
    authenticate : async function(){
      let am = bibliograph.AccessManager.getInstance();
      let token = am.getToken();
      let client = this.getApplication().getRpcClient("access");
      if( ! token ) {
      this.info("Authenticating with server...");
      let response = await client.send("authenticate",[]);
      if( ! response ) {
        return this.error("Cannot authenticate with server: " + client.getErrorMessage() );
      }
      let { message, token, sessionId } = response; 
      this.info(message);
      
      am.setToken(token);
      am.setSessionId(sessionId);
      this.info("Acquired access token.");
      } else {
      this.info("Got access token from session storage" );
      }
    },

    loadConfig : async function(){
      this.info("Loading config values...");
      await this.getApplication().getConfigManager().init().load();
      this.info("Config values loaded.");
    },

    loadUserdata : async function(){
      this.info("Loading userdata...");
      await this.getApplication().getAccessManager().init().load();
      this.info("Userdata loaded.");
    },

    /**
     * Loads the plugins
     */
    loadPlugins : async function()
    {
      this.warn("Plugins not implemented, skipping...");
      return; 
    },  

    /**
     * Initialize  subscribers for server messages
     */
    initSubscribers : function()
    {
      var bus = qx.event.message.Bus.getInstance();

      // listen to reload event
      bus.subscribe("application.reload", function(e){
        window.location.reload();
      }, this);       

      // remotely log to the browser console
      bus.subscribe("console.log", function(e){
        console.log(e.getData());
      }, this);

      // server message to force logout the user
      bus.subscribe("client.logout", function(e){
        this.logout();
      }, this);

      // server message to set model type and id
      bus.subscribe("bibliograph.setModel", e => {
        var data = e.getData();
        var app = this.getApplication();
        if ( data.datasource == app.getDatasource()) {
          app.setModelType(data.modelType);
          app.setModelId(data.modelId);
        }
      });

      // used by the bibliograph.export.exportReferencesHandleDialogData
      bus.subscribe("window.location.replace", function(e){
        var data = e.getData();
        window.location.replace(data.url);
      }, this);
      
      // reload the main list view
      bus.subscribe("mainListView.reload", function(e){
        var data = e.getData();
        var app = this.getApplication();
        if (data.datasource !== app.getDatasource())return;
        app.getWidgetById("bibliograph/mainListView").reload();
      }, this);

      // show the login dialog
      bus.subscribe("loginDialog.show", function(){
        this.getApplication().getWidgetById("bibliograph/loginDialog").show();
      }, this);
    },  

    /**
     * Restores the state of the origininal URL 
     */
    restoreApplicationState : function()
    {
      let app = this.getApplication();
      if (this.__itemView) {
        app.setItemView(this.__itemView);
      }
      if (this.__selectedIds) {
        var selectedIds = [];
        this.__selectedIds.split(",").forEach(function(id) {
          id = parseInt(id);
          if (id && !isNaN(id))selectedIds.push(id);
        }, this);
      }
      if (this.__folderId && !isNaN(parseInt(this.__folderId))) {
        this.info("Restoring folder id: " + this.__folderId);
        app.setFolderId(parseInt(this.__folderId))
      } else if (this.__query) {
        this.info("Restoring query: " + this.__query);
        app.setQuery(this.__query);
      }
      if (this.__modelId && !isNaN(parseInt(this.__modelId))) {
        this.info("Restoring model id: " + this.__modelId);
        app.setModelId(parseInt(this.__modelId))
      }
    },

    /**
     * Start polling service to get messages when no server action
     * happens
     */
    startPolling : async function() {
      let delayInMs = await this.getApplication().getRpcClient("message").send("getMessages");
      if( delayInMs ){
        qx.lang.Function.delay(this.startPolling,delayInMs,this);
      }
    },

    /** Applies the foo property */
    _applyFoo: function(value, old) {
      //
    }
  }
});
