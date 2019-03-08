/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright:
    2018 Christian Boulanger

  License:
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors:
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * This is a qooxdoo singleton class
 *
 */
qx.Class.define("bibliograph.store.Datasources",
{
  extend : qcl.data.store.JsonRpcStore,
  include : [],
  type : "singleton",
  
  construct : function() {
    this.base(arguments, "datasource");
    qx.event.message.Bus.subscribe("datasources.reload", () => {
      this.load();
    });
    qx.event.message.Bus.subscribe("user.loggedin", () => {
      this.load();
    });
  },
  
  members : {
    _applyModel : function(data, old) {
      // call overriddden method
      this.base(arguments, data, old);
      
      let app = this.getApplication();
      let datasourceCount = qx.lang.Type.isObject(data) ? data.length : 0;
      this.info("User has access to " + datasourceCount + " datasources.");
      
      // show datasource button depending on whether there is a choice
      app.getWidgetById("app/toolbar/buttons/datasource").setVisibility(
        datasourceCount > 1 ? "visible" : "excluded"
      );

      // if we have no datasource loaded, no access
      if (datasourceCount === 0 && !this.__loggingout) {
        if (!this.getApplication().getActiveUser().isAnonymous()) {
          this.__loggingout = true;
          this.getApplication().getCommands().logout();
        }
        dialog.Dialog.alert(app.tr("You don't have access to any datasource. Reloading the page might help."));
        return;
      }
      this.__loggingout = false;

      // if there is one saved in the application state, and we have access, use this
      let datasource = app.getStateManager().getState("datasource");
      let found=false;
      data.forEach(item => {
        if (item.getValue() === datasource) {
         found=item;
        }
      });
      if (datasource && found) {
        app.getStateManager().updateState();
        return;
      }

      // if we have access to exactly one datasource, load this one
      if (datasourceCount === 1) {
        let item = data.getItem(0);
        app.setDatasource(item.getValue());
        app.getStateManager().updateState();
      } else {
        // else, we have a choice of datasource
        app.setDatasourceLabel(app.getConfigManager().getKey("application.title"));
        let dsWin = app.getWidgetById("app/windows/datasources");
        dsWin.open();
      }
    }
  }
});
