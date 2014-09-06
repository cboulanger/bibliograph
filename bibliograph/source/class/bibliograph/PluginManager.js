/* ************************************************************************

 Bibliograph: Online Collaborative Reference Management

 Copyright:
 2007-2014 Christian Boulanger

 License:
 LGPL: http://www.gnu.org/licenses/lgpl.html
 EPL: http://www.eclipse.org/org/documents/epl-v10.php
 See the LICENSE file in the project's top-level directory for details.

 Authors:
 * Christian Boulanger (cboulanger)

 ************************************************************************ */
/*global bibliograph qx qcl dialog*/

/**
 * Manages plugin loading
 */
qx.Class.define("bibliograph.PluginManager",
{
  extend : qcl.application.PluginManager,

  members :
  {
    /**
     * Load the plugin as a qooxdoo part
     * @param data {Object} plugin data
     * @param next {Function} callback
     */
    _loadPart : function( data, next, self )
    {
      var self = this;
      qx.io.PartLoader.require(data.part, function() {
        var plugins= {
          "plugin_csl"          : window.csl ? csl.Plugin : null,
          "plugin_z3950"        : window.z3950 ? z3950.Plugin : null,
          "plugin_isbnscanner"  : window.isbnscanner ? isbnscanner.Plugin : null
        };
        try
        {
          plugins[data.part].getInstance().init();
        }
        catch(e)
        {
          self.warn("Plugin '" + data.name + "': Error initializing: " + e);
        }
        next();
      });
    }
  }
});