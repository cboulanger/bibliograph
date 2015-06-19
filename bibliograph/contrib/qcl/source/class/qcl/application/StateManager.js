/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2015 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/**
 * Provides synchronization between the application's properties and the 
 * application state saved in the URL hash.
 * The syntax is the similar to the URL GET parameters, i.e. state values are
 * saved as key-value pairs. You can freely choose the characters that represent
 * the "equal" and "ampersand" characters (including those), however the default
 * are "." and "!"   #key1.value1!key2.value2!key3.value3 etc.
 *  
 * Any change to the state values (for example, by using the back or forward 
 * buttons or by manually changing the URL) will update a corresponding property, 
 * if defined, dispatching change events or calling apply methods, if so configured. 
 * 
 * Since the state is not automatically updated when the property changes, 
 * you need to manually set the state in an "apply" method.
 * 
 * <pre>
 * 
 * ...
 * properties : {
 * ...
 *   myProperty : {
 *     check : "String",
 *     nullable : true
 *     apply : "_applyMyProperty",
 *     event : "changeMyProperty"
 *   },
 * ...
 * members: {
 * ...
 *   _applyMyProperty : function( value, old )
 *   { 
 *     qx.core.Init.getApplication().getStateManager().setState("myProperty",value);
 *   }
 * ...
 * 
 * Properties can also be boolean or integer and will be automatically converted
 * when the state changes.
 */
