/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/*global qx qcl bibliograph dialog template*/

/**
 * Plugin Initializer Class
 * 
 */
qx.Class.define("debug.Plugin", {
  extend: qx.core.Object,
  include: [qx.locale.MTranslation],
  type: "singleton",
  members: {
    init: function() {
      // vars
      var app = this.getApplication();
      var systemMenu = app.getWidgetById("bibliograph/menu-system");
      var permMgr = app.getAccessManager().getPermissionManager();
      var rpcMgr = app.getRpcManager();
      var confMgr = app.getConfigManager();
      
      // add debug menu
      var debugMenuButton = new qx.ui.menu.Button();
      debugMenuButton.setLabel(this.tr("Debug backend"));
      permMgr
        .create("debug.allowDebug")
        .bind("state", debugMenuButton, "visibility", {
          converter: function(v) {
            return v ? "visible" : "excluded";
          }
        });
      debugMenuButton.setMenu(createDebugMenu.bind(this)());
      systemMenu.add(debugMenuButton);      

      // add logging menu
      var logMenuButton = new qx.ui.menu.Button();
      logMenuButton.setLabel(this.tr("Select log filters"));
      permMgr
        .create("debug.selectFilters")
        .bind("state", logMenuButton, "visibility", {
          converter: function(v) {
            return v ? "visible" : "excluded";
          }
        });
      
      logMenuButton.setMenu(createLoggingMenu.bind(this)());
      systemMenu.add(logMenuButton);
      
      function createDebugMenu(){
        var debugMenu = new qx.ui.menu.Menu();
        // button to show window
        var openWindowButton = new qx.ui.menu.Button();
        openWindowButton.setLabel(this.tr("Open log window"));
        openWindowButton.addListener("execute",function(){
          var url = this.getApplication().getPluginManager().getPluginServiceUrl("debug") + "/logtail.php";
          window.open(url,"debug_window");
        },this);
        debugMenu.add(openWindowButton);
        
        // checkbox button for recording jsonrpc traffic
        var checkBoxButton = new qx.ui.menu.CheckBox(this.tr("Record JSONRPC traffic"));
        confMgr.addListener("ready", function() {
          confMgr.bindKey("debug.recordJsonRpcTraffic", checkBoxButton, "value", false);
        });        
        checkBoxButton.addListener(
          "changeValue",
          function(e) {
            var value = e.getData();
            rpcMgr.execute(
              "debug.Service",
              "recordJsonRpcTraffic",
              [value],
              function(data) {
                this.info("Recording of JSONRPC traffic has been turned "+(value?"on":"off")+".");
              },
              this
            );
          },
          this
        );
        debugMenu.add(checkBoxButton);        
        return debugMenu;
      }

      function createLoggingMenu(){
        var loggingMenu = new qx.ui.menu.Menu();
        this.info("Retrieving log filter data...");
        rpcMgr.execute(
          "debug.Service",
          "getLogFilters",
          null,
          function(data) {
            if (!(data instanceof Array)) {
              this.error("Invalid log filter data from backend...");
            }
            data.forEach(function(element) {
              var checkBoxButton = new qx.ui.menu.CheckBox();
              checkBoxButton.set({
                label : this.tr(element.description),
                value : element.enabled
              });
              checkBoxButton.addListener(
                "changeValue",
                function(e) {
                  var value = e.getData();
                  rpcMgr.execute(
                    "debug.Service",
                    "setFilterEnabled",
                    [element.name,value],
                    function(data) {
                      this.info("Log filter "+element.name+" has been turned "+(value?"on":"off")+".");
                    },
                    this
                  );
                },
                this
              );
              loggingMenu.add(checkBoxButton);
            }, this);
          },
          this
        );        
        return loggingMenu;
      }
    }
  }
});
