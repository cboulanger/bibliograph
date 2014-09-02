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
            self.fireDataEvent("loadingPlugin", {
              'name'  : data.name,
              'count' : count,
              'sum'   : sum
            });
            
            if( data.url )
            {
              var url = self.getPreventCache()
                  ? data.url + "?nocache=" + (new Date()).getTime()
                  : data.url;
              var loader = new qx.bom.request.Script();
              loader.onload = loadScript;
              loader.open("GET", url);
              loader.send();
            }
            
            else if ( data.part )
            {
              if( typeof data.part == "string" )
              {
                data.part = [data.part]
              }
              qx.io.PartLoader.require(data.part, loadScript);
            }
                
            else
            {
              self.error("Plugin '" + data.name + "' has no url or part property.");
            }

          })();
          
        },this
      );
    }
  }
});