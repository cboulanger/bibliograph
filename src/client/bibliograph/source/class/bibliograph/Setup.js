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

  /**
   * The properties of the singleton
   */
  properties: {
    /** The foo property of the object */
    foo: {
      apply: "_applyFoo",
      nullable: true,
      check: "String",
      event: "changeFoo"
    }
  },

  /**
   * Declaration of events fired by class instances in addition
   * to the property change events
   */

  events: {
    /** Fired when something happens */
    changeSituation: "qx.event.type.Data"
  },

  /**
   * Methods and simple properties of the singleton
   */
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
       Storage
    ---------------------------------------------------------------------------
    */

    /** @var {qx.bom.storage.Web} */
    __storage : null,
    
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

    __url : null,

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

    /** @var {Object} */
    __clients : {},

    /**
     * Returns a jsonrpc client object with the current auth token already set
     * @param {String} service The name of the service to get the client for
     * @return {bibliograph.io.JsonRpcClient}
     */
    getClient : function(service){
      qx.core.Assert.assert(!!service, "Service parameter cannot be empty");
      qx.util.Validate.checkString(service, "Service parameter must be a string");
      if( ! this.__clients[service] ){
        this.__clients[service] = new bibliograph.io.JsonRpcClient(this.getServerUrl() + service );
      }
      let client = this.__clients[service];
      client.setToken(this.getToken());
      return client;
    },

    /**
     * Retrives the current auth token from the session storage
     * @return {String}
     */
    getToken : function(){
      return this.getStorage().getItem('token');
    },

    /**
     * Saves the current auth token in the session storage.
     * @param {String} token 
     */
    setToken : function(token){
      return this.getStorage().setItem('token', token);
    },    

    /**
     * Retrives the current session id from the session storage
     * @return {String}
     */
    getSessionId : function(){
      return this.getStorage().getItem('sessionId');
    },

    /**
     * Saves the current session id in the session storage.
     * @param {String} sessionId 
     */
    setSessionId : function(sessionId){
      return this.getStorage().setItem('sessionId', sessionId);
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
       HELPER METHODS
    ---------------------------------------------------------------------------
    */    

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
       SETUP METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * This will initiate server setup. When done, server will send a 
     * "bibliograph.setup.done" message.
     */
    checkServerSetup : async function(){
      this.getClient("setup").send("setup");
      await this.resolveOnMessage("bibliograph.setup.done");
      this.info("Server setup done.");
    },

    /**
     * Unless we have a token in the session storage, authenticate
     * anomymously with the server.
     */
    authenticate : async function(){
      let token = this.getToken();
      let client = this.getClient("access");
      if( ! token ) {
        this.info("Authenticating with server...");
        let response = await client.send("authenticate",[]);
        if( ! response ) {
          return this.error("Cannot authenticate with server: " + client.getErrorMessage() );
        }
        let { message, token, sessionId } = response; 
        this.info(message);
        this.setToken(token);
        this.setSessionId(sessionId);
        this.info("Acquired access token.");
      } else {
        this.info("Got access token from session storage" );
      }
    },

    loadConfig : async function(){
      this.info("Loading config values...");
      await bibliograph.ConfigManager.getInstance().init().load();
      this.info("Config values loaded.");
    },   

    /** Applies the foo property */
    _applyFoo: function(value, old) {
      //
    }
  }
});
