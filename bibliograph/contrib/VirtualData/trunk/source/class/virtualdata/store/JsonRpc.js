/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/

   Copyright:
     2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
   * Christian Boulanger (cboulanger)

 ************************************************************************ */

/**
 * 
 * The jsonrpc data store is responsible for fetching data from a json-rpc
 * server backend. The data will be processed by the marshaler that you supply as
 * third parameter in the constructor. If you don't supply one, the default
 * qx.data.marshal.Json will be used. 
 * 
 * 
 */
qx.Class.define("virtualdata.store.JsonRpc", 
{
  extend : qx.core.Object,

 /**  
  * @param url {String|null} The url of the jsonrpc service. If no url is
  *   given, the serverUrl property of the main application is used.
  * @param serviceName {String|null} The name of the service, i.e. "foo.bar"   
  * @param marshaler {Object|null} The marshaler to be used to create a model 
  *   from the data. If not provided, {@link qx.data.marshal.Json} is used and
  *   instantiated with the 'delegate' parameter as contstructor argument.
  * @param delegate {Object|null} The delegate containing one of the methods 
  *   specified in {@link qx.data.store.IStoreDelegate}. Ignored if a 
  *   custom marshaler is provided
  * @param rpc {qx.io.remote.Rpc|undefined} Optional qx.io.remote.Rpc object 
  *   that can be shared between stores. If not given, try and get object
  *   from application instance.
  */
  construct : function( url, serviceName, marshaler, delegate, rpc )
  {
    this.base(arguments);
  
    /*
     * set url, name and method of the service. If URL is null,
     * the server url is used
     */
    if ( url != null) 
    {
      this.setUrl(url);
    }
    
    if (serviceName != null) 
    {
      this.setServiceName( serviceName );
    }

  
    /* 
     * store the marshaler
     */
    if ( ! marshaler )
    {
      this.setMarshaler( new qx.data.marshal.Json(delegate) );
    }
    else
    {
      this.setMarshaler( marshaler );
    }
  
    /* 
     * use existing or create new JSON-RPC object
     */
    if ( rpc )
    {
      this._rpc = rpc;
    }
    else
    {
      this._rpc = new qx.io.remote.Rpc();
    }
  },

  /*
  *****************************************************************************
     EVENTS
  *****************************************************************************
  */  
  events : 
  {
    /**
    * Data event fired after the model has been created. The data will be the 
    * created model.
    */
    "loaded": "qx.event.type.Data",
    
    /**
    * The change event which will be fired if there is a change in the array.
    * The data contains a map with three key value pairs:
    * <li>start: The start index of the change.</li>
    * <li>end: The end index of the change.</li>
    * <li>type: The type of the change as a String. This can be 'add',  
    * 'remove' or 'order'</li>
    * <li>items: The items which has been changed (as a JavaScript array).</li>
    */
   "change" : "qx.event.type.Data",
   
   /**
    * Event signaling that the model data has changed
    */
   "changeBubble" : "qx.event.type.Data",
   
   /**
    * Error event
    */
   "error" : "qx.event.type.Data"
  },

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties : 
  {
    
    /**
     * The unique id of the store
     */
    storeId :
    {
      check : "String",
      nullable : true,
      init : null
    },
    
   /**
    * Property for holding the loaded model instance.
    */
    model : 
    {
      nullable: true,
      event: "changeModel"
    },
  
  
    /**
     * The url where the request should go to.
     */
    url : 
    {
      check: "String",
      nullable: true
    },
  
    /** 
     * The service class name on the server that provides data
     * for the store 
     */
    serviceName :
    {
      check : "String",
      event : "changeServiceName",
      nullable: true
    },
 
    /**
     * The name of the service method that is called on the server when the load()
     * method is called without arguments. Defaults to "load"
     */
    defaultLoadMethodName :
    {
      check : "String",
      init : "load"
    },
  
    /**
     * The marshaler used to create models from the json data
     */
    marshaler : {
      check: "Object",
      nullable: true
    },  
  
    /**  
     * Timeout for request 
     */
    timeout :
    {
      check : "Integer",
      init : 30000
    },
  
    /**  
     * If jsonrpc is used, whether cross-domain requests will be used  
     */
    allowCrossDomainRequests :
    {
      check : "Boolean",
      init : false
    },
    
    serverData :
    {
      nullable: true,
      init : null
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
    _request : null,
    _opaqueCallRef : null,
    _responseData : null,
    _rpc : null,
    _requestId : 0,
    _timerId : null,
    _dataEvents : [],
    _requestCounter : 0,
    
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
     * Returns a incrementing number to distinguish requests
     * dispatched by this store
     */
    getNextRequestId : function()
    {
      return this._requestId++;
    },

    /** 
     * Loads the data from a service method asynchroneously. 
     * @param serviceMethod {String} Method to call
     * @param params {Array} Array of arguments passed to the service method
     * @param finalCallback {function} The callback function that is called with
     *   the result of the request
     * @param context {Object} The context of the callback function     
     */
    load : function( serviceMethod, params, finalCallback, context  )
    {
      this._sendJsonRpcRequest( 
          serviceMethod||this.getDefaultLoadMethodName(), 
          params,
          finalCallback, 
          context,
          true
      );
    },
    
   /** 
    * Executes a service method without loading model data in response. 
    * @param serviceMethod {String} Method to call
    * @param params {Array} Array of arguments passed to the service method
    * @param finalCallback {function} The callback function that is called with
    *   the result of the request
    * @param context {Object} The context of the callback function     
    */
   execute : function( serviceMethod, params, finalCallback, context )
   {
     this._sendJsonRpcRequest( 
         serviceMethod||this.getServiceMethod(), 
         params,
         finalCallback, 
         context,
         false
     );
   },    
   
   /**
    * Aborts the current request
    */
   abort : function()
   {
     if ( this._opaqueCallRef )
     {
       this.getCurrentRequest().abort( this._opaqueCallRef );
     }
   },
    
    /*
    ---------------------------------------------------------------------------
       PRIVATE METHODS
    ---------------------------------------------------------------------------
    */     

    /** 
     * Configures the request object
     * @return {qx.io.remote.Rpc}
     */
    _configureRequest: function() 
    {
            
      /* 
       * configure request object
       */
      var rpc = this._rpc;
      rpc.setTimeout( this.getTimeout() );
      
      if ( this.getUrl() )
      {
        rpc.setUrl( this.getUrl() );
      }
      else
      {
        this.error("Cannot determine URL for request.");
      }
      
      rpc.setServiceName( this.getServiceName() );
      rpc.setCrossDomain( this.getAllowCrossDomainRequests() );
      rpc.setServerData( this.getServerData() );  

      return rpc;
    },


    /**  
     * Send json-rpc request with arguments
     * @param serviceMethod {String} Method to call
     * @param params {Array} Array of arguments passed to the service method
     * @param finalCallback {function} The callback function that is called with
     *   the result of the request
     * @param context {Object} The context of the callback function
     * @param createModel {Boolean}
     */
    _sendJsonRpcRequest : function( serviceMethod, params, finalCallback, context, createModel )
    {

      var rpc = this._configureRequest();
      
      /*
       * display a global cursor as long as a request is
       * underway
       * @todo replace this with a more sophistaced system
       */
      var app = qx.core.Init.getApplication();
      app.getRoot().setGlobalCursor("wait");
      if ( virtualdata._requestCounter === undefined  )
      {
        virtualdata._requestCounter = 0;
      }
      virtualdata._requestCounter++;

      /*
       * create callback function
       */
      var callbackFunc = qx.lang.Function.bind( function( data, ex, id ) 
      { 
        /*
         * decrement counter and reset cursor
         */
        if ( --virtualdata._requestCounter < 1)
        {
          app.getRoot().setGlobalCursor("default");
        }
        
        /*
         * save data for debugging etc.
         */
        this._responseData = data;

        /*
         * show that no request is underway
         */
        this._opaqueCallRef = null ;      

        /*
         * check for error
         */
        if ( ex === null ) 
        {  
          /* 
           * create the model if requested
           */
          if ( createModel )
          {
            /*
             * create the class
             */
            this.getMarshaler().toClass( data, true);
       
            /*
             * set the initial data
             */
            this.setModel( this.getMarshaler().toModel(data) );
             
            /*
             * fire 'loaded' event only if we creat a model
             */
            this.fireDataEvent( "loaded", this.getModel() );             
          }
           
          /*
           * final callback, only sent if request was successful
           */
          if ( typeof finalCallback == "function" )
          {
            finalCallback.call( context, data );
          }            
        } 
        else 
        {
          /* 
           * dispatch error event  
           */
          this.fireDataEvent( "error", ex );
          
          /*
           * handle event
           */
          this._handleError( ex, id );
          
          /*
           * notify that data has been received but failed
           */
          this.fireDataEvent("loaded",null);
        }
      }, this);

      /*
       * send request 
       */
      var params2 = [ callbackFunc, serviceMethod ].concat( params || [] );
      this._opaqueCallRef = rpc.callAsync.apply( rpc, params2 );

    },
    
    /**
     * Handles an error returned by the rpc object. Override
     * this method if you want to have a different error behavior.
     * @param ex {Object} Exception object
     * @param id {Integer} Request id
     */
    _handleError : function( ex, id )
    {
      /*
       * log warning to client log
       */
      this.warn ( "Async exception (#" + id + "): " + ex.message );
            
    }
  }
});


