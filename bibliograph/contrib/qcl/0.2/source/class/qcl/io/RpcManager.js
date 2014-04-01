/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2014 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/* ************************************************************************
#require(qcl.application.*)
************************************************************************ */

/**
 * This object manages the json-rpc -based backend communication
 */
qx.Class.define("qcl.io.RpcManager",
{
  
  extend : qx.core.Object,
 
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties : 
  {
      
     /**
      * The RPC object that is shared by all methods that require access
      * to the backend.
      */
     rpcObject : 
     {
       check : "qx.io.remote.Rpc",
       nullable : true,
       event    : "changeRpcObject"
     },
     
     /**
      * The URL of the json-rpc server
      * @type 
      */
     serverUrl :
     {
        check : "String",
        nullable : false
     },
     
     /**
      * The JSONRPC service method that should be called when the application is
      * closed
      */
     serviceMethodOnTerminate :
     {
       check : "String",
       nullable : true
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
    
    /*
     * global rpc object to be reused by for all requests
     */
    if ( ! this.getRpcObject() )
    {
      this.setRpcObject( new qx.io.remote.Rpc() );
    }
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
    _rpc : null,
    _appStore : null,
    
    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */ 
    
    /**
     * Applying the server url will automatically create a rpc object if it 
     * does not exist.
     */
    _applyServerUrl : function( url, old )
    {
      if ( ! this.getRpcObject() )
      {
        this.setRpcObject( new qx.io.remote.Rpc );
      }
      this.getRpcObject().setUrl(url);
    },
    
    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */     
        
    /**
     * Called when the page is closed and unregisteres stores on the server. 
     * If you want to have additional termination
     * action, define a _terminate method in your main application, which is 
     * called after at the end of this method.
     */
    terminate : function()
    {
      
      /*
       * execute terminate function on server
       */
      if ( this.getServiceMethodOnTerminate() && this.getRpcObject() )
      {
        this.execute( this.getServiceMethodOnTerminate() );
      }
       
      /*
       * call application function
       */
       if ( typeof this._terminate == "function" )
       {
         this._terminate();
       }
    },
   
    /** 
     * Executes a jsonrpc service method with the rpc object configured in the 
     * main application's constructor
     * @param serviceName {String}
     * @param serviceMethod {String}
     * @param params {Array} Parameters to send to the method
     * @param callback {Function} Callback function that is called with the data returned from the server
     * @param context {Object} The object context in which the callback function is executed
     * @return {void}
     */
    execute : function( serviceName, serviceMethod, params, callback, context )
    {
      
      /* 
       * create all-purpose json store
       */
      if ( ! this._appStore )
      {
        this._appStore = new qcl.data.store.JsonRpc( 
            null, null, null, null, this.getRpcObject() 
        ); 
      }
      
      this._appStore.setServiceName(serviceName);
      this._appStore.execute( serviceMethod, params, callback, context);
    },
    
    /**
     * Registers a store with the server
     * @param store {qcl.data.store.JsonRpc}
     */
    registerStore : function( store )
    {
      this.load("register",[ store.getStoreId() ],function(data){
        //this.info(data);
      }, this );  
    },
    
    /**
     * Unregisters a store from the server
     * @param store {qcl.data.store.JsonRpc}
     */
    unregisterStore : function( store )
    {
      this.load("unregister",[ store.getStoreId() ],function(data){
        //this.info(data);
      }, this );  
    }    
  }
});