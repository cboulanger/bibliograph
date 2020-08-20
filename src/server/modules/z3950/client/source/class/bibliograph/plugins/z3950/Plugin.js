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

/**
 * Z39.50 Plugin:
 *    This plugin allows to import references from Z39.50 datasources
 *
 */
qx.Class.define("bibliograph.plugins.z3950.Plugin",
{
  extend: qcl.application.BasePlugin,
  include: [qx.locale.MTranslation],
  type: "singleton",
  members: {

    /**
     * Returns the name of the plugin
     * @returns {string}
     */
    getName : function() {
      return "Z39.50 Datasource Connector";
    },

    /**
     * Initialize the plugin
     * @return void
     */
    init: function () {
      // Manager shortcuts
      let app = qx.core.Init.getApplication();
      let permMgr = app.getAccessManager().getPermissionManager();
  
      // remote search progress indicator widget
      let z3950Progress = new qcl.ui.dialog.ServerProgress("plugin-z3950-progress", "z3950/search/progress");
      z3950Progress.set({
        hideWhenCompleted: true,
        allowCancel : true
      });
      
      // add window
      let importWindow = new bibliograph.plugins.z3950.ImportWindow();
      qx.core.Id.getQxObject("windows").addOwnedQxObject(importWindow, "plugin-z3950-import");
      app.getRoot().add(importWindow);
  

      // add a new menu button
      
      let menuButton = new qx.ui.menu.Button(this.tr("Import from library catalog"));
      menuButton.addListener("execute", () => importWindow.show());
      qx.core.Id.getQxObject("toolbar/import").getMenu().add(menuButton);
      
      let prefsTabView = qx.core.Id.getQxObject("windows/preferences").tabView; // to do use child widget
      let pluginTab = new qx.ui.tabview.Page(this.tr("Z39.50 Import"));
      
      // ACL
      permMgr.create("z3950.manage").bind("state", pluginTab.getChildControl("button"), "visibility", {
        converter: v => v ? "visible" : "excluded"
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
          controller.bindProperty("active", "value", { converter: v => v === 1 }, item, id);
          controller.bindPropertyReverse("active", "value", { converter: v => v === 1 }, item, id);
        }
      };
      list.setDelegate(delegate);
      
      let store = new qcl.data.store.JsonRpcStore("z3950.table");
      store.setModel(qx.data.marshal.Json.createModel([]));
      store.bind("model", list, "model");
      pluginTab.addListener("appear", e => {
        store.load("server-list", [false]);
      });
      qx.event.message.Bus.getInstance().subscribe("plugins.z3950.reloadDatasources", e => {
        store.load("server-list", [false]);
      });
      
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
        store.execute("set-datasource-state", [result], () => {
          this.getApplication().hidePopup();
          saveButton.setEnabled(true);
        });
      }, this);
      
      // Reload datassources Button
      let reloadButton = new qx.ui.form.Button(this.tr("Reload"));
      buttons.add(reloadButton);
      reloadButton.addListener("execute", () => {
        reloadButton.setEnabled(false);
        this.getApplication().showPopup(this.tr("Reloading library metadata..."));
        store.load("server-list", [false, true], () => {
          this.getApplication().hidePopup();
          reloadButton.setEnabled(true);
        });
      });
      
      // add tab to tabview (must be done at the end)
      prefsTabView.add(pluginTab);
    }
  }
});
