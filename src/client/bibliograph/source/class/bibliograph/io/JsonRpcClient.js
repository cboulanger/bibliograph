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
 * A JSONRPC 2.0 client. This uses a fork of the NPM package "raptor-client"
 * under the hood. See https://github.com/cboulanger/raptor-client
 */
qx.Class.define("bibliograph.io.JsonRpcClient", {
  extend: qx.core.Object,

  /**
   * Create a new instance  
   * @param {String} url 
   *    The url of the endpoint of the JSONRPC service
   * @param {String} token
   *    The authorizatio token which will be sent in the Authorization header as
   *    "Bearer <token>"
   */
  construct: function(url) {
    qx.util.Validate.checkUrl(url);
    this.__client = window.raptor( url );
  },

  properties: {

    /** 
     * If the last request has resulted in an error, it is stored here.
     * The error object takes the form { message, code } */
    error: {
      nullable: true,
      check: "Object",
      apply: "_applyError"
    },

    /** 
     * Set authentication token
     * */
    token: {
      nullable: true,
      check: "String",
      apply: "_applyToken"
    },

  },

  events: {
    /** Fired when something happens */
    changeSituation: "qx.event.type.Data"
  },

  members: {
    /** The client object */
    __client: null,

    /** The url template string */
    __url: null,

    /**
     * Sends a jsonrpc request to the server. An error will be caught
     * and displayed in a dialog. In this case, the returned promis 
     * resolves to null
     * @param method {String} The service method
     * @param payload {*} The paylooad
     * @return {Promise<*>}
     */
    send : async function( method, payload ){
      this.setError(null);
      try{
        return await this.__client.send( method, payload);
      } catch( e ) {
        this.setError(e);                
        dialog.Dialog.error( this.getErrorMessage() ); // @todo use one instance!
      }
    },

    /**
     * Sends a jsonrpc notification to the server. An error will be caught
     * and displayed in a dialog. In this case, the returned promis 
     * resolves to null
     * @param method {String} The service method
     * @param payload {*} The paylooad
     * @return {Promise<void>}
     */
     notify : async function( method, payload ){
      this.setError(null); 
      try {
         return this.__client.notify( method, payload);
      } catch( e ) {
        this.setError( {
          code : e.rpcCode,
          message : e.rpcData
        });
        dialog.Dialog.error( e.rpcData ); // @todo use one instance!
        return null;
      }
    },    

    /** applys the error property */
    _applyError : function( value, old ){
      if( value ){
        console.warn( value );
      }
    },

    /** applys the token property */
    _applyToken : function( value, old ){
      this.__client.setAuthToken(value);
    },

    /**
     * Returns a descriptive message of the last error, if available
     * @return {String}
     */
    getErrorMessage(){
      let e = this.getError()
      if( ! e ){
        return undefined;
      }
      if ( typeof e.message == "string" ){
        return e.message.substring(0,100);
      }
      return "Unknown Error";
    }
  },

  /**
   * Destructor
   */
  destruct: function() {
    delete this.__client;
  }
});
