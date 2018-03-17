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
qx.Class.define("plugins.z3950.Plugin",
{
  extend: qx.core.Object,
  include: [qx.locale.MTranslation],
  type: "singleton",
  members: {
    init: function () {
      
      // Manager shortcuts
      let app = qx.core.Init.getApplication();
      let permMgr = app.getAccessManager().getPermissionManager();
      let confMgr = app.getConfigManager();
      
      
      // add window
      let importWindow = new z3950.ImportWindowUi();
      app.getRoot().add(importWindow);
      
      // add a new menu button
      let importMenu = app.getWidgetById("app/toolbar/import");
      let menuButton = new qx.ui.menu.Button(this.tr("Import from library catalog"));
      menuButton.addListener("execute", function () {
        importWindow.show();
      });
      importMenu.add(menuButton);
      
      // Overlays for preference window @todo rename
      let prefsTabView = app.getWidgetById("bibliograph/preferences-tabView");
      let pluginTab = new qx.ui.tabview.Page(this.tr('Z39.50 Import'));
      
      // ACL
      permMgr.create("z3950.manage").bind("state", pluginTab.getChildControl("button"), "visibility", {
        converter: function (v) {
          return v ? "visible" : "excluded"
        }
      });
      let vboxlayout = new qx.ui.layout.VBox(5);
      pluginTab.setLayout(vboxlayout);
      
      // create virtual list
      let list = new qx.ui.list.List();
      list.setWidth(150);
      pluginTab.add(list);
      
      // create the delegate to change the bindings
      let delegate = {
        configureItem: function (item) {
          item.setPadding(3);
        },
        createItem: function () {
          return new qx.ui.form.CheckBox();
        },
        bindItem: function (controller, item, id) {
          controller.bindProperty("label", "label", null, item, id);
          controller.bindProperty("active", "value", null, item, id);
          controller.bindPropertyReverse("active", "value", null, item, id);
        }
      };
      list.setDelegate(delegate);
      
      let store = new qcl.data.store.JsonRpc(null, "z3950.Service");
      store.setModel(qx.data.marshal.Json.createModel([]));
      store.bind("model", list, "model");
      store.load("getServerListItems", [false]);
      qx.event.message.Bus.getInstance().subscribe("z3950.reloadDatasources", function (e) {
        store.load("getServerListItems", [false]);
      }, this);
      
      // buttons
      let hbox = new qx.ui.layout.HBox(10);
      let buttons = new qx.ui.container.Composite();
      buttons.setLayout(hbox);
      pluginTab.add(buttons);
      
      // Toggle Button
      let statusButton = new qx.ui.form.Button(this.tr("(Un-)Select All"));
      buttons.add(statusButton);
      let statusSelectAll = false;
      statusButton.addListener("execute", function () {
        let model = store.getModel();
        statusSelectAll = !statusSelectAll;
        for (let i = 0; i < model.length; i++) {
          model.getItem(i).setActive(statusSelectAll);
        }
      }, this);
      
      // Save data Button
      let saveButton = new qx.ui.form.Button(this.tr("Save"));
      buttons.add(saveButton);
      saveButton.addListener("execute", function () {
        let model = store.getModel();
        let result = {};
        for (let i = 0; i < model.length; i++) {
          let name = model.getItem(i).getValue();
          result[name] = model.getItem(i).getActive();
        }
        saveButton.setEnabled(false);
        this.getApplication().showPopup(this.tr("Saving..."));
        store.execute("setDatasourceState", [result], function () {
          this.getApplication().hidePopup();
          saveButton.setEnabled(true);
        }, this);
      }, this);
      
      // Reload datassources Button
      let reloadButton = new qx.ui.form.Button(this.tr("Reload"));
      buttons.add(reloadButton);
      reloadButton.addListener("execute", function () {
        reloadButton.setEnabled(false);
        this.getApplication().showPopup(this.tr("Reloading library metadata..."));
        store.load("getServerListItems", [false, true], function () {
          this.getApplication().hidePopup();
          reloadButton.setEnabled(true);
        }, this);
      });
      
      // add tab to tabview (must be done at the end)
      prefsTabView.add(pluginTab);
      
      // remote search progress indicator widget
      let z3950Progress = new qcl.ui.dialog.ServerProgress( "z3950Progress", "z3950/search", "progress");
      z3950Progress.set({
        hideWhenCompleted: true
      });
    }
  }
});