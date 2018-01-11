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
     * Returns the URL to the JSONRPC server
     * @return {String}
     */
    getServerUrl: function() {
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
      console.info("Server Url is " + serverUrl);
      return serverUrl;
    },

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

    /** @var {bibliograph.io.JsonRpcClient} */
    __client : null,

    /**
     * Returns a jsonrpc client object with the current auth token already set
     * @return {bibliograph.io.JsonRpcClient}
     */
    getClient : function(){
      if( ! this.__client ){
        this.__client = new bibliograph.io.JsonRpcClient(this.getServerUrl());
      }
      this.__client.setToken(this.getToken());
      return this.__client;
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

    /**
     * Unless we have a token in the session storage, authenticate
     * anomymously with the server.
     */
    authenticate : async function(){
      let token = this.getToken();
      let client = this.getClient();
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

    

    /** Applies the foo property */
    _applyFoo: function(value, old) {
      //
    }
  }
});
