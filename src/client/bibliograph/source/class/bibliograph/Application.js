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
  include : [ bibliograph.MApplicationState, qcl.ui.MLoadingPopup ],

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
     */
    main : async function()
    {
      this.base(arguments);

      if (qx.core.Environment.get("qx.debug")) {
        qx.log.appender.Native;
      }
  
      // application startup
      await bibliograph.Setup.getInstance().boot();
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
      var year = (new Date).getFullYear();
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
    __clients : {},
    /** {qx.ui.core.Blocker} */
    __blocker : null,
    /** @var {String} */
    __url : null,
    /** @var {Object} */
    __widgets : {},

   /*
    ---------------------------------------------------------------------------
     COMPONENTS
    ---------------------------------------------------------------------------
    */

    /**
     * @return {bibliograph.AccessManager}
     */
    getAccessManager : function(){
      return bibliograph.AccessManager.getInstance();
    },

    /**
     * @return {qcl.access.PermissionManager}
     */
    getPermissionManager : function(){
      return this.getAccessManager().getPermissionManager();
    },

    /**
     * @return {bibliograph.ConfigManager}
     */
    getConfigManager : function(){
      return bibliograph.ConfigManager.getInstance();
    },

    /**
     * @return {qcl.application.StateManager}
     */
    getStateManager : function(){
      return qcl.application.StateManager.getInstance();
    },    

    /**
     * @return {qx.bom.storage.Web}
     */
    getStorage : function(){
      if ( ! this.__storage ){
        this.__storage = new qx.bom.Storage.getSession();
      }
      return this.__storage;  
    },

    /**
     * @return {qx.ui.core.Blocker}
     */
    getBlocker : function (){
      return this.__blocker;
    },

    /**
     * @return {bibliograph.Commands}
     */
    getCommands : function(){
      return bibliograph.Commands.getInstance();
    },

    /**
     * @return {bibliograph.store.Datasources}
     */
    getDatasourceStore : function(){
      return bibliograph.store.Datasources.getInstance();
    },

    /*
    ---------------------------------------------------------------------------
     COMMANDS
    ---------------------------------------------------------------------------
    */ 

    /**
     * Run command by dispatching a message
     */
    cmd : function( command, value ){
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
      if( this.__url ) return this.__url;

      let serverUrl = qx.core.Environment.get("app.serverUrl");
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
     * @return {qcl.io.JsonRpcClient}
     */
    getRpcClient : function(service){
      qx.core.Assert.assert(!!service, "Service parameter cannot be empty");
      qx.util.Validate.checkString(service, "Service parameter must be a string");
      if( ! this.__clients[service] ){
        this.__clients[service] = new qcl.io.JsonRpcClient(this.getServerUrl() + service );
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
       WIDGET ID
    ---------------------------------------------------------------------------
    */

    /**
     * Store a reference to a widget linked to its id.
     * @param id {String}
     * @param widget {qx.ui.core.Widget}
     */
    setWidgetById : function(id,widget)
    {
      this.__widgets[id] = widget;
    },
    
    /**
     * gets a reference to a widget by its id
     * @param id {String}
     * @return {qx.ui.core.Widget} The widget with the given id
     */
    getWidgetById : function(id)
    {
      return this.__widgets[id];
    },

   
  }
});