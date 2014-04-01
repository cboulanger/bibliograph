/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/

   Copyright:
     2014 Christian Boulanger

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
 * The store also takes care of transport of events between all widget controllers
 * bound to it and the store service on the server. This allows to synchronize
 * widget data across browser windows and even across different computers. A separate
 * tutorial will explain this feature. 
 * 
 * The databinding requests can be used to transport events and message between 
 * server and client in yet another way by "piggybacking" on the transport in both 
 * directions. If you want to use this feature, the result sent from the server 
 * needs to contain an additional data layer. The response has the to be a hash map 
 * of the following structure:
 * 
 * <pre>
 * {
 *   // result property should always be provided in order to allow events and 
 *   // messages to be transported
 *   result : 
 *   {
 *     __qcl : true, // this marks the result as coming from the qcl server
 *     
 *     data   : { (... result data ...) },
 *     events : [ { type : "fooDataEvent", class: "qx.event.type.Data", data : "foo" }, 
 *                { type : "barEvent", class: "qx.event.type.Event" }, ... ],
 *     messages : [ { name : "fooMessage", data : "foo" }, 
 *                  { name : "barMessage", data: "bar" }, ... ]
 *   }
 *   
 *   // error property only exists if an error occurred 
 *   error : 
 *   {
 *     (... error data ...)
 *   }
 *   id : (int id of rpc request)
 * }
 * </pre>
 * 
 * The "events" and "messages" array elements will be dispatched as events on
 * the sending/receiving object or as public messages.This is entirely optional, though
 * and not an integral part of the databinding.
 * 
 */
