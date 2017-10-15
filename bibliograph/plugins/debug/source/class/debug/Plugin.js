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

      // add debug menu
      var debugMenuButton = new qx.ui.menu.Button();
      debugMenuButton.setLabel(this.tr("Debug"));
      permMgr
        .create("debug.selectFilters")
        .bind("state", debugMenuButton, "visibility", {
          converter: function(v) {
            return v ? "visible" : "excluded";
          }
        });
      var debugMenu = new qx.ui.menu.Menu();
      debugMenuButton.setMenu(debugMenu);
      systemMenu.add(debugMenuButton);
      // button to show window
      var openWindowButton = new qx.ui.menu.Button();
      openWindowButton.setLabel(this.tr("Open log window"));
      openWindowButton.addListener("execute",function(){
        var url = this.getApplication().getPluginManager().getPluginServiceUrl("debug") + "/logtail.php";
        window.open(url,"debug_window");
      },this);
      debugMenu.add(openWindowButton);
      debugMenu.addSeparator();
      // populate menu with log filters
      this.info("Retrieving log filter data...");
      rpcMgr.execute(
        "debug.Service",
        "getLogFilters",
        null,
        function(data) {
          this.debug(data);
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
            debugMenu.add(checkBoxButton);
          }, this);
        },
        this
      );
    }
  }
});
