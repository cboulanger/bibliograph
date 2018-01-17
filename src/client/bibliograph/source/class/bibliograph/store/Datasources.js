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
  extend : bibliograph.io.JsonRpcStore,
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
  members :
  {
    _applyModel : function( data, old )
    {
      // call overriddden method
      this.base(arguments, data, old);

      console.debug(qx.util.Serializer.toNativeObject(data));
      return; 

      let app = this.getApplication();    
      var datasourceCount = data.length;
      // if we have no datasource loaded, no access
      if (datasourceCount == 0) {
        dialog.Dialog.alert(app.tr("You don't have access to any datasource on the server."));
      }
      // if we have access to exactly one datasource, load this one
      else if (datasourceCount == 1) {
        var item = data.getItem(0);
        app.setDatasource(item.getValue()); //???
        app.setDatasourceLabel(item.getTitle());
        app.getStateManager().updateState();
      }
      // else, we have a choice of datasource
      else
      {
        // if there is one saved in the application state, use this
        var datasource = app.getStateManager().getState("datasource");
        if (!datasource)
        {
          app.setDatasourceLabel(app.getConfigManager().getKey("application.title"));
          var dsWin = app.getWidgetById("bibliograph/datasourceWindow");
          dsWin.open();
          dsWin.center();
        } else {
          app.setDatasource(datasource);
          app.getStateManager().updateState();
        }
      }

      /*
      * show datasource button depending on whether there is a choice
      */
      app.getWidgetById("bibliograph/datasourceButton").setVisibility(datasourceCount > 1 ? "visible" : "excluded");
    },
  }
});