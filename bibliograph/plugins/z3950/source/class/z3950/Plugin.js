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

/*global qx qcl z3950*/

/**
 * Z39.50 Plugin:
 *    This plugin allows to import references from Z39.50 datasources
 * 
 */
qx.Class.define("z3950.Plugin",
{
  extend : qx.core.Object,
  include : [qx.locale.MTranslation],
  type : "singleton",
  members : {
    /**
     * TODOC
     *
     * @return {void}
     */
    init : function()
    {
      
      // Manager shortcuts
      var app = qx.core.Init.getApplication();
      var permMgr = app.getAccessManager().getPermissionManager();
      var confMgr = app.getConfigManager();
      
      
      /*
       * add window
       */
      var app = qx.core.Init.getApplication();
      var importWindow = new z3950.ImportWindowUi();
      app.getRoot().add(importWindow);

      /*
       * add a new menu button
       */
      var importMenu = app.getWidgetById("bibliograph/importMenu");
      var menuButton = new qx.ui.menu.Button(this.tr("Import from library catalog"));
      menuButton.addListener("execute", function() {
        importWindow.show();
      });
      importMenu.add(menuButton);
      
      
      /*
       * Overlays for preference window
       */

      var prefsTabView = app.getWidgetById("bibliograph/preferences-tabView");
      var pluginTab = new qx.ui.tabview.Page( this.tr('Z39.50 Import') );

      // ACL
      permMgr.create("z3950.manage").bind("state", pluginTab.getChildControl("button"), "visibility", {
        converter : function(v){ return v ? "visible" : "excluded" }
      });
      var vboxlayout = new qx.ui.layout.VBox(5);
      pluginTab.setLayout(vboxlayout);


      // create virtual list
      var list = new qx.ui.list.List();
      list.setWidth(150);
      pluginTab.add(list);

      // create the delegate to change the bindings
      var delegate = {
        configureItem : function(item) {
          item.setPadding(3);
        },
        createItem : function() {
          return new qx.ui.form.CheckBox();
        },
        bindItem : function(controller, item, id) {
          controller.bindProperty("label", "label", null, item, id);
          controller.bindProperty("active", "value", null, item, id);
          controller.bindPropertyReverse("active", "value", null, item, id);
        }
      };
      list.setDelegate(delegate);

      var store = new qcl.data.store.JsonRpc(null,"z3950.Service");
      store.setModel( qx.data.marshal.Json.createModel([]) );
      store.bind("model",list,"model");
      store.load("getServerListItems",[false]);
      qx.event.message.Bus.getInstance().subscribe("z3950.reloadDatasources", function(e) {
        store.load("getServerListItems",[false]);
      }, this);      

      // buttons
      var hbox = new qx.ui.layout.HBox(10);
      var buttons = new qx.ui.container.Composite();
      buttons.setLayout(hbox);
      pluginTab.add(buttons);
      
      // Toggle Button
      var statusButton = new qx.ui.form.Button(this.tr("(Un-)Select All"));
      buttons.add(statusButton);
      var statusSelectAll = false;
      statusButton.addListener("execute", function() {
        var model = store.getModel();
        statusSelectAll = ! statusSelectAll;
        for (var i = 0; i < model.length; i++) {
          model.getItem(i).setActive(statusSelectAll);
        }
      }, this); 
      
      // Save data Button
      var saveButton = new qx.ui.form.Button(this.tr("Save"));
      buttons.add(saveButton);
      saveButton.addListener("execute", function() {
        var model = store.getModel();
        var result = {};
        for (var i = 0; i < model.length; i++) {
          var name   = model.getItem(i).getValue();
          var active = model.getItem(i).getActive();
          result[name] = active;
        }
        store.execute("setDatasourceState",[result]);
      }, this);       
      
      // Reload datassources Button
      var reloadButton= new qx.ui.form.Button(this.tr("Reload"));
      buttons.add(reloadButton);
      reloadButton.addListener("execute",function(){
        store.load("getServerListItems",[false,true]);
      });
      
      // add tab to tabview (must be done at the end)
      prefsTabView.add(pluginTab);
    }
  }
});