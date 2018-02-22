/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2015 Christian Boulanger
 *
 * License: LGPL: http://www.gnu.org/licenses/lgpl.html EPL:
 * http://www.eclipse.org/org/documents/epl-v10.php See the LICENSE file in the
 * project's top-level directory for details.
 *
 * Authors: Christian Boulanger (cboulanger)
 *
 ******************************************************************************/

/* global qx qcl bibliograph */

/**


 */
qx.Class.define("bibliograph.ui.window.AccessControlTool",
{
  extend: qx.ui.window.Window,
  
  construct: function () {
    this.base(arguments);
    
    let pm = this.getApplication().getAccessManager().getPermissionManager();
    let bus = qx.event.message.Bus.getInstance();
    
    this.setCaption(this.tr('Access control tool'));
    this.setVisibility("visible");
    this.setWidth(800);
    
    // on appear
    this.addListener("appear", function (e) {
      this.center();
      this.selectBoxStore.setAutoLoadParams(null);
      this.selectBoxStore.setAutoLoadParams([]);
    }, this);
    
    // close on logout
    bus.subscribe("user.loggedout", function (e) {
      this.close();
    }, this);
    
    // layout
    let qxVbox1 = new qx.ui.layout.VBox(null, null, null);
    this.setLayout(qxVbox1);
    
    // toolbar
    let qxToolBar1 = new qx.ui.toolbar.ToolBar();
    this.add(qxToolBar1);
    
    // user button
    let addUserButton = new qx.ui.toolbar.Button(this.tr('New User'), "icon/22/apps/preferences-users.png");
    qxToolBar1.add(addUserButton);
    pm.create("access.manage").bind("state", addUserButton, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });
    addUserButton.addListener("execute", function (e) {
      this.leftSelectBox.setSelection([this.leftSelectBox.getSelectables()[0]]);
      this.getApplication().showPopup(this.tr("Please wait ..."));
      this.getApplication().getRpcClient("actool").send("newUserDialog");
    }, this);
    bus.subscribe("ldap.enabled", function (e) {
      addUserButton.setVisibility(e.getData() ? "excluded" : "visible");
    }, this);
    
    //  datasource button
    let addDatasourceButton = new qx.ui.toolbar.Button(this.tr('New Datasource'), "icon/22/apps/internet-transfer.png");
    qxToolBar1.add(addDatasourceButton);
    pm.create("access.manage").bind("state", addDatasourceButton, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });
    addDatasourceButton.addListener("execute", function (e) {
      this.leftSelectBox.setSelection([this.leftSelectBox.getSelectables()[4]]);
      this.getApplication().showPopup(this.tr("Please wait ..."));
      this.getApplication().getRpcClient("actool").send("newDatasourceDialog");
    }, this);
    
    // help button
    let helpButton = new qx.ui.toolbar.Button(this.tr('Help'), "icon/22/apps/utilities-help.png");
    qxToolBar1.add(helpButton);
    helpButton.addListener("execute", function (e) {
      this.getApplication().cmd("login", "access-control");
    }, this);
    
    qxToolBar1.addSpacer();
    
    // exit button
    let qxToolBarButton3 = new qx.ui.toolbar.Button(
    this.tr('Exit'), "icon/22/actions/application-exit.png", null);
    qxToolBar1.add(qxToolBarButton3);
    qxToolBarButton3.addListener("execute", function (e) {
      this.close();
    }, this);
    
    // group box container
    let qxGroupBox1 = new qx.ui.groupbox.GroupBox(null, null);
    this.add(qxGroupBox1, {flex: 1});
    
    let qxVbox2 = new qx.ui.layout.VBox(null, null, null);
    qxGroupBox1.setLayout(qxVbox2);
    
    // store for select box
    let selectBoxStore = new qcl.data.store.JsonRpcStore("access-config");
    this.selectBoxStore = selectBoxStore;
    selectBoxStore.setAutoLoadParams(null);
    selectBoxStore.setAutoLoadMethod("types");
    
    // store for left list
    let leftListStore = new qcl.data.store.JsonRpcStore("access-config");
    leftListStore.setAutoLoadMethod("elements");
    leftListStore.addListener("loaded", function (e) {
      let m = new qx.event.message.Message("leftListReloaded", e.getData ? e.getData() : []);
      m.setSender(e.getTarget());
      qx.event.message.Bus.getInstance().dispatch(m);
    }, this);
    qx.event.message.Bus.getInstance().subscribe("accessControlTool.reloadLeftList", function (e) {
      leftListStore.reload();
    }, this);
    
    // store for right list
    let rightListStore = new qcl.data.store.JsonRpcStore("access-config");
    rightListStore.setAutoLoadMethod("elements");
    qx.event.message.Bus.getInstance().subscribe("leftListReloaded", function (e) {
      rightListStore.setModel(null);
      rightListStore.setAutoLoadParams(null);
    }, this);
    qx.event.message.Bus.getInstance().subscribe("treeReloaded", function (e) {
      rightListStore.setModel(null);
      rightListStore.setAutoLoadParams(null);
    }, this);
    
    // store for tree
    let treeStore = new qcl.data.store.JsonRpcStore("access-config");
    treeStore.setAutoLoadMethod("tree");
    qx.event.message.Bus.getInstance().subscribe("leftListReloaded", function (e) {
      treeStore.setModel(null);
      treeStore.setAutoLoadParams(null);
    }, this);
    treeStore.addListener("loaded", function (e) {
      let m = new qx.event.message.Message("treeReloaded", e.getData ? e.getData() : []);
      m.setSender(e.getTarget());
      qx.event.message.Bus.getInstance().dispatch(m);
    }, this);
    
    // layout for three columns
    let qxHbox1 = new qx.ui.layout.HBox(10, null, null);
    let qxComposite1 = new qx.ui.container.Composite();
    qxComposite1.setLayout(qxHbox1);
    qxComposite1.setAllowStretchY(true);
    qxGroupBox1.add(qxComposite1, {flex: 1});
    qxHbox1.setSpacing(10);
    let qxVbox3 = new qx.ui.layout.VBox(10, null, null);
    let qxComposite2 = new qx.ui.container.Composite();
    qxComposite2.setLayout(qxVbox3);
    qxComposite1.add(qxComposite2, {flex: 1});
    qxVbox3.setSpacing(10);
    
    // select box
    let leftSelectBox = new qx.ui.form.SelectBox();
    this.leftSelectBox = leftSelectBox;
    qxComposite2.add(leftSelectBox);
    let leftSelectBoxController = new qx.data.controller.List(null, leftSelectBox, "label");
    leftSelectBoxController.setIconPath("icon");
    this.selectBoxStore.bind("model", leftSelectBoxController, "model");
    leftSelectBox.bind("selection", leftListStore, "autoLoadParams", {
      converter: function (sel) {
        return sel.length ? sel[0].getModel().getValue() : null
      }
    });
    
    // left list
    let leftList = new qx.ui.form.List();
    qxComposite2.add(leftList, {flex: 1});
    let leftListController = new qx.data.controller.List(null, leftList, "label");
    leftListController.setIconPath("icon");
    leftListStore.bind("model", leftListController, "model");
    leftList.bind("selection", treeStore, "autoLoadParams", {
      converter: function (sel) {
        return sel.length ? sel[0].getModel().getParams() : null
      }
    });
    let qxHbox2 = new qx.ui.layout.HBox(10, null, null);
    let qxComposite3 = new qx.ui.container.Composite();
    qxComposite3.setLayout(qxHbox2);
    qxComposite2.add(qxComposite3);
    qxHbox2.setSpacing(10);
    
    // "Add" button
    let qxButton1 = new qx.ui.form.Button(null, "bibliograph/icon/button-plus.png", null);
    qxButton1.setEnabled(false);
    qxComposite3.add(qxButton1);
    leftSelectBox.bind("selection", qxButton1, "enabled", {
      converter: (s) => s.length > 0
    });
    qxButton1.addListener("execute", function (e) {
      let type = leftSelectBox.getSelection()[0].getModel().getValue();
      let msg = this.tr("Please enter the id of the new '%1'-Object", /* this.tr( */
      type/* ) */);
      dialog.Dialog.prompt(msg, function (name) {
        if (name) {
          leftListStore.execute("add", [type, name], function () {
            leftListStore.reload();
          });
        }
      });
    }, this);
    
    // "Delete" button
    let qxButton2 = new qx.ui.form.Button(null, "bibliograph/icon/button-minus.png", null);
    qxButton2.setEnabled(false);
    qxComposite3.add(qxButton2);
    leftList.bind("selection", qxButton2, "enabled", {
      converter: (s) => s.length > 0
    });
    qxButton2.addListener("execute", function (e) {
      let itemModel = leftList.getSelection()[0].getModel();
      let name = itemModel.getValue();
      let type = itemModel.getType();
      let msg = this.tr("Do you really want to delete '%1'?", name);
      dialog.Dialog.confirm(msg, function (yes) {
        if (yes) {
          leftListStore.execute("delete", [type, name], function () {
            leftListStore.reload();
          });
        }
      });
    }, this);
    
    // "Edit" button
    let editButton = new qx.ui.form.Button(null, "bibliograph/icon/button-edit.png", null);
    editButton.setEnabled(false);
    qxComposite3.add(editButton);
    leftList.bind("selection", editButton, "enabled", {
      converter: function (s) {
        return s.length > 0
      }
    });
    editButton.addListener("execute", function (e) {
      let itemModel = leftList.getSelection()[0].getModel();
      let type = itemModel.getType();
      let name = itemModel.getValue();
      // this triggers a server dialog response
      this.getApplication().showPopup(this.tr("Loading data ..."));
      leftListStore.execute("edit", [type, name], function () {
        this.getApplication().hidePopup();
      }, this);
    }, this);
    
    // reload button
    let reloadBtn = new qx.ui.form.Button(null, "bibliograph/icon/button-reload.png", null);
    reloadBtn.setIcon("bibliograph/icon/button-reload.png");
    qxComposite3.add(reloadBtn);
    reloadBtn.addListener("execute", function (e) {
      leftListStore.reload();
    }, this);
    
    // Email button
    // let emailBtn = new qx.ui.form.Button(null, "bibliograph/icon/button-mail.png", null);
    // emailBtn.setEnabled(false);
    // qxComposite3.add(emailBtn);
    // leftList.bind("selection", emailBtn, "enabled", {
    //   converter : function(s) {
    //       return s.length > 0 && ["user","group"].indexOf(s[0].getModel().getType()) > -1
    //   }
    // });
    // emailBtn.addListener("execute", function(e) {
    //   let itemModel = leftList.getSelection()[0].getModel();
    //   let type = itemModel.getType();
    //   let name = itemModel.getValue();
    //   this.getApplication().showPopup(this.tr("Please wait..."));
    //   leftListStore.execute("compose-email", [type, name], function() {
    //     this.getApplication().hidePopup();
    //   }, this);
    // }, this);
    
    // Label for edited element
    let qxVbox4 = new qx.ui.layout.VBox(10, null, null);
    let qxComposite4 = new qx.ui.container.Composite();
    qxComposite4.setLayout(qxVbox4);
    qxComposite1.add(qxComposite4, {flex: 2});
    qxVbox4.setSpacing(10);
    let centerLabel = new qx.ui.basic.Label(this.tr('Edited element'));
    centerLabel.setValue(this.tr('Edited element'));
    centerLabel.setMaxWidth(250);
    centerLabel.setHeight(20);
    qxComposite4.add(centerLabel);
    leftList.bind("selection", centerLabel, "value", {
      converter: function (sel) {
        return sel.length ? sel[0].getLabel() : null
      }
    });
    
    // Tree of linked Elements
    let elementTree = new qx.ui.tree.Tree();
    qxComposite4.add(elementTree, {flex: 1});
    let treeController = new qx.data.controller.Tree(null, elementTree, "children", "label");
    treeController.setIconPath("icon");
    treeStore.bind("model", treeController, "model", {});
    treeController.setDelegate({
      configureItem: function (item) {
        item.setOpen(true);
      }
    });
    elementTree.bind("selection", rightListStore, "autoLoadParams", {
      converter: function (selection) {
        return selection.length ? selection[0].getModel().getType() : null;
      }
    });
    elementTree.addListener("changeSelection", function (e) {
      let m = new qx.event.message.Message("treeSelectionChanged", e.getData ? e.getData() : []);
      m.setSender(e.getTarget());
      qx.event.message.Bus.getInstance().dispatch(m);
    }, this);
    let qxHbox3 = new qx.ui.layout.HBox(10, null, null);
    let qxComposite5 = new qx.ui.container.Composite();
    qxComposite5.setLayout(qxHbox3);
    qxComposite4.add(qxComposite5);
    qxHbox3.setSpacing(10);
    
    // link button
    let qxButton5 = new qx.ui.form.Button(this.tr('Link'), null, null);
    qxButton5.setEnabled(false);
    qxButton5.setLabel(this.tr('Link'));
    qxComposite5.add(qxButton5);
    pm.create("allowLink").bind("state", qxButton5, "enabled");
    qxButton5.addListener("execute", function (e) {
      let treeModel = elementTree.getSelection()[0].getModel();
      let rightModel = rightList.getSelection()[0].getModel();
      let params = [treeModel.getValue(), rightModel.getType(), rightModel.getValue()];
      treeStore.execute("link", params, function () {
        treeStore.reload();
      });
    }, this);
    
    // Unlink button
    let qxButton6 = new qx.ui.form.Button(this.tr('Unlink'), null, null);
    qxButton6.setEnabled(false);
    qxButton6.setLabel(this.tr('Unlink'));
    qxComposite5.add(qxButton6);
    pm.create("allowUnlink").bind("state", qxButton6, "enabled");
    qxButton6.addListener("execute", function (e) {
      let leftModel = leftList.getSelection()[0].getModel();
      let treeModel = elementTree.getSelection()[0].getModel();
      let params = [treeModel.getValue(), leftModel.getType(), leftModel.getValue()];
      treeStore.execute("unlink", params, function () {
        treeStore.reload();
      });
    }, this);
    let qxVbox5 = new qx.ui.layout.VBox(10, null, null);
    let qxComposite6 = new qx.ui.container.Composite();
    qxComposite6.setLayout(qxVbox5);
    qxComposite1.add(qxComposite6, {
      flex: 1
    });
    qxVbox5.setSpacing(10);
    
    // Linkable items
    let rightLabel = new qx.ui.basic.Label(this.tr('Linkable items'));
    rightLabel.setValue(this.tr('Linkable items'));
    rightLabel.setRich(true);
    rightLabel.setHeight(20);
    qxComposite6.add(rightLabel);
    let rightList = new qx.ui.form.List();
    rightList.setSelectionMode("multi");
    rightList.setWidgetId("bibliograph/acltool-rightList");
    qxComposite6.add(rightList, {flex: 1});
    let rightListController = new qx.data.controller.List(null, rightList, "label");
    rightListController.setIconPath("icon");
    rightListStore.bind("model", rightListController, "model");
    rightList.addListener("changeSelection", function (e) {
      let m = new qx.event.message.Message("rightListSelectionChanged", e.getData ? e.getData() : []);
      m.setSender(e.getTarget());
      qx.event.message.Bus.getInstance().dispatch(m);
    }, this);
    let qxHbox4 = new qx.ui.layout.HBox(10, null, null);
    let qxComposite7 = new qx.ui.container.Composite();
    qxComposite7.setLayout(qxHbox4);
    qxComposite6.add(qxComposite7);
    qxHbox4.setSpacing(10);
    let qxButton7 = new qx.ui.form.Button(null, "bibliograph/icon/button-plus.png", null);
    qxButton7.setEnabled(false);
    qxButton7.setIcon("bibliograph/icon/button-plus.png");
    qxComposite7.add(qxButton7);
    elementTree.bind("selection", qxButton7, "enabled", {
      converter: (s) => s.length > 0
    });
    qxButton7.addListener("execute", function (e) {
      let type = elementTree.getSelection()[0].getModel().getType();
      let msg = this.tr("Please enter the id of the new '%1'-Object", /* this.tr( */
      type/* ) */);
      dialog.Dialog.prompt(msg, function (name) {
        if (name) {
          rightListStore.execute("add", [type, name], function () {
            rightListStore.reload();
          });
        }
      });
    }, this);
    let qxButton8 = new qx.ui.form.Button(null, "bibliograph/icon/button-minus.png", null);
    qxButton8.setEnabled(false);
    qxButton8.setIcon("bibliograph/icon/button-minus.png");
    qxComposite7.add(qxButton8);
    rightList.bind("selection", qxButton8, "enabled", {
      converter: (s) => s.length > 0
    });
    qxButton8.addListener("execute", function (e) {
      let selection = rightList.getSelection();
      let names = [];
      let types = [];
      selection.forEach(function (item) {
        let itemModel = item.getModel();
        names.push(itemModel.getValue());
        types.push(itemModel.getType());
      });
      let msg = this.tr("Do you really want to delete the objects '%1'?", names.join(", "));
      dialog.Dialog.confirm(msg, function (yes) {
        if (yes) {
          rightListStore.execute("delete", [types[0], names], function () {
            rightListStore.reload();
          });
        }
      });
    }, this);
    let qxButton9 = new qx.ui.form.Button(null, "bibliograph/icon/button-edit.png", null);
    qxButton9.setEnabled(false);
    qxButton9.setIcon("bibliograph/icon/button-edit.png");
    qxComposite7.add(qxButton9);
    rightList.bind("selection", qxButton9, "enabled", {
      converter: (s) => s.length > 0
    });
    qxButton9.addListener("execute", function (e) {
      let itemModel = rightList.getSelection()[0].getModel();
      let type = itemModel.getType();
      let name = itemModel.getValue();
      // this triggers a server dialog response
      this.getApplication().showPopup(this.tr("Loading data ..."));
      rightListStore.execute("edit", [type, name], function () {
        this.getApplication().hidePopup();
      }, this);
    }, this);
    let qxButton10 = new qx.ui.form.Button(null, "bibliograph/icon/button-reload.png", null);
    qxButton10.setIcon("bibliograph/icon/button-reload.png");
    qxComposite7.add(qxButton10);
    qxButton10.addListener("execute", function (e) {
      rightListStore.reload();
    }, this);
    
    // update permissions
    let allowLinkPermission = pm.create("allowLink");
    allowLinkPermission.setGranted(true);
    allowLinkPermission.addCondition(() => {
      let treeSelection = elementTree.getSelection();
      return (
      treeSelection.length > 0
      && treeSelection[0].getModel()
      && treeSelection[0].getModel().getAction() === "link"
      && rightList.getSelection().length > 0);
    });
    qx.event.message.Bus.subscribe("leftListReloaded", () => allowLinkPermission.update());
    qx.event.message.Bus.subscribe("treeSelectionChanged", () => allowLinkPermission.update());
    qx.event.message.Bus.subscribe("rightListSelectionChanged", () => allowLinkPermission.update());
    
    let allowUnlinkPermission = pm.create("allowUnlink");
    allowUnlinkPermission.setGranted(true);
    allowUnlinkPermission.addCondition(() => {
      let treeSelection = elementTree.getSelection();
      return (
      treeSelection.length > 0
      && treeSelection[0].getModel()
      && treeSelection[0].getModel().getAction() === "unlink");
    });
    qx.event.message.Bus.subscribe("leftListReloaded", () => allowUnlinkPermission.update());
    qx.event.message.Bus.subscribe("treeSelectionChanged", () => allowUnlinkPermission.update());
    qx.event.message.Bus.subscribe("rightListSelectionChanged", () => allowUnlinkPermission.update());
  }
});
