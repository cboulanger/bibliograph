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
#require(qcl.access.*)
************************************************************************ */

/**
 * A mixin for the application instance that provides access to managers
 * that support:
 * <ul>
 * <li>session management</li>
 * <li>application state saved in the URL / history support</li>
 * <li>authentication with backend</li>
 * <li>synchronization of configuration values with backend</li>
 * <li>generic json-rpc backend communication</li>
 * <li>exchanging events and messages with a backend</li>
 * <li>addressing widgets by unique ids</li>
 * <li>cross-window clipboard</li>
 * <li>creating new native child windows using the object tree of 
 * the current application (not yet functional, depends on the resolution
 * of bug <a href="http://bugzilla.qooxdoo.org/show_bug.cgi?id=3086">3096</a>).</li>
 * </ul>
 */
qx.Mixin.define("qcl.application.MAppManagerProvider",
{
 
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties : {
    
    /**
     * The named id of the application === application namespace
     */
    applicationId : 
    {
      check : "String",
      nullable : false,
      init : "qooxdoo"
    },    
    
    /**
     * The name of the application
     */
    applicationName : 
    {
      check : "String",
      nullable : false,
      init : "A qooxdoo application"
    },
    
    /**
     * Whether this is the main application window or a dependent
     * child window
     */
    mainApplication : 
    {
      check : "Boolean",
      init : true
    },
    
    /** 
     * The session manager
     * 
     */
    sessionManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeSessionManager"
    },
    
    /**
     * The manager for rpc backend calls
     * @type 
     */
    rpcManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeRpcManager"
    },
    
    /**
     * The manager responsible for authentication
     * @type 
     */
    accessManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeAccessManager"
    },
    
    /**
     * The manager synchronizing configuration values between client and 
     * server 
     * @type 
     */
    configManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeConfigManager"
    },
    
    /**
     * The manager for state maintenance in the URL and application state history
     * @type 
     */
    stateManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeStateManager"
    },
    
    /**
     * The manager for maintaining a central clipboard that interacts
     * with the clipboard of the OS
     * @type 
     */
    clipboardManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeClipboardManager"
    },
    
    /**
     * The manager for loading the client-side code of the
     * plugins of the application
     * @type 
     */
    pluginManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changePluginManager"
    },    
    
    /**
     * The manager for native child windows
     * @type 
     */
    nativeWindowManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeNativeWindowManager"
    },

    /**
     * The manager for exchanging events and messages with the server
     * @type 
     */
    eventTransportManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeEventTransportManager"
    },    
    
    /**
     * The manager for maintaining a central clipboard that interacts
     * with the clipboard of the OS
     * @type 
     */
    clipboardManager :
    {
      check    : "qx.core.Object", // @todo: create interface
      nullable : false,
      event    : "changeClipboardManager"
    },
    
    /**
     * Whether the application should ask users if they "really" want 
     * to quit the application.
     * @type {Boolean} 
     */
    confirmQuit : 
    {
      check : "Boolean",
      init : true
    }
    
  },

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */  

  /**
   * Contructor, initializes the application.
   * Creates the necessary manager instances. If you want to use
   * different manager classes, override them in the constructor of the
   * application.
   */
  construct : function()
  {
    
    /*
     * cache for widget ids
     */
    this._widgetById = {};
    
    /*
     * Mixins
     */
    qx.Class.include( qx.core.Object, qcl.application.MGetApplication );
    qx.Class.include( qx.core.Object, qcl.application.MWidgetId );
    
  },
  
  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  
  members :
  {
    
    /**
     * Initializes the managers. Override by defining this method in your
     * application class. 
     */
    initializeManagers : function()
    {
      /*
       * setup managers
       */ 
      this.setSessionManager( new qcl.application.SessionManager );
      this.setStateManager( new qcl.application.StateManager );
      this.setRpcManager( new qcl.io.RpcManager );
      this.setAccessManager( new qcl.access.AccessManager );
      this.setConfigManager( new qcl.application.ConfigManager );
      this.setPluginManager( new qcl.application.PluginManager );
      // this.setClipboardManager ( new qcl.application.ClipboardManager );
   
      /*
       * session id
       */
      var sid =  this.getStateManager().getState("sessionId");
      if ( sid )
      {
        this.getSessionManager().setSessionId( sid );  
      }      
    },
    
//    /**
//     * Returns a reference to the main application
//     *
//     * @return {qx.application.Standalone}
//     */
//    getMainApplication : function()
//    {
//       if ( window.opener ) 
//       {
//         var app = opener.qx.core.Init.getApplication();
//       } 
//       else 
//       {
//         var app = this;
//       }
//       return app;
//    },    

    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */         
    
    _widgetById : {},   
    
        
    /*
    ---------------------------------------------------------------------------
       WIDGET ID
    ---------------------------------------------------------------------------
    */             
    
    /**
     * Store a reference to a widget linked to its id.
     * @param id {String}
     * @param widget {Object}
     * @return void
     */
    setWidgetById : function(id,widget)
    {
      this._widgetById[id] = widget;
    },
    
    /**
     * gets a reference to a widget by its id
     * @param id {String}
     * @return widget {Object}
     */
    getWidgetById : function(id)
    {
      return this._widgetById[id];
    },
    
    /*
    ---------------------------------------------------------------------------
       STARTUP AND TERMINATION
    ---------------------------------------------------------------------------
    */     
    
    /**
     * Called before the page is closed. If you would like to override this
     * method, define a close method in your main application. 
     * @return
     */
    close : function()
    {  
      if ( this.isMainApplication() && this.isConfirmQuit() )
      {  
        return this.tr("Do you really want to quit %1?",  this.getApplicationName() );
      }
      return undefined;
    },
    
    /**
     * Called when the page is closed. Calls the terminate() method of the
     * rpc manager. Override by definining a terminate() method in your application
     * class
     */
    terminate : function()
    {
      this.getRpcManager().terminate();
    }        
 
  }
});