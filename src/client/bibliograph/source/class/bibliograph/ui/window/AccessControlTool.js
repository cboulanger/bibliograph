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

/* global  qcl bibliograph */

/**
 * The Access configuration window
 */
qx.Class.define("bibliograph.ui.window.AccessControlTool",
{
  extend: qx.ui.window.Window,
  
  construct: function () {
    this.base(arguments);
  
    const app = qx.core.Init.getApplication();
    const pm  = app.getPermissionManager();
    const bus = qx.event.message.Bus.getInstance();
    
    this.setCaption(this.tr('Access control tool'));
    //this.setVisibility("excluded");
    this.setWidth(800);
    this.addListener("appear", (e) => this.center());
    
    // close on logout
    bus.subscribe("user.loggedout", function (e) {
      this.close();
    }, this);
  
    /*
     ---------------------------------------------------------------------------
        PERMISSIONS
     ---------------------------------------------------------------------------
     */
  
    const allowLinkPermission = pm.create("act.allowLink").set({granted:true});
    const allowUnlinkPermission = pm.create("act.allowUnlink").set({granted:true});
  
    // closure vars:
    // elementTree
    
    // add conditions
    allowLinkPermission.addCondition(() => {
      let treeSelection = elementTree.getSelection();
      return (
      treeSelection.length > 0
      && treeSelection[0].getModel()
      && treeSelection[0].getModel().getAction() === "link"
      && rightList.getSelection().length > 0);
    });
    allowUnlinkPermission.addCondition(() => {
      let treeSelection = elementTree.getSelection();
      return (
      treeSelection.length > 0
      && treeSelection[0].getModel()
      && treeSelection[0].getModel().getAction() === "unlink");
    });
    // update on events
    // @todo convert messages to events
    bus.subscribe("leftListReloaded", () => {
      allowLinkPermission.update();
      allowUnlinkPermission.update();
    });
    bus.subscribe("treeSelectionChanged", () => {
      allowLinkPermission.update();
      allowUnlinkPermission.update();
    });
    bus.subscribe("rightListSelectionChanged", () => {
      allowLinkPermission.update();
      allowUnlinkPermission.update();
    });
    
    /*
     ---------------------------------------------------------------------------
        STORES & SERVICES
     ---------------------------------------------------------------------------
     */
  
    // store for select box
    this.selectBoxStore = new qcl.data.store.JsonRpcStore("access-config");
    this.selectBoxStore.set({
      autoLoadMethod : "types",
      autoLoadParams : null
    });
  
    // on appear
    this.addListener("appear", function (e) {
      this.selectBoxStore.setAutoLoadParams(null);
      this.selectBoxStore.setAutoLoadParams([]);
    }, this);
  
  
    // store for left list
    const leftListStore = new qcl.data.store.JsonRpcStore("access-config");
    leftListStore.set({
      autoLoadMethod : "elements"
    });
    // @todo this message stuff smells bad...
    leftListStore.addListener("loaded", e => {
      let m = new qx.event.message.Message("leftListReloaded", e.getData ? e.getData() : []);
      m.setSender(e.getTarget());
      bus.dispatch(m);
    }, this);
    bus.subscribe("accessControlTool.reloadLeftList", () => leftListStore.canReload() && leftListStore.reload());
  
    // store for right list
    const rightListStore = new qcl.data.store.JsonRpcStore("access-config");
    rightListStore.setAutoLoadMethod('elements');
    bus.subscribe("leftListReloaded", () => {
      rightListStore.setModel(null);
      rightListStore.setAutoLoadParams(null);
    });
    bus.subscribe("treeReloaded", e => {
      rightListStore.setModel(null);
      rightListStore.setAutoLoadParams(null);
    });
  
    // store for tree
    const treeStore = new qcl.data.store.JsonRpcStore("access-config");
    treeStore.setAutoLoadMethod("tree");
    bus.subscribe("leftListReloaded", () => {
      treeStore.setModel(null);
      treeStore.setAutoLoadParams(null);
    });
    treeStore.addListener("loaded", e => {
      let m = new qx.event.message.Message("treeReloaded", e.getData ? e.getData() : []);
      m.setSender(e.getTarget());
      bus.dispatch(m);
    });
  
    /**
     * This is a globally addressable proxy method
     * @todo Implement this differently!
     * @param method
     * @param params
     * @param callback
     * @private
     */
    bibliograph._actRpcSendProxy = ( method, params, callback) => {
      //this.debug(arguments);
      this.getApplication().getRpcClient("access-config").send(method,params)
      .then((response)=>{
        //this.debug("Server response: " + response);
        callback(response);
      });
    };
    
    /*
    ---------------------------------------------------------------------------
       LAYOUT
    ---------------------------------------------------------------------------
    */

    this.setLayout(new qx.ui.layout.VBox());
    
    // toolbar
    let toolBar1 = new qx.ui.toolbar.ToolBar();
    this.add(toolBar1);
    
    // user button
    let addUserButton = new qx.ui.toolbar.Button(this.tr('New User'), "icon/22/apps/preferences-users.png");
    toolBar1.add(addUserButton);
    addUserButton.setEnabled(false);
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
    toolBar1.add(addDatasourceButton);
    pm.create("access.manage").bind("state", addDatasourceButton, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });
    // @todo rewrite
    addDatasourceButton.addListener("execute", function (e) {
      this.leftSelectBox.setSelection([this.leftSelectBox.getSelectables()[4]]);
      rpc.AccessConfig.createDatasourceDialog();
    }, this);
    
    // help button
    let helpButton = new qx.ui.toolbar.Button(this.tr('Help'), "icon/22/apps/utilities-help.png");
    toolBar1.add(helpButton);
    helpButton.addListener("execute", function (e) {
      this.getApplication().cmd("login", "access-control");
    }, this);
    
    toolBar1.addSpacer();
    
    // exit button
    let toolBarButton3 = new qx.ui.toolbar.Button(
    this.tr('Exit'), "icon/22/actions/application-exit.png", null);
    toolBar1.add(toolBarButton3);
    toolBarButton3.addListener("execute", function (e) {
      this.close();
    }, this);
    
    // group box container
    let groupBox1 = new qx.ui.groupbox.GroupBox(null, null);
    this.add(groupBox1, {flex: 1});
    
    let vbox2 = new qx.ui.layout.VBox(null, null, null);
    groupBox1.setLayout(vbox2);
    
    // layout for three columns
    let hbox1 = new qx.ui.layout.HBox(10, null, null);
    let composite1 = new qx.ui.container.Composite();
    composite1.setLayout(hbox1);
    composite1.setAllowStretchY(true);
    groupBox1.add(composite1, {flex: 1});
    hbox1.setSpacing(10);
    let vbox3 = new qx.ui.layout.VBox(10, null, null);
    let composite2 = new qx.ui.container.Composite();
    composite2.setLayout(vbox3);
    composite1.add(composite2, {flex: 1});
    vbox3.setSpacing(10);
    
    // select box
    let leftSelectBox = new qx.ui.form.SelectBox();
    this.leftSelectBox = leftSelectBox;
    composite2.add(leftSelectBox);
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
    composite2.add(leftList, {flex: 1});
    let leftListController = new qx.data.controller.List(null, leftList, "label");
    leftListController.setIconPath("icon");
    leftListStore.bind("model", leftListController, "model");
    leftList.bind("selection", treeStore, "autoLoadParams", {
      converter: function (sel) {
        return sel.length ? sel[0].getModel().getParams() : null
      }
    });
    let hbox2 = new qx.ui.layout.HBox(10, null, null);
    let composite3 = new qx.ui.container.Composite();
    composite3.setLayout(hbox2);
    composite2.add(composite3);
    hbox2.setSpacing(10);
    
    // "Add" button
    let button1 = new qx.ui.form.Button(null, "bibliograph/icon/button-plus.png", null);
    button1.setEnabled(false);
    composite3.add(button1);
    leftSelectBox.bind("selection", button1, "enabled", {
      converter: (s) => s.length > 0
    });
    button1.addListener("execute", async () => {
      let selection = leftSelectBox.getSelection();
      let type = selection.length ? selection[0].getModel().getValue() : null;
      if( ! type) {
        this.warn("Cannot get type!");
        return;
      }
      if( type === "datasource" ){
        await rpc.AccessConfig.createDatasourceDialog();
      } else {
        let msg = this.tr("Please enter the id of the new '%1'-Object", type);
        let name = await dialog.Dialog.prompt(msg).promise();
        if (name) {
          await rpc.AccessConfig.add(type, name,null,true);
          leftListStore.reload();
        }
      }
    });
    
    // "Delete" button
    let button2 = new qx.ui.form.Button(null, "bibliograph/icon/button-minus.png", null);
    button2.setEnabled(false);
    composite3.add(button2);
    leftList.bind("selection", button2, "enabled", {
      converter: (s) => s.length > 0
    });
    button2.addListener("execute", function (e) {
      if( ! leftList.getSelection().length ) return;
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
    composite3.add(editButton);
    leftList.bind("selection", editButton, "enabled", {
      converter: function (s) {
        return s.length > 0
      }
    });
    editButton.addListener("execute", () => {
      if( ! leftList.getSelection().length ) return;
      let itemModel = leftList.getSelection()[0].getModel();
      let type = itemModel.getType();
      let name = itemModel.getValue();
      // this triggers a server dialog response
      this.getApplication().showPopup(this.tr("Loading data ..."));
      leftListStore.execute("edit", [type, name], function () {
        this.getApplication().hidePopup();
      }, this);
    });
    
    // reload button
    let reloadBtn = new qx.ui.form.Button(null, "bibliograph/icon/button-reload.png", null);
    reloadBtn.setIcon("bibliograph/icon/button-reload.png");
    composite3.add(reloadBtn);
    reloadBtn.addListener("execute", function (e) {
      leftListStore.reload();
    }, this);
    
    // Email button
    // let emailBtn = new qx.ui.form.Button(null, "bibliograph/icon/button-mail.png", null);
    // emailBtn.setEnabled(false);
    // composite3.add(emailBtn);
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
    let vbox4 = new qx.ui.layout.VBox(10, null, null);
    let composite4 = new qx.ui.container.Composite();
    composite4.setLayout(vbox4);
    composite1.add(composite4, {flex: 2});
    vbox4.setSpacing(10);
    let centerLabel = new qx.ui.basic.Label();
    centerLabel.set({
      value: this.tr('Edited element'),
      maxWidth : 250,
      height : 20,
      rich : true
    });
    composite4.add(centerLabel);
    leftList.bind("selection", centerLabel, "value", {
      converter: function (sel) {
        return sel.length ? '<b>' + sel[0].getLabel() + '</b>' : null
      }
    });
    
    // Tree of linked Elements
    const elementTree = new qx.ui.tree.Tree();
    composite4.add(elementTree, {flex: 1});
    const treeController = new qx.data.controller.Tree(null, elementTree, "children", "label");
    treeController.setIconPath("icon");
    treeStore.bind("model", treeController, "model", {});
    treeController.setDelegate({
      configureItem: function (item) {
        item.setOpen(true);
      }
    });

    // Tree bindings
    elementTree.bind("selection", rightListStore, "autoLoadParams", {
      converter: function (selection) {
        if( selection.length ){
         let model = selection[0].getModel();
         return model.getAction() === "link" ? model.getType() : null;
        }
        return null;
      }
    });
    elementTree.addListener("changeSelection", function (e) {
      let m = new qx.event.message.Message("treeSelectionChanged", e.getData());
      m.setSender(e.getTarget());
      bus.dispatch(m);
    }, this);

    // tree button pane
    let hbox3 = new qx.ui.layout.HBox(10, null, null);
    let composite5 = new qx.ui.container.Composite();
    composite5.setLayout(hbox3);
    composite4.add(composite5);
    hbox3.setSpacing(10);
    
    // link button
    let button5 = new qx.ui.form.Button(this.tr('Link'), null, null);
    button5.setEnabled(false);
    button5.setLabel(this.tr('Link'));
    composite5.add(button5);
    allowLinkPermission.bind("state", button5, "enabled");
    button5.addListener("execute", () => {
      let treeModel = elementTree.getSelection()[0].getModel();
      let rightModel = rightList.getSelection()[0].getModel();
      let params = [treeModel.getValue(), rightModel.getType(), rightModel.getValue()];
      treeStore.execute("link", params, () => treeStore.reload() );
    });
    
    // Unlink button
    let button6 = new qx.ui.form.Button(this.tr('Unlink'), null, null);
    button6.setEnabled(false);
    button6.setLabel(this.tr('Unlink'));
    composite5.add(button6);
    allowUnlinkPermission.bind("state", button6, "enabled");
    button6.addListener("execute", function (e) {
      let leftModel = leftList.getSelection()[0].getModel();
      let treeModel = elementTree.getSelection()[0].getModel();
      let params = [treeModel.getValue(), leftModel.getType(), leftModel.getValue()];
      treeStore.execute("unlink", params, function () {
        treeStore.reload();
      });
    }, this);
    let vbox5 = new qx.ui.layout.VBox(10, null, null);
    let composite6 = new qx.ui.container.Composite();
    composite6.setLayout(vbox5);
    composite1.add(composite6, {
      flex: 1
    });
    vbox5.setSpacing(10);
    
    // Linkable items

    // Label
    let rightLabel = new qx.ui.basic.Label();
    rightLabel.set({
      rich : true,
      value : '<b>' + this.tr('Linkable items')  + '</b>',
      height : 20
    });
    bus.subscribe("treeSelectionChanged",(e)=>{
      let sel = e.getData();
      rightLabel.setValue(
        '<b>' + ( sel.length ? sel[0].getModel().getLabel() : this.tr('No linkable items') ) + "</b>"
      );
    });
    composite6.add(rightLabel);

    // List
    let rightList = new qx.ui.form.List();
    rightList.setSelectionMode("multi");
    rightList.setWidgetId("app/windows/acltool/rightList");
    composite6.add(rightList, {flex: 1});

    // Controller
    let rightListController = new qx.data.controller.List(null, rightList, "label");
    rightListController.setIconPath("icon");
    rightListStore.bind("model", rightListController, "model");
    rightList.addListener("changeSelection", function (e) {
      let m = new qx.event.message.Message("rightListSelectionChanged", e.getData ? e.getData() : []);
      m.setSender(e.getTarget());
      bus.dispatch(m);
    }, this);

    // Button panel
    let hbox4 = new qx.ui.layout.HBox(10, null, null);
    let composite7 = new qx.ui.container.Composite();
    composite7.setLayout(hbox4);
    composite6.add(composite7);
    hbox4.setSpacing(10);

    // "Add" button
    let button7 = new qx.ui.form.Button(null, "bibliograph/icon/button-plus.png", null);
    button7.setEnabled(false);
    button7.setIcon("bibliograph/icon/button-plus.png");
    composite7.add(button7);
    elementTree.bind("selection", button7, "enabled", {
      converter: (s) => s.length > 0
    });
    button7.addListener("execute", async () => {
      if( ! elementTree.getSelection().length ) return;
      let type = elementTree.getSelection()[0].getModel().getType();
      if( type === "datasource" ){
        rpc.AccessConfig.createDatasourceDialog();
      } else {
        let msg = this.tr("Please enter the id of the new '%1'-Object", type );
        let name = dialog.Dialog.prompt(msg).promise();
        if (name) {
          await rightListStore.execute("add", [type, name]);
          rightListStore.reload();
        }
      }
    });

    // "Delete" button
    let button8 = new qx.ui.form.Button();
    button8.setEnabled(false);
    button8.setIcon("bibliograph/icon/button-minus.png");
    composite7.add(button8);
    rightList.bind("selection", button8, "enabled", {
      converter: (s) => s.length > 0
    });
    button8.addListener("execute", function (e) {
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

    // "Edit" button
    let button9 = new qx.ui.form.Button(null, "bibliograph/icon/button-edit.png", null);
    button9.setEnabled(false);
    button9.setIcon("bibliograph/icon/button-edit.png");
    composite7.add(button9);
    rightList.bind("selection", button9, "enabled", {
      converter: (s) => s.length > 0
    });
    button9.addListener("execute", function (e) {
      if( ! rightList.getSelection().length ) return;
      let itemModel = rightList.getSelection()[0].getModel();
      let type = itemModel.getType();
      let name = itemModel.getValue();
      // this triggers a server dialog response
      this.getApplication().showPopup(this.tr("Loading data ..."));
      rightListStore.execute("edit", [type, name], function () {
        this.getApplication().hidePopup();
      }, this);
    }, this);

    // "Reload" button
    let button10 = new qx.ui.form.Button(null, "bibliograph/icon/button-reload.png", null);
    button10.setIcon("bibliograph/icon/button-reload.png");
    composite7.add(button10);
    button10.addListener("execute", function (e) {
      rightListStore.reload();
    }, this);
  }
});
