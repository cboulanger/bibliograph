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
qx.Class.define("bibliograph.rbac.AccessManager",
{
  extend : qx.core.Object,  
  type : "singleton",
 
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

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
      * The user manager
      */
     userManager :
     {
       check : "qx.core.Object", //@todo: interface
       nullable : true,
       event    : "changeUserManager"
     },
     
     /**
      * The permission manager
      */
     permissionManager :
     {
       check : "qx.core.Object", //@todo: interface
       nullable : true,
       event    : "changePermissionManager"
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

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */  

  construct : function()
  {
    this.base(arguments);
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
       PRIVATES
    ---------------------------------------------------------------------------
    */    

    _authenticationSetup : false,
    __sessionId : null,
    __activeUser : null,
           

   /*
    ---------------------------------------------------------------------------
       API METHODS 
    ---------------------------------------------------------------------------
    */       
    
    /**
     * Returns the session id 
     */
    getSessionId : function()
    {
      return this.__sessionId;
    },
    
    /**
     * Sets the session id 
     */
    setSessionId : function(sessionId)
    {
      this.__sessionId = sessionId;
    },

    /**
     * Setup the authentication mechanism.
     * @param authStore {qcl.data.store.JsonRpc}
     */
    init : function( service )
    {
     
      // check if setup is already done
      if ( this._authenticationSetup ) {
        this.warn("Authentication already set up");
        return;
      }
      this._authenticationSetup = true;      
      
      this.setStore( new bibliograph.io.JsonRpcStore("access") );
      
      // bind the authentication stores data model to the user managers data model
      this.getStore().bind("model", bibliograph.rbac.UserManager.getInstance(), "model");
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
     * @param callback {Function}
     * @param context {Object} The context in which the callback is executed
     * @return {void}
     */
    authenticate : function( username, password, callback, context )
    {
      var sha1 = qcl.crypto.Sha1.hex_sha1.bind(qcl.crypto.Sha1);
      this.getStore().execute("challenge", [username], function(challenge){
        if( challenge.method == "hashed" )
        {
          var nounce   = challenge.nounce.split(/\|/), 
            randSalt   = nounce[0], 
            storedSalt = nounce[1],
            serverHash = sha1( storedSalt + password );
          password = sha1( randSalt + serverHash );
        }
        this.getStore().load("authenticate",[ username, password ], callback, context );        
      }, this);
    },
    
    /**
     * Shorthand method to return active user
     * @return {qcl.access.User}
     */
    getActiveUser : function()
    {
      return this.__activeUser;
    },

    setActiveUser : function( activeUser )
    {
      this.__activeUser = activeUser; 
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
       
    /**
     * Log out current user on the server
     * @param callback {function|undefined} optional callback that is called
     * when logout request returns from server.
     * @param context {object|undefined} Optional context for callback function
     * @return {void}
     */
    logout : function( callback, context )
    {
      qx.event.message.Bus.dispatch( new qx.event.message.Message("logout", true ) );
      this.getStore().load("logout", null, callback, context );
    }
  }
});