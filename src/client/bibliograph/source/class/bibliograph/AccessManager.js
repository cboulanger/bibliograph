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
/*global qx qcl*/

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
  construct : function()
  {
    this.base(arguments);
  },
 
  properties : {
    
     /**
      * The data store used for authentication.
      */
     store :
     {
       check : "bibliograph.io.JsonRpcStore",
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

    getPermissionManager : function(){
      return qcl.access.PermissionManager.getInstance();
    },

    getUserManager : function(){
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
    getSessionId : function(){
      return this.getApplication().getStorage().getItem('sessionId');
    },

    /**
     * Saves the current session id in the session storage.
     * @param {String} sessionId 
     */
    setSessionId : function(sessionId){
      return this.getApplication().getStorage().setItem('sessionId', sessionId);
    },

    /**
     * Retrives the current auth token from the session storage
     * @return {String}
     */
    getToken : function(){
      return this.getApplication().getStorage().getItem('token');
    },

    /**
     * Saves the current auth token in the session storage.
     * @param {String} token 
     */
    setToken : function(token){
      this.getApplication().getStorage().setItem('token', token);
      qx.event.message.Bus.dispatchByName("bibliograph.token.change",token);
    },        

    /**
     * Setup the manager
     * @return {bibliograph.rbac.AccessManager} Returns itself
     */
    init : function( )
    {
     
      // check if setup is already done
      if ( this._authenticationSetup ) {
        this.warn("Authentication already set up");
        return;
      }
      this._authenticationSetup = true;      

      // store for authenticated user
      this.setStore( new bibliograph.io.JsonRpcStore("access") );
      
      // bind the authentication stores data model to the user managers data model
      this.getStore().bind("model", this.getUserManager(), "model");

      // load userdata
      this.getStore().setLoadMethod("userdata");
      return this;
    }, 

    /**
     * Loads the permissions of the active user from the server
     */
    load : async function(){
      await this.getStore().load();
    },

    /**
     * Authenticate anomymously with the server.
     * @return {Promise<Object>}
     */
    authenticateAsGuest : async function()
    {
      this.info("Authenticating anonymously with server...");
      let client = this.getApplication().getRpcClient("access");
      let response = await client.send("authenticate",[]);
      let { message, token, sessionId } = response; 
      this.info(message);
      this.setToken(token);
      this.setSessionId(sessionId);
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
     * @return {Promise<Object>}
     */
    authenticate : async function( username, password )
    {
      var sha1 = qcl.crypto.Sha1.hex_sha1.bind(qcl.crypto.Sha1);
      let challenge = await this.getStore().execute("challenge", [username]);
      if( challenge.method == "hashed" ) {
        var nounce   = challenge.nounce.split(/\|/), 
          randSalt   = nounce[0], 
          storedSalt = nounce[1],
          serverHash = sha1( storedSalt + password );
        password = sha1( randSalt + serverHash );
      }
      return this.getStore().load("authenticate",[ username, password ]);  
    },
    
    /**
     * Returns the active user object
     * @return {qcl.access.User}
     */
    getActiveUser : function()
    {
      return this.getUserManager().getActiveUser();
    },
    
   /**
    * Shorthand method to return a permission object by name
    * @return {qcl.access.Permission}
    */    
    getPermission : function( name )
    {
      return this.getPermissionManager().create( name );   
    },
    
    /**
     * Shorthand method to return a permission state
     * @return {Boolean}
     */    
     getPermissionState : function( name )
     {
       return this.getPermissionManager().create( name ).getState();   
     },    

    /**
     * Shorthand method to update a permission
     * @return {void}
     */        
    updatePermission : function( name )
    {
      this.getPermission( name ).update();
    },
  }
});