/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2010 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/**
 * This object manages authentication and authorization issues.
 */
qx.Class.define("bibliograph.AccessManager",
{
  extend : qx.core.Object,
  type : "singleton",

  /**
   * Constructor
   */
  construct : function() {
    this.base(arguments);
  },

  statics : {
    messages : {
      LOGIN  : "user.loggedin",
      LOGOUT : "user.loggedout",
      LOGOFF : "user.logoff"
    }
  },


  properties : {
    
     /**
      * The data store used for authentication.
      */
     store :
     {
       check : "qcl.data.store.JsonRpcStore",
       nullable : true,
       event    : "changeStore"
     },
     
    /**
     * Flag to indicate if we have an authenticated user
     */
    authenticatedUser :
    {
      check    : "Boolean",
      init     : false,
      event    : "changeAuthenticatedUser"
    }
  },
  
  members :
  {
    /*
    ---------------------------------------------------------------------------
       PRIVATES
    ---------------------------------------------------------------------------
    */

    _authenticationSetup : false,
    
   /*
    ---------------------------------------------------------------------------
       COMPONENTS
    ---------------------------------------------------------------------------
    */
  
    /**
     * @return {qcl.access.PermissionManager}
     */
    getPermissionManager : function() {
      return qcl.access.PermissionManager.getInstance();
    },
  
    /**
     * @return {qcl.access.UserManager}
     */
    getUserManager : function() {
      return qcl.access.UserManager.getInstance();
    },

   /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * Retrives the current session id from the session storage
     * @return {String}
     */
    getSessionId : function() {
      return this.getApplication().getStorage().getItem("sessionId");
    },

    /**
     * Saves the current session id in the session storage.
     * @param {String} sessionId
     */
    setSessionId : function(sessionId) {
      return this.getApplication().getStorage().setItem("sessionId", sessionId);
    },

    /**
     * Retrives the current auth token from the session storage
     * @return {String}
     */
    getToken : function() {
      return this.getApplication().getStorage().getItem("token");
    },

    /**
     * Saves the current auth token in the session storage.
     * @param {String} token
     */
    setToken : function(token) {
      this.getApplication().getStorage().setItem("token", token);
      qx.event.message.Bus.dispatchByName("qcl.token.change", token);
    },

    /**
     * Setup the manager
     * @return {bibliograph.AccessManager} Returns itself
     */
    init : function() {
      // check if setup is already done
      if (this._authenticationSetup) {
        this.warn("Authentication already set up");
        return this;
      }
      this._authenticationSetup = true;

      // store for authenticated user
      this.setStore(new qcl.data.store.JsonRpcStore("access"));
      
      // bind the authentication stores data model to the user managers data model
      this.getStore().bind("model", this.getUserManager(), "model");

      // bind the userdata anonymous property
      this.getStore().bind("model.anonymous", this, "authenticatedUser", {
        converter: v => !v
      });

      // load userdata
      this.getStore().setLoadMethod("userdata");
      return this;
    },

    /**
     * Loads the permissions of the active user from the server
     */
    load : async function() {
      this.getPermissionManager().getAll().map(permission => permission.setGranted(false));
      await this.getStore().load("userdata", [this.getApplication().getDatasource()]);
    },

    /**
     * Handles the response of the access/authenticate server action:
     * If authentication is successful, reload config and user data.
     *
     * @param response
     */
    __handleAuthenticationResponse : async function(response) {
      let { message, token, sessionId, error } = response;
      if (error) {
        this.warn(error);
        return response;
      }
      // load user config & userdata
      this.info(message);
      this.setToken(token);
      this.setSessionId(sessionId);
      await this.getApplication().getConfigManager().load();
      await this.load();
      // notify subscribers
      qx.event.message.Bus.dispatchByName(bibliograph.AccessManager.messages.LOGIN, this.getUserManager().getActiveUser());
      return response;
    },

    /**
     * Authenticate anomymously with the server.
     * @return {Promise<Object>}
     */
    guestLogin : async function() {
      this.info("Logging in as a guest...");
      let client = this.getApplication().getRpcClient("access");
      let response = await client.send("authenticate", []);
      return await this.__handleAuthenticationResponse(response);
    },

    /**
     * Authenticates a user with the given password.
     *
     * This is done in the following steps:
     *  - Client request the authentication method, passing the username
     *  - Server responds with either "plaintext", in which case the password
     *    is sent plain text (requires https connection), or with "hashed".
     *  - If a hashed password is requested, server also sends a nounce
     *    consisting of a random part and the salt
     *    used to hash the password in the database, concatenated by "|"
     *  - Client hashes the password with the following algorithm:
     *    sha1( random salt + sha1( storedSalt + password )
     *  - Client returns hash for authentication
     *
     * @param username {String}
     * @param password {String}
     * @param authOnly {Boolean}
     * @return {Promise<Object>}
     */
    authenticate : async function(username, password, authOnly) {
      let sha1 = qcl.crypto.Sha1.hex_sha1.bind(qcl.crypto.Sha1);
      let client = this.getApplication().getRpcClient("access");
      let challenge = await client.send("challenge", [username]);
      if (challenge.method === "hashed") {
        let nounce = challenge.nounce.split(/\|/);
          let randSalt = nounce[0];
          let storedSalt = nounce[1];
          let serverHash = sha1(storedSalt + password);
        password = sha1(randSalt + serverHash);
      }
      let response = await client.send("authenticate", [username, password]);
      if (authOnly) {
        return response;
      }
      return await this.__handleAuthenticationResponse(response);
    },
    
    /**
     * Returns the active user object
     * @return {qcl.access.User}
     */
    getActiveUser : function() {
      return this.getUserManager().getActiveUser();
    },


    /**
     * Logs out the current user
     * @return {Promise<void>}
     */
    logout : async function() {
      qx.event.message.Bus.dispatch(new qx.event.message.Message(bibliograph.AccessManager.messages.LOGOFF, true));
      let app = this.getApplication();
      // reset datasource
      app.setDatasource(null);
      qx.event.message.Bus.dispatch(new qx.event.message.Message(bibliograph.AccessManager.messages.LOGOUT));
      // notify server
      await app.getRpcClient("access").request("logout");
      // re-login as guest
      await this.guestLogin();
      // load config and userdata
      await this.load();
      await app.getConfigManager().load();
    }
  }
});
