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
 * This object manages the configuration settings of an application. In 
 * conjunction with a JsonRpc store, you can load the configuration data at 
 * startup and synchronize the config values with the server.
 * 
 * <pre>   
 * var myConfigStore = qcl.data.store.JsonRpc( 
 *   "path/to/server/index.php", "myapp.Config" 
 * ); 
 * myConfigStore.bind("model", qcl.application.ConfigManager.getInstance(), "model");
 * myConfigStore.load();
 * 
 * The server must expose a "load"  method that returns the following response
 * data:
 * 
 * <pre> 
 * {
 *   keys : [ ... array of the names of the configuration keys ],
 *   values : [ ... array of the configuration values ... ],
 *   types : [ ... array of the configuration value types ... ]
 * }
 * </pre>
 * 
 * In order to send the changes back to the server, you can do the following
 *       
 * <pre>   
 * var cm = qcl.application.ConfigManager.getInstance();   
 * cm.addListener("change",function(event){
 *   var key = event.getData();
 *   myConfigStore.execute("set",[ key, cm.getValue(key) ] );
 * });  
 * </pre>
 * 
 * This requires that the server exposess a "set" method with the parameters
 * key, value that saves the config value back into the database.
 * 
 * You can bind any property of an object to a config value by using
 * the {@link #bindKey} method.
 * 
 */
qx.Class.define("qcl.application.ConfigManager",
{
  extend : qx.core.Object,
 
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties : {
     
     /**
      * The data store used for configuration data
      */
     store :
     {
       check : "qcl.data.store.JsonRpc",
       nullable : true,
       event    : "changeStore"
     },
     
    /*
     * The config manager's data model which can be
     * bound to a data store. It must be an qx.core.Object
     * with two properties, "keys" and "values", which
     * contain config keys and values, respectively and
     * will be converted to qx.data.Array objects
     */
    model :
    {
      check : "qx.core.Object",
      nullable : true,
      event : "changeModel",
      apply : "_applyModel"
    }
  },
  
  /*
  *****************************************************************************
     EVENTS
  *****************************************************************************
  */
 
  events :
  {
    /* 
     * Dispatched when the configuration data is ready
     */
    "ready" : "qx.event.type.Event",
    
    /* 
     * Dispatched with the name of the changed config key when
     * the config value changes, regardless of whether the change
     * was caused be the client or server.
     */
    "change" : "qx.event.type.Data",
    
    /* 
     * Dispatched  when the config value changes on the client.
     * The evend data is a map with the keys 'key','value','old' 
     */
    "clientChange" : "qx.event.type.Data"    
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
    _configSetup : false,
    _index : null,
    
    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */          
    
    _applyModel : function( model, old )
    {
      
      if( model === null ) return;
      
      /* 
       * create index
       */
       this._index = {};
       
       var keys = model.getKeys();
       for( var i=0; i < keys.length; i++ )
       {
         var key = keys.getItem(i);
         this._index[ key ] = i;
         this.fireDataEvent( "change", key );
       }
       
       /*
        * attach event listener
        */
       model.getValues().addListener("changeBubble", function(event){
         var data = event.getData();
         var key = model.getKeys().getItem( data.name );
         if ( data.value != data.old )
         {
           this.fireDataEvent( "change", key );
         }
       },this);
        
       /*
        * inform the listeners that we're ready
        */
       this.fireEvent("ready"); 
    },
    
    /*
    ---------------------------------------------------------------------------
      PRIVATE METHODS
    ---------------------------------------------------------------------------
    */
   
    
    /**
     * Returns the numerical index for a config key 
     * name
     * @param key {String}
     * @return {Integer}
     */
    _getIndex : function( key )
    {
      if ( ! this._index )
      {
        this.error("Model has not yet finished loading.");
      }
      var index = this._index[key];
      if ( index == undefined )
      {
        throw new Error("Invalid config key '" + key + "'.");
      }
      return index;
    },
    
    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */     

    /**
     * Initializes the manager
     * @param service {String}
     */
    init : function( service )
    {
      /*
       * avoid duplicate bindings
       */
      if ( this._configSetup )
      {
        this.error("Configuration already set up");
      }
      this._configSetup = true;
      
      /*
       * set default config store
       */
      if ( ! this.getStore() )
      {
        this.setStore(
          new qcl.data.store.JsonRpc( null, service )       
        );        
      }
           
      /* 
       * bind the configuration store's data model to the user manager's data model
       */
      this.getStore().bind("model", this, "model");

      /*
       * whenever a config value changes on the server, send it to server
       */
      this.addListener("clientChange",function(event){
        var data = event.getData();
        this.getStore().execute("set",[ data.key, data.value ] );
      },this);       
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
     * Loads configuration values from the server and configures auto-update
     * whenever the a value changes on the server. The config data has to be sent
     * in the following format:
     * <pre> 
     * {
     *   keys : [ ... array of the names of the configuration keys ],
     *   values : [ ... array of the configuration values ... ]
     * }
     * </pre>
     */
    load : function( callback, context )
    {
      this.getStore().load( null, null, callback, context );
    },
    
    /**
     * Checks if a config key exists
     * @param key {String}
     * @return {Boolean}
     */
    keyExists : function( key )
    {
      try 
      {
        this._getIndex( key );
        return true;
      }
      catch( e )
      {
        return false;
      }
    },
   
    /**
     * Returns a config value
     * @param key {String}
     * @return {var}
     */
    getKey : function ( key )
    {
      var index = this._getIndex( key );
      return this.getModel().getValues().getItem( index );
    },
    
    /**
     * Sets a config value and fire a 'clientChange' event.
     * @param key {String}
     * @param value {Mixed} 
     */
    setKey : function (key, value)
    {
       var index = this._getIndex( key) ;
       var old = this.getModel().getValues().getItem( index );
       if ( value != old )
       {
         this.getModel().getValues().setItem( index, value );
         this.fireDataEvent("clientChange", {
           'key' : key, 
           'value' : value,
           'old' : old 
          });
       }
    },
    
    /**
     * Binds a config value to a target widget property, optionally in both
     * directions.
     * @param key {String}
     * @param targetObject {qx.core.Object}
     * @param targetPath {String}
     * @param updateSelfAlso {Boolean} Optional, default undefined. If true,
     *  change the config value if the target property changes
     * @return {void}
     */
    bindKey : function( key, targetObject, targetPath, updateSelfAlso )
    {
      if ( ! this.getModel() )
      {
        this.error("You cannot bind a config key before config values have been loaded!");
      }
      
      if ( ! targetObject instanceof qx.core.Object )
      {
        this.error( "Invalid target object." );
      }
      
      if ( ! qx.lang.Type.isString( targetPath ) )
      {
        this.error( "Invalid target path." );
      }
      
      /*
       * if the target path is a property and not a property chain,
       * use event listeners. This also solves a problem with a bug
       * in the SigleValueBinding implementation, 
       * see http://www.nabble.com/Databinding-td24099676.html
       */
      if ( targetPath.indexOf(".") == -1 )
      {
        /*
         * set the initial value
         */
        //try{
        targetObject.set( targetPath, this.getKey(key) );
        //}catch(e){alert(e);}
        
        /*
         * add a listener to update the target widget property when 
         * config value changes
         * @todo: add converter
         */
        this.addListener( "change", function(e){       
          var changeKey = e.getData();
          if( changeKey == key )
          {
            //console.warn("Updating property "+targetPath+" from config key "+key+":"+this.getValue(key));
            targetObject.set(targetPath,this.getKey(key));
          }
        },this);

        /*
         * update config value if target widget property changes
         */
        if ( updateSelfAlso )
        {
          targetObject.addListener(
            "change" + targetPath.substr(0,1).toUpperCase() + targetPath.substr(1),
            function(e)
            {
              var value= e.getData();
              //console.warn("Updating config key "+key+" with "+value);
              this.setKey(key,value);
            },
            this
          );
        }
      }
      
      /* 
       * use SigleValueBinding, was not working last time I checked.
       * @todo check with current qooxdoo code, but might have been fixed.
       */
      else
      {
        /*
         * get index of config key
         */
        var index = this._getIndex( key );        
        
        /*
         * update the target widget property when config value changes
         */
        targetObject.bind( targetPath, this, "model.values[" + index + "]" );
        
        /*
         * update config value if target widget property changes
         */
        if ( updateSelfAlso )
        {
          this.bind( "model.values[" + index + "]", targetObject, targetPath );
        }
      }
    }   
   
  },
  
  /*
  *****************************************************************************
     DESTRUCTOR
  *****************************************************************************
  */

  destruct : function()
  {
    this._disposeArray("_index"); 
  }  
  
  
});