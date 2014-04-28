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
qx.Class.define("qcl.access.AccessManager",
{
  
  extend : qx.core.Object,  
 
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
       check : "qcl.data.store.JsonRpc",
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
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */         
    _authenticationSetup : false,
    
    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */          

   /*
    ---------------------------------------------------------------------------
       API METHODS 
    ---------------------------------------------------------------------------
    */       
    
    /**
     * Returns the session id of the current application instance
     */
    getSessionId : function()
    {
      qx.core.Init.getApplication().getSessionManager().getSessionId();
    },
    
    /**
     * Setup the authentication mechanism.
     * @param authStore {qcl.data.store.JsonRpc}
     */
    init : function( service )
    {
     
      /*
       * check if setup is already done
       */
      if ( this._authenticationSetup )
      {
        this.error("Authentication already set up");
      }
      this._authenticationSetup = true;      
      
      /*
       * set user manager and auth store
       */
      if ( ! this.getUserManager() )
      {
        this.setUserManager( qcl.access.UserManager.getInstance() );
      }
      
      if ( ! this.getPermissionManager() )
      {
        this.setPermissionManager( qcl.access.PermissionManager.getInstance() );
      }
      
      if ( ! this.getStore() )
      {
        this.setStore(       
          new qcl.data.store.JsonRpc( null, service ) 
        );
      }

      /*
       * bind the authentication stores data model to the user managers data model
       */
      this.getStore().bind("model", this.getUserManager(), "model");

      /*
       * bind the session id propery of the auth store to the session
       * id of the application
       */
      this.getStore().bind("model.sessionId", qx.core.Init.getApplication().getSessionManager(), "sessionId" );
      
      /*
       * bind the authentication state to a local boolean
       * property, which will be false if there is no user logged 
       * in (initial state) or the user is anonymous (after the backend
       * has connected) and true when a real login has occurred 
       */
      this.getUserManager().bind("activeUser",this,"authenticatedUser",{
        converter : function(activeUser){ 
          return ( ! activeUser || activeUser.isAnonymous() ? false : true ) 
        }
      });
    }, 

    /**
     * Changes the service name of the store
     * @param service {String}
     */
    setService : function( service )
    {
      this.getStore().setServiceName( service );  
    },
    
    /**
     * Authenticate with session id, if any, otherwise with null to get
     * guest access, if allowed.
     * @param callback {function|undefined} optional callback that is called
     *   when logout request returns from server.
     * @param context {object|undefined} Optional context for callback function
     */    
    connect : function(callback,context)
    {
      this.getStore().load("authenticate",[ this.getSessionId() || null ], callback, context );
    },
    
    /**
     * Authenticates a user with the given password. Since this is done
     * asynchroneously, the method has no return value but uses a callback 
     * instead.
     * @param username {String}
     * @param password {String}
     * @param callback {Function}
     * @param context {Object} The context in which the callback is executed
     * @return {void}
     */
    authenticate : function( username, password, callback, context )
    {
       this.getStore().load("authenticate",[ username, password ], callback, context );
    },
    
    /**
     * Shorthand method to return active user
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