qx.Class.define("qcl.data.store.JsonRpc", 
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
      this.__rpc = rpc;
    }
    else if( qx.Class.hasMixin( qx.Class.getByName( qx.core.Init.getApplication().classname ), qcl.application.MAppManagerProvider ) )
    {
      this.__rpc = this.getApplication().getRpcManager().getRpcObject();
    }
    else
    {
      this.__rpc = new qx.io.remote.Rpc();
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
    loadMethod :
    {
      check : "String",
      init : "load",
      event : "changeLoadMethod"
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
      init : 180000 // 3 Minutes
    },
  
    /**  
     * If jsonrpc is used, whether cross-domain requests will be used  
     */
    allowCrossDomainRequests :
    {
      check : "Boolean",
      init : false
    },
    
    
    autoLoadMethod:
    {
      check     : "String",
      nullable  : true,
      apply     : "_applyAutoLoadMethod",
      event     : "changeAutoLoadMethod"
    },
    
    autoLoadParams:
    {
      nullable  : true,
      apply     : "_applyAutoLoadParams",
      event     : "changeAutoLoadParams"
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
    _responseData : null,
    __request : null,
    __pool : null,
    __opaqueCallRef : null,
    __rpc : null,
    __requestId : 0,
    __requestCounter : 0,
    __lastMethod : null,
    __lastParams : null,
    
    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */ 
    
    _applyAutoLoadMethod : function( value, old)
    {
      if ( this.getAutoLoadParams() )
      {
        this.load( value, this.getAutoLoadParams() );
      }
    },

    _applyAutoLoadParams : function( value, old)
    {
      if ( qx.lang.Type.isString( value ) )
      {
        var value = value.split(",");
      } 
      if (value && this.getAutoLoadMethod() )
      {
        this.load( this.getAutoLoadMethod(), value );
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
      
      var app = qx.core.Init.getApplication();
      
      /* 
       * configure request object
       */
      var rpc = this.__rpc;
      rpc.setTimeout( this.getTimeout() );
      
      if ( this.getUrl() )
      {
        rpc.setUrl( this.getUrl() );
      }
      else if( qx.Class.hasMixin( 
            qx.Class.getByName( app.classname ), 
            qcl.application.MAppManagerProvider ) )
      {
        rpc.setUrl( this.getApplication().getRpcManager().getServerUrl() );
      }
      else
      {
        this.error("Cannot determine URL for request.");
      }
      
      rpc.setServiceName( this.getServiceName() );
      rpc.setCrossDomain( this.getAllowCrossDomainRequests() );

      /*
       * Application state is sent as server data (piggybacking on the request
       * to update the server about the state). (is ignored if application
       * doesn't support application state) @todo rewrite, remove if we have a
       * cometd implementation
       */
      if( qx.Class.hasMixin( 
            qx.Class.getByName( app.classname ), 
            qcl.application.MAppManagerProvider ) )
      {
        rpc.setServerData( app.getStateManager().getServerStates() );  
      }

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
      //qx.core.Init.getApplication().getRoot().setGlobalCursor("wait");
      this.__requestCounter++;
     

      /*
       * create callback function
       */
      var callbackFunc = qx.lang.Function.bind( function( result, ex, id ) 
      { 
          /*
           * decrement counter and reset cursor
           */
          if ( --this.__requestCounter < 1)
          {
            //qx.core.Init.getApplication().getRoot().setGlobalCursor("default");
          }
          
          /*
           * save data for debugging etc.
           */
          this._responseData = result;
  
          /*
           * show that no request is underway
           */
          this.__opaqueCallRef = null ;      
  
          /*
           * check for error
           */
          if ( ex == null ) 
          {  
  
            /* 
             * The result data is either in the 'data' property of the object (qcl) or the object
             * itself. If we have a 'data' property, also check for 'messages' and 'events' 
             * property.
             */
            var data;
            if ( this._is_qcl_result( result ) )
            {
              /*
               * handle messages and events
               */
              if ( result.messages || result.events ) 
              {
                this.__handleEventsAndMessages( this, result );
              }              
              data = result.data; 
            }
            else
            {
              data = result;
            }
            
            /* 
             * create the model if requested
             */
            if ( createModel )
            {
              try
              {
                /*
                 * create the class if neccessary
                 */
                this.getMarshaler().toClass( data, true);

                /*
                 * create model
                 */
                var model = this.getMarshaler().toModel(data);
                
                /*
                 * tear down old model?
                 */
                if( this.getModel() )
                {
                  //this.getModel().removeAllBindings();
                  //this.getModel().dispose();
                  //debugger;
                }
                
                /*
                 * set the initial data
                 */
                this.setModel( model );
              }
              catch(e)
              {
                this.warn("Error during marshaling of data: ");
                this.info(qx.dev.StackTrace.getStackTrace().join("\n")); 
                this.error(e);
                return;
              }
              
              /*
               * fire 'loaded' event only if we created a model
               */
              this.fireDataEvent( "loaded", this.getModel() );             
            }
             
            /*
             * final callback, only sent if request was successful
             */
            if ( typeof finalCallback == "function" )
            {
              try
              {
                finalCallback.call( context, data );
              }
              catch(e)
              {
                this.warn("Error in final callback: ");
                this.error(e);
              }
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

      }, this );    

      /*
       * send request 
       */
      var params2 = [ callbackFunc, serviceMethod ].concat( params || [] );
      this.__opaqueCallRef = rpc.callAsync.apply( rpc, params2 );

    },
    
    /**
     * Checks if result object is a qcl result (containing events and messages)
     * @param result {Object}
     * @return {Boolean}
     */
    _is_qcl_result : function ( result )
    {
      return ( qx.lang.Type.isObject( result )  && result.__qcl === true );  
    },

    /** 
     * Handles events and messages received with server response 
     * @param obj {Object} Context
     * @param data {Object} Data
     * @return {Void}
     */
    __handleEventsAndMessages : function ( obj, data )
    {
      /*
       * server messages
       */
      if( data.messages && qx.lang.Type.isArray(data.messages) ){
        data.messages.forEach( function(message){
          var msg = new qx.event.message.Message(message.name, message.data );
          qx.event.message.Bus.dispatch( msg ); 
        });
      }

      /*
       * server events
       */ 
      if( data.events && qx.lang.Type.isArray(data.events) )
      {
        data.events.forEach( function(event) {
          if (event.data) 
          {
            var eventObj = new qx.event.type.Data;
            eventObj.init(event.data);
          }
          else
          {
            var eventObj = new qx.event.type.Event;
          }
          eventObj.setType(event.Type);
          obj.dispatchEvent( eventObj ); 
        });
      }       
      return;
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
      
      /*
       * alert error if the dialog package is loaded
       */
      if ( qx.lang.Type.isObject( window.dialog ) && qx.lang.Type.isFunction( dialog.alert ) )
      {
        dialog.alert(ex.message);  
      }
    },
    
    
    /**
     * Handles the events sent by the server
     */
    _handleServerEvents : function( events )
    {
      for ( var i=0; i < events.length; i++)
      {
        var ed = events[i];
        ed.isServerEvent = true;
        var type = ed.eventType;
        delete ed.eventType;
        
        var event = new qx.event.type.Data;
        event.init(ed);
        event.setType(type);
        event.setTarget(this);
        
        this.dispatchEvent( event );
      }
    },    
    
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
      return this.__requestId++;
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
      this.__lastMethod = serviceMethod;
      this.__lastParams = params;
      this._sendJsonRpcRequest( 
          serviceMethod||this.getLoadMethod(), 
          params,
          finalCallback, 
          context,
          true
      );
    },
    
    /** 
     * Reloads the data from a service method asynchroneously. Uses the last
     * method and parameters used.
     * 
     * @param callback {function} The callback function that is called with
     *   the result of the request
     * @param context {Object} The context of the callback function     
     */    
    reload : function( callback, context )
    {
      this.load( this.__lastMethod, this.__lastParams, callback, context );
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
     if ( this.__opaqueCallRef )
     {
       this.getCurrentRequest().abort( this.__opaqueCallRef );
     }
   }
  }
});