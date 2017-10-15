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

/*global bibliograph qx qcl dialog*/

/**
 * Plugin Manager Class 
 */
qx.Class.define("qcl.application.PluginManager",
{
  extend : qx.core.Object,

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties :
  {
    preventCache :
    {
      check : "Boolean",
      init  : "false"
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
     * Dispatched when the plugins have been loaded
     */
    "loadingPlugin" : "qx.event.type.Data",
    
    /**
     * Dispatched when the plugins have been loaded
     */
    "pluginsLoaded" : "qx.event.message.Message"
        
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

    /**
     * Returns the relative path to the plugin service folder
     * @return string
     */
    getPluginServiceUrl : function(name){
      return "../plugins/"+name+"/services";
    },

    /**
     * Loads the plugin javascript code
     */
    loadPlugins : function( callback, context )
    {
      var app = this.getApplication();
      var service = app.getApplicationId() + ".plugin";
      
      /*
       * get script url arry from server
       */
      app.getRpcManager().execute(
        service, "getPluginData", [],
        function( pluginData )
        {
          
          if ( ! qx.lang.Type.isArray( pluginData ) )
          {
            this.error( service + ".getPluginData did not return an array." );
          }
          
          var sum = pluginData.length;
          var count = 0;
          var self = this;
          
          /*
           * function to sequentially load all plugins
           * @todo: why not load them in parallel? what if plugins
           * are dependent on each other?
           */
          var self = this;
          (function loadScript(){
            
            /*
             * if no more scripts to load, we're done
             */
            if ( ! pluginData.length )
            {
              /*
               * dispatching message
               */
              qx.event.message.Bus.dispatch( new qx.event.message.Message("pluginsLoaded") );
              
              /*
               * returning to callback
               */
              if( typeof callback == "function" )
              {
                callback.call( context );
              }
              return;
            }
            
            /*
             * at least one more script left, load next one
             * with this function as callback
             */
            var data = pluginData.shift();
            count++;
            
            if( data.source || data.part )
            {
              self.fireDataEvent("loadingPlugin", {
                'name'  : data.name,
                'count' : count,
                'sum'   : sum
              });              
            }
            
            if( data.source )
            {
              var url = self.getPreventCache()
                  ? data.source + "?nocache=" + (new Date()).getTime()
                  : data.source;
              var loader = new qx.bom.request.Script();
              loader.onload = loadScript;
              loader.open("GET", url);
              loader.send();
              return;
            }
            
            else if ( data.part )
            {
              if( ! qx.lang.Type.isString(data.namespace) )
              {
                self.warn("Plugin '" + data.name + "' has no namespace property. Not installed.");
                loadScript();
              }

              self._loadPart(data, loadScript);
              return;
            }
            loadScript();
          })();
          
        },this
      );
    },
    
    /**
     * Load the plugin as a qooxdoo part
     * @param data {Object} plugin data
     * @param next {Function} callback
     */
    _loadPart : function( data, next, self )
    {
      this.error("You must extend qcl.application.PluginManager and provide your own _loadPart method containing your parts and plugin classes.");
//      var self = this;
//      qx.io.PartLoader.require(data.part, function() {
//        var plugins= {
//          // "part-name-1" : plugin1.Plugin,
//          // "part-name-2" : plugin2.Plugin,
//          // ....
//        };
//        try
//        {
//          plugins[data.part].getInstance().init();
//        }
//        catch(e)
//        {
//          self.warn("Plugin '" + data.name + "': Error initializing: " + e);
//        }
//        next();
//      });
    }
  }
});