qx.Class.define("qcl.application.StateManager",
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
     * The character separating the state variable definitions.
     * You can use all non-reserved characters here To improve
     * readability of the URI string, use characters that are not
     * percent-encoded, such as the tilde ~. You can also use a 
     * combination of characters, such as '~~~'. The URI hash 
     * string is first split on this character(s), then on the
     * {@link qcl.application.MAppManagerProviderState#stateDefineChar}
     * character(s).
     */
    stateSeparatorChar :
    {
      check : "String",
      init : "!",
      nullable : false 
    },
    
    /** 
     * The character which separates state variable name and state value.
     * See {@link qcl.application.MAppManagerProviderState#stateSeparatorChar} for
     * the choice of the character.
     */
    stateDefineChar :
    {
      check : "String",
      init : ".",
      nullable : false
    },
    
    /**
     * Whether to support the forward and back button to control
     * application state
     */
    historySupport :
    {
      check : "Boolean",
      init : false,
      apply : "_applyHistorySupport",
      event : "changeHistorySupport"
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
     * initialize history stacks
     */
    this.__backHistoryStack = [];
    this.__forwardHistoryStack = [];
    
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
    __lastHash : null, 
    __hashParams  : null,
    __backHistoryStack : null,
    __forwardHistoryStack : null,
    
    
    /*
    ---------------------------------------------------------------------------
       GET PARAMETERS
    ---------------------------------------------------------------------------
    */     

    /**
     * Returns a map of GET parameters from the URL
     *
     * @return {Map} 
     */
    _analyzeSearchString : function()
    {
      var search = window.location.search;
      var getParams = window.location.parameters = {};
      if (search)
      {
        var searchStr = decodeURIComponent(search.substr(1));
        var parts = searchStr.split( this.getStateSeparatorChar() );
        for (var i=0; i<parts.length; i++)
        {
          var p = parts[i].split( this.getStateDefineChar() );
          getParams[p[0]] = typeof p[1] == "string" ? p[1].replace(/\+/g, ' ') : true;
        }
      }
      return getParams;
    },


    /**
     * Returns a specific GET parameter
     *
     * @param key {String} The parameter name
     * @return {String}
     */
    getGetParam : function(key)
    {
      return this._analyzeSearchString()[key];
    },


    /**
     * Sets a GET parameter in the URL, triggering a reload of the page
     * if the parameter has changed
     *
     * @param first {String|Map} If a map, set each key-value pair, if a string, treat as key and set the value
     * @param second {String|null} If first parameter is a string, use this as value.
     * @return {void} 
     */
    setGetParam : function(first, second)
    {
      var getParams = this._analyzeSearchString();
      if (typeof first == "object")
      {
        for (var key in first) 
        {
          getParams[key] = first[key];
        }
      }
      else
      {
        getParams[first] = second;
      }
      var p = [];
      for (var key in getParams) 
      {
        p.push(key + this.getStateDefineChar() + encodeURIComponent(getParams[key]));
      }
      window.location.search = p.join( this.getStateSeparatorChar() );
    },

    /*
    ---------------------------------------------------------------------------
       HASH PARAMETERS
    ---------------------------------------------------------------------------
    */ 
    
    /**
     * Returns a Map of the parameterized hash string
     *
     * @param string {String} Optional string to analyze instead of location.hash
     * @return {Map} 
     */
    _analyzeHashString : function(string)
    { 
      var hash  = string || location.hash.substr(1) || "";

      /*
       * Safari bug
       */
      while ( hash.search(/%25/) != -1 )
      {
        hash = hash.replace(/%25/g,"%");
      }
      
      hash = decodeURIComponent(hash);

      var hashParams = {};
      if (hash)
      {
        var parts = hash.split( this.getStateSeparatorChar() );

        for (var i=0; i<parts.length; i++)
        {
          var p = parts[i].split( this.getStateDefineChar() );
          hashParams[p[0]] = typeof p[1] == "string" ? p[1].replace(/\+/g, ' ') : true;
        }
      }
      if ( ! string ) location.hashParams = hashParams;
      return hashParams;
    },


    /**
     * Returns a specific parameter in the hash string
     *
     * @param key {var} TODOC
     * @return {var} TODOC
     */
    getHashParam : function(key)
    {
       return this._analyzeHashString()[key];
    },


    /** 
     * Sets an url hash parameter
     *
     * Sets a parameter in the URL hash. This does not trigger a reload of the page
     * if the parameter has changed.
     *
     * @param first {String|Map} If a map, set each key-value pair, if a string, treat as key and set the value
     * @param second {String|null} If first parameter is a string, use this as value.
     * @return {Map} 
     */
    setHashParam : function(first, second)
    {
      var hashParams = this._analyzeHashString();

      if (typeof first == "object")
      {
        for (var key in first) 
        {
          hashParams[key] = first[key];
        }
      }
      else
      {
        hashParams[first] = second;
      }

      var p = [];

      for (var key in hashParams) 
      {
        p.push(key + this.getStateDefineChar() + encodeURIComponent(hashParams[key]));
      }

      window.location.hash = p.join( this.getStateSeparatorChar() );
      
      /*
       * Safari bug
       */      
      while ( window.location.hash.search(/%25/) != -1 )
      {
        window.location.hash = window.location.hash.replace(/%25/g,"%");
      }
      
      //console.log(window.location.hash);
      return hashParams;
    },

    /**
     * Removes a hash parameter
     *
     * @param name {String} TODOC
     * @return {Map}  
     */
    removeHashParam : function(name)
    {
      var hashParams = this._analyzeHashString();
      
      if ( hashParams[name] != undefined ) 
      {
        delete hashParams[name];
        var p = [];
        for (var key in hashParams) 
        {
          p.push(key + this.getStateDefineChar() + encodeURIComponent( hashParams[key] ) );
        }
        if ( p.length )
        {
          window.location.hash = p.join( this.getStateSeparatorChar() );
        }
        else
        {
          /*
           * placeholder to avoid page reload
           */
          window.location.hash = "";
        }
        
        /*
         * Safari bug
         */        
        while ( window.location.hash.search(/%25/) != -1 )
        {
          window.location.hash = window.location.hash.replace(/%25/g,"%");
        }
        
      }
      return hashParams;
    },
    
    /*
    ---------------------------------------------------------------------------
       STATE
    ---------------------------------------------------------------------------
    */     
    
    /** 
     * Sets a state aspect of the application. Primitive values will be
     * converted in String representations, Arrays into a list of values
     * separated by comma. 
     * 
     * @param name {String} 
     * @param value {String} 
     * @param description {String} Optional description of the state that 
     *   will appear in the title bar and the browser history
     * @return {void}
     * @todo handle null or undefined
     */
    setState : function( name, value, description )
    {
      if ( typeof name  != "string" )
      {
        this.error( "Invalid parameters" );
      }
      
      /*
       * convert to string
       */
      if ( typeof value != "string" )
      {
        value = new String(value).toString();
      }
      
      var oldValue = this.getState( name );
      //console.log("New state for '" + name + "' :'" +value +"', old state :'" + oldValue +"'");
      
      /*
       * only dispatch events if value actually changes
       */
      if ( value != oldValue )
      {
        /*
         * setting hash parameter and property
         */
        this.setHashParam( name, value );
         
        /*
         * Update application property, if exists
         */ 
        this._set( name, value );
        
        /*
         * qooxdoo browser navigation button support
         */
        this.addToHistory( location.hash.substr(1), description );        
      }
    },
    
    /**
     * Sets a property of the main application instance, if it exists, 
     * casting values to the correct type, if necessary.
     * @return {void}
     */
    _set : function ( name, value )
    {
      var app = qx.core.Init.getApplication();
      var clazz = qx.Class.getByName( app.classname );
      
      if ( qx.Class.hasProperty( clazz, name ) )
      { 
        var type = qx.Class.getPropertyDefinition( clazz, name ).check;
        switch( type )
        {
          case "Integer":
            if ( isNaN( parseInt( value )  ) )
            {
              this.error("Trying to set non-integer state property to integer application property");
            }
            value = parseInt( value );
            break;
          
          case "Boolean":
            value = new Boolean( value );
            break;
            
          case "Array":
            if ( value == "" )
            {
              value = [];
            }
            else
            {
              value = value.split(",");
            }
            break; 
            
          case undefined:
          case "String":
          case "Object":
            break;
            
          default:
            this.error( "Cannot set application property for state '" +  name + "': invalid type '" + type +"'");
            
        }
        app.set( name, value );
      }      
    },
    
    /**
     * Gets the string value of a state
     * @param name {String} 
     * @return {String}
     */
    getState : function ( name )
    {
      var value = this.getHashParam( name );
      switch (value)
      {
        case "null": return null;
        case "false": return false;
        case "true": return true; 
        case "undefined": return undefined;
        case "NaN" : return undefined;
        default: return value;
      }
    },
    
    /**
     * Returns a map with the complete application state
     * @return {Map}
     */
    getStates : function()
    {
      return this._analyzeHashString();
    },
    

    /**
     * Updates the current state, firing all change events even if 
     * the state hasn't changed. If you don't supply any argument,
     * all states will be updated. If you supply an array of strings
     * or a variable number of string arguments, only the states 
     * in the array or arguments will be updated.
     * @param state {var} optional. a variable number of string arguments or an array
     * @return {Map}
     */
    updateState : function()
    {  
      var states = {};
      var stateMap = this._analyzeHashString();
      if ( arguments[0] instanceof Array )
      {
        arguments[0].forEach(function(name){
         states[name] = true; 
        }); 
      }
      else if ( arguments.length)
      {
        for (var i=0; i<arguments.length; i++)
        {
          states[arguments[i]] = true; 
        }
      }
      else
      {
        states = null;
      }
      
      for(var key in stateMap)
      {
         if ( states && ! states[key] ) continue;
         this._set( key, stateMap[key] );
      }
      return stateMap;    
    },

    /**
     * Removes a state
     * @param name {String}
     * @return {Map}
     */
    removeState : function ( name )
    {
      this.removeHashParam( name );
      this.addToHistory(location.hash.substr(1),null);
    },
    
    
    /*
    ---------------------------------------------------------------------------
       HISTORY SUPPORT
    ---------------------------------------------------------------------------
    */     
    
    /**
     * Support qooxdoo history manager 
     * @param value {Boolean}
     * @return {void}
     */
    _applyHistorySupport : function (value,old)
    {
      if ( value && ! old )
      {            
    
        var state = qx.bom.History.getInstance().getState();  
        this.__lastHash    = state; 
        this.__hashParams  = this._analyzeHashString();
        
        /*
         * setup event listener for history changes
         */
        qx.bom.History.getInstance().addListener("request", function(e) 
        {
          this.updateState();
        }, this);
      }  
    },
  
    /**
     * Wraps qx.bom.History.getInstance().navigateBack();
     */
    navigateBack : function()
    {
      var bHist = this.__backHistoryStack;
      var fHist = this.__forwardHistoryStack;
      //console.log("Trying to navigate backwards, stack length is "+ bHist.length);
      if ( bHist.length )
      {
        var hash = bHist.shift(); // get from backward stack
        fHist.unshift(hash); // and put on forward stack
        
        /*
         * for some reason, this has to be executed twice to trigger the back action
         */
        qx.bom.History.getInstance().navigateBack();
        qx.bom.History.getInstance().navigateBack();

        return true;
      }
      return false;
    },
    
    /**
     * Wraps qx.bom.History.getInstance().navigateForward()
     */
    navigateForward : function()
    {
      var fHist = this.__forwardHistoryStack;
      var bHist = this.__backHistoryStack;
      //console.log("Trying to navigate forwards, stack length is "+ fHist.length);
      if ( fHist.length )
      {
        var hash = fHist.shift(); // get from forward stack
        bHist.unshift(hash); // and put on backward stack
        
        /*
         * for some reason, this has to be executed twice to trigger the forward action
         */
        qx.bom.History.getInstance().navigateForward();
        qx.bom.History.getInstance().navigateForward();
        return true;
      }
      return false;
    },
    
    /**
     * Wraps the qooxdoo history function
     * @param hash {String}
     * @param description {String|undefined}
     */
    addToHistory : function( hash, description )
    {
      /*
       * check if state has changed
       */
      if ( hash == this.__lastHash )
      {
        //console.log("Hash hasn't changed, not adding it to history...");
        return;
      }
      this.__lastHash = hash;
      var bHist = this.__backHistoryStack;
      bHist.unshift(hash);
      qx.bom.History.getInstance().addToHistory( hash, description );
    },

    /**
     * Checks if there is something to navigate back to.
     * @return {Boolean}
     */
    canNavigateBack : function()
    {
      return ( this.__backHistoryStack.length > 1 );
    },

   /**
    * Checks if there is something to navigate forward to.
    * @return {Boolean}
    */    
    canNavigateForward : function()
    {
      return ( this.__forwardHistoryStack.length > 1 );
    }
  }
});