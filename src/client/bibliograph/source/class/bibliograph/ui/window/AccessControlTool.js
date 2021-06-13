/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2018 Christian Boulanger
 *
 * License: LGPL: http://www.gnu.org/licenses/lgpl.html EPL:
 * http://www.eclipse.org/org/documents/epl-v10.php See the LICENSE file in the
 * project's top-level directory for details.
 *
 * Authors: Christian Boulanger (cboulanger)
 *
 ******************************************************************************/

/**
 * The Access configuration window
 * @ignore(bibliograph._actRpcSendProxy)
 */
qx.Class.define("bibliograph.ui.window.AccessControlTool",
{
  extend: qx.ui.window.Window,
  include: [qcl.ui.MLoadingPopup],
  construct: function () {
    this.base(arguments);
  
    const app = qx.core.Init.getApplication();
    const pm = app.getPermissionManager();
    const bus = qx.event.message.Bus.getInstance();
    
    this.setCaption(this.tr("Access control tool"));
    //this.setVisibility("excluded");
    this.setWidth(800);
    this.addListener("appear", e => this.center());
    this.createPopup();
    
    // close on logout
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, () => this.close());
  
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
      treeSelection.length > 0 &&
      treeSelection[0].getModel() &&
      treeSelection[0].getModel().getAction() === "link" &&
      rightList.getSelection().length > 0);
    });
    allowUnlinkPermission.addCondition(() => {
      let treeSelection = elementTree.getSelection();
      return (
      treeSelection.length > 0 &&
      treeSelection[0].getModel() &&
      treeSelection[0].getModel().getAction() === "unlink");
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
    this.addListener("appear", e => {
      this.selectBoxStore.setAutoLoadParams(null);
      this.selectBoxStore.setAutoLoadParams([]);
    });
    
    // store for left list
    const leftListStore = new qcl.data.store.JsonRpcStore("access-config");
    leftListStore.set({
      autoLoadMethod : "elements"
    });
    bus.subscribe("accessControlTool.reloadLeftList", () => leftListStore.canReload() && leftListStore.reload());
  
    // store for right list
    const rightListStore = new qcl.data.store.JsonRpcStore("access-config");
    rightListStore.setAutoLoadMethod("elements");
    
    // store for tree
    const treeStore = new qcl.data.store.JsonRpcStore("access-config");
    treeStore.setAutoLoadMethod("tree");
    
    leftListStore.addListener("loaded", e => {
      allowLinkPermission.update();
      allowUnlinkPermission.update();
      // reset the right list and the tree when the left list is loaded
      rightListStore.setModel(null);
      rightListStore.setAutoLoadParams(null);
      treeStore.setModel(null);
      treeStore.setAutoLoadParams(null);
    });
    treeStore.addListener("loaded", e => {
      // reset the right list when the tree is loaded
      rightListStore.setModel(null);
      rightListStore.setAutoLoadParams(null);
    });
  
    /**
     * This is a globally addressable proxy method
     * @todo Implement this differently!
     * @param method
     * @param params
     * @param callback
     * @private
     */
    bibliograph._actRpcSendProxy = (method, params, callback) => {
      //this.debug(arguments);
      this.getApplication().getRpcClient("access-config")
      .send(method, params)
      .then(response => {
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
    let toolbar = new qx.ui.toolbar.ToolBar();
    this.add(toolbar);
    
    // user button
    let addUserButton = new qx.ui.toolbar.Button(this.tr("New local user"), "icon/22/apps/preferences-users.png");
    toolbar.add(addUserButton);
    this.addOwnedQxObject(addUserButton, "add-user-btn");
    pm.create("access.manage").bind("state", addUserButton, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });
    addUserButton.addListener("execute", async () => {
      this.leftSelectBox.setSelection([this.leftSelectBox.getSelectables()[0]]);
      this.getApplication().showPopup(this.tr("Please wait ..."));
      await rpc.AccessConfig.newUserDialog();
    });
  
    //  import LDAP user button
    let findLdapUserButton = new qx.ui.toolbar.Button(this.tr("Import LDAP user"));
    this.addOwnedQxObject(findLdapUserButton, "find-ldap-user-btn");
    toolbar.add(findLdapUserButton);
    pm.create("access.manage").bind("state", findLdapUserButton, "enabled");
    let cm = app.getConfigManager();
    cm.addListener("ready", () => {
      findLdapUserButton.setVisibility(bibliograph.Utils.bool2visibility(cm.getKey("ldap.enabled")));
    });
    findLdapUserButton.addListener("execute", async function (e) {
      leftSelectBox.setSelection([this.leftSelectBox.getSelectables()[0]]);
      //findLdapUserButton.setEnabled(false);
      await rpc.AccessConfig.findLdapUserDialog();
      //findLdapUserButton.setEnabled(true);
    }, this);
  
    // datasource button
    let addDatasourceButton = new qx.ui.toolbar.Button(this.tr("New Datasource"), "icon/22/apps/internet-transfer.png");
    toolbar.add(addDatasourceButton);
    this.addOwnedQxObject(addDatasourceButton, "add-datasource-btn");
    pm.create("access.manage").bind("state", addDatasourceButton, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });
    // @todo rewrite
    addDatasourceButton.addListener("execute", async () => {
      this.leftSelectBox.setSelection([this.leftSelectBox.getSelectables()[4]]);
      await rpc.AccessConfig.createDatasourceDialog();
    });
    
    // help button
    let helpButton = new qx.ui.toolbar.Button(this.tr("Help"), "icon/22/apps/utilities-help.png");
    toolbar.add(helpButton);
    this.addOwnedQxObject(helpButton, "help-btn");
    helpButton.addListener("execute", () => {
      this.getApplication().getCommands().showHelpWindow("administration/access-control");
    });
    
    toolbar.addSpacer();
    
    // exit button
    let exitBtn = new qx.ui.toolbar.Button(this.tr("Exit"), "icon/22/actions/application-exit.png");
    toolbar.add(exitBtn);
    this.addOwnedQxObject(exitBtn, "exit-btn");
    exitBtn.addListener("execute", function (e) {
      this.close();
    }, this);
    
    // group box container
    let groupBox = new qx.ui.groupbox.GroupBox();
    groupBox.setLayout(new qx.ui.layout.VBox());
    this.add(groupBox, {flex: 1});
    
    // layout for three columns
    let mainContainer = new qx.ui.container.Composite(new qx.ui.layout.HBox(10));
    mainContainer.setAllowStretchY(true);
    groupBox.add(mainContainer, {flex: 1});

    // left column
    let vbox3 = new qx.ui.layout.VBox(10, null, null);
    let composite2 = new qx.ui.container.Composite();
    composite2.setLayout(vbox3);
    mainContainer.add(composite2, {flex: 1});
    vbox3.setSpacing(10);
    
    // select box
    let leftSelectBox = new qx.ui.form.SelectBox();
    this.leftSelectBox = leftSelectBox;
    composite2.add(leftSelectBox);
    this.addOwnedQxObject(leftSelectBox, "left-selectbox");
    let leftSelectBoxController = new qx.data.controller.List(null, leftSelectBox, "label");
    leftSelectBoxController.setIconPath("icon");
    this.selectBoxStore.bind("model", leftSelectBoxController, "model");
    leftSelectBox.bind("selection", leftListStore, "autoLoadParams", {
      converter: function (sel) {
        return sel.length ? sel[0].getModel().getValue() : null;
      }
    });
  
    // left search box
    let leftSearchbox = new qx.ui.form.TextField();
    leftSearchbox.set({
      placeholder: this.tr("Filter by name here..."),
      liveUpdate: true
    });
    composite2.add(leftSearchbox);
    this.addOwnedQxObject(leftSearchbox, "left-searchbox");
    leftSearchbox.addListener("changeValue", e => {
      let input = (e.getData()||"").toLocaleLowerCase();
      if (input) {
        leftList.setDelegate({filter: model => model.getLabel().toLowerCase().includes(input) });
      } else {
        leftList.setDelegate(null);
      }
    });
    leftSelectBox.addListener("changeSelection", () => {
      leftSearchbox.setValue("");
      leftList.setDelegate(null);
    });
    // server command
    bus.subscribe("acltool.searchbox-left.set", e => leftSearchbox.setValue(e.getData()));
    
    // left list
    let leftList = new qx.ui.list.List();
    composite2.add(leftList, {flex: 1});
    this.addOwnedQxObject(leftList, "left-list");
    leftList.set({iconPath: "icon", labelPath: "label"});
    leftListStore.bind("model", leftList, "model");
    leftList.getSelection().addListener("change", () => {
      let sel = leftList.getSelection();
      treeStore.set("autoLoadParams", sel.getLength() > 0 ? sel.getItem(0).getParams() : null);
    });
    
    // button pane
    let hbox2 = new qx.ui.layout.HBox(10, null, null);
    let composite3 = new qx.ui.container.Composite();
    composite3.setLayout(hbox2);
    composite2.add(composite3);
    hbox2.setSpacing(10);
    
    // "Add" button
    let leftAddBtn = new qx.ui.form.Button(null, "bibliograph/icon/button-plus.png", null);
    leftAddBtn.setEnabled(false);
    composite3.add(leftAddBtn);
    this.addOwnedQxObject(leftAddBtn, "left-add-btn");
    leftSelectBox.bind("selection", leftAddBtn, "enabled", {
      converter: s => s.length > 0
    });
    leftAddBtn.addListener("execute", async () => {
      let selection = leftSelectBox.getSelection();
      let type = selection.length ? selection[0].getModel().getValue() : null;
      if (!type) {
        this.warn("Cannot get type!");
        return;
      }
      if (type === "datasource") {
        await rpc.AccessConfig.createDatasourceDialog();
      } else {
        let msg = this.tr("Please enter the id of the new '%1'-Object", type);
        let name = await this.getApplication().prompt(msg);
        if (name) {
          await rpc.AccessConfig.add(type, name, null, true);
          leftListStore.reload();
        }
      }
    });
    
    // "Delete" button
    let leftDeleteBtn = new qx.ui.form.Button(null, "bibliograph/icon/button-minus.png", null);
    leftDeleteBtn.setEnabled(false);
    composite3.add(leftDeleteBtn);
    this.addOwnedQxObject(leftDeleteBtn, "left-delete-btn");
    leftList.getSelection().addListener("change", () => {
      leftDeleteBtn.setEnabled(leftList.getSelection().getLength() > 0);
    });
    leftDeleteBtn.addListener("execute", async () => {
      if (!leftList.getSelection().length) {
        return;
      }
      let itemModel = leftList.getSelection().getItem(0);
      let name = itemModel.getValue();
      let type = itemModel.getType();
      let msg = this.tr("Do you really want to delete '%1'?", name);
      if (await this.getApplication().confirm(msg)) {
        await leftListStore.execute("delete", [type, name]);
        leftListStore.reload();
      }
    });
    
    // "Edit" button
    let leftEditButton = new qx.ui.form.Button(null, "bibliograph/icon/button-edit.png", null);
    leftEditButton.setEnabled(false);
    composite3.add(leftEditButton);
    this.addOwnedQxObject(leftEditButton, "left-edit-btn");
    leftList.getSelection().addListener("change", async () => {
      leftEditButton.setEnabled(leftList.getSelection().getLength() > 0);
    });
    leftEditButton.addListener("execute", async () => {
      if (!leftList.getSelection().getLength()) {
        return;
      }
      let itemModel = leftList.getSelection().getItem(0);
      let type = itemModel.getType();
      let name = itemModel.getValue();
      // this triggers a server dialog response
      await rpc.AccessConfig.edit(type, name);
    });
    
    // reload button
    let leftReloadBtn = new qx.ui.form.Button(null, "bibliograph/icon/button-reload.png", null);
    leftReloadBtn.setIcon("bibliograph/icon/button-reload.png");
    composite3.add(leftReloadBtn);
    this.addOwnedQxObject(leftReloadBtn, "left-reload-btn");
    leftReloadBtn.addListener("execute", () => leftListStore.reload());
    
    // tree container
    let treeContainer = new qx.ui.container.Composite(new qx.ui.layout.VBox(10));
    mainContainer.add(treeContainer, {flex: 2});
    // Label for edited element
    let centerLabel = new qx.ui.basic.Label();
    centerLabel.set({
      value: this.tr("Edited element"),
      maxWidth : 250,
      height : 20,
      rich : true
    });
    treeContainer.add(centerLabel);
    leftList.getSelection().addListener("change", () => {
      let sel = leftList.getSelection();
      centerLabel.setValue(
        sel.getLength() > 0 ? `<b>${sel.getItem(0).getLabel()}</b>` : null
      );
      leftEditButton.setEnabled(leftList.getSelection().length > 0);
    });
    
    // Tree of linked Elements
    const elementTree = new qx.ui.tree.Tree();
    treeContainer.add(elementTree, {flex: 1});
    this.addOwnedQxObject(elementTree, "tree");
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
        if (selection.length) {
         let model = selection[0].getModel();
         return model.getAction() === "link" ? model.getType() : null;
        }
        return null;
      }
    });
    elementTree.addListener("changeSelection", () => {
      allowLinkPermission.update();
      allowUnlinkPermission.update();
    });
    // double click on element in the tree selects it in the left pane
    elementTree.addListener("dblclick", e => {
      let model = elementTree.getSelection()[0].getModel();
      let value = model.getValue();
      if (value && value.includes("=")) {
        let [type, id] = value.split("=");
        let sel = leftSelectBoxController.getModel().filter(item => item.getValue() === type);
        if (sel.length) {
          leftSelectBoxController.getSelection().replace(sel);
          leftListStore.addListenerOnce("changeModel", e => {
            let model = e.getData();
            if (model){
              sel = model.filter(item => item.getValue() === id);
              leftList.getSelection().replace(sel);
            }
          });
        }
      }
    });

    // tree button pane
    let treeBtnPane = new qx.ui.container.Composite(new qx.ui.layout.HBox(10));
    treeContainer.add(treeBtnPane);
    
    // link button
    let linkBtn = new qx.ui.form.Button(this.tr("Link"));
    linkBtn.setEnabled(false);
    treeBtnPane.add(linkBtn);
    this.addOwnedQxObject(linkBtn, "link-btn");
    allowLinkPermission.bind("state", linkBtn, "enabled");
    linkBtn.addListener("execute", () => {
      let treeModel = elementTree.getSelection()[0].getModel();
      let rightModel = rightList.getSelection().getItem(0);
      let params = [treeModel.getValue(), rightModel.getType(), rightModel.getValue()];
      treeStore.execute("link", params, () => treeStore.reload());
    });
    
    // Unlink button
    let unlinkBtn = new qx.ui.form.Button(this.tr("Unlink"), null, null);
    unlinkBtn.setEnabled(false);
    unlinkBtn.setLabel(this.tr("Unlink"));
    treeBtnPane.add(unlinkBtn);
    this.addOwnedQxObject(unlinkBtn, "unlink-btn");
    allowUnlinkPermission.bind("state", unlinkBtn, "enabled");
    unlinkBtn.addListener("execute", function (e) {
      let leftModel = leftList.getSelection().getItem(0);
      let treeModel = elementTree.getSelection()[0].getModel();
      let params = [treeModel.getValue(), leftModel.getType(), leftModel.getValue()];
      treeStore.execute("unlink", params, function () {
        treeStore.reload();
      });
    }, this);
  
    // Linkable items container
    let rightContainer = new qx.ui.container.Composite(new qx.ui.layout.VBox(10));
    mainContainer.add(rightContainer, { flex: 1 });
    
    // Label
    let rightLabel = new qx.ui.basic.Label();
    rightLabel.set({
      rich : true,
      value : "<b>" + this.tr("Linkable items") + "</b>",
      height : 20
    });
    elementTree.addListener("changeSelection", e => {
      let sel = e.getData();
      rightLabel.setValue(
        "<b>" + (sel.length ? sel[0].getModel().getLabel() : this.tr("No linkable items")) + "</b>"
      );
    });
    rightContainer.add(rightLabel);
    this.addOwnedQxObject(rightLabel, "right-label");
  
    // right search box
    let rightSearchbox = new qx.ui.form.TextField();
    rightSearchbox.setPlaceholder(this.tr("Filter by name here..."));
    rightContainer.add(rightSearchbox);
    this.addOwnedQxObject(rightSearchbox, "right-searchbox");
    rightSearchbox.addListener("input", e => {
      let input = (e.getData()||"").toLocaleLowerCase();
      if (input) {
        rightList.setDelegate({filter: item => item.getLabel().toLowerCase().includes(input) });
      } else {
        rightList.setDelegate(null);
      }
    });
    elementTree.addListener("changeSelection", () => {
     rightSearchbox.setValue("");
     rightList.setDelegate(null);
    });
    
    // right list
    let rightList = new qx.ui.list.List();
    rightList.setSelectionMode("multi");
    this.addOwnedQxObject(rightList, "right-list");
    rightContainer.add(rightList, {flex: 1});
    rightList.set({iconPath: "icon", labelPath: "label"});
    rightListStore.bind("model", rightList, "model");
    rightList.getSelection().addListener("change", () => {
      allowLinkPermission.update();
      allowUnlinkPermission.update();
    });

    // Button panel
    let rightBtnPane = new qx.ui.container.Composite(new qx.ui.layout.HBox(10));
    rightContainer.add(rightBtnPane);

    // "Add" button
    // let button7 = new qx.ui.form.Button(null, "bibliograph/icon/button-plus.png", null);
    // button7.setEnabled(false);
    // button7.setIcon("bibliograph/icon/button-plus.png");
    // composite7.add(button7);
    // elementTree.bind("selection", button7, "enabled", {
    //   converter: (s) => s.length > 0
    // });
    // button7.addListener("execute", async () => {
    //   if( ! elementTree.getSelection().length ) return;
    //   let type = elementTree.getSelection()[0].getModel().getType();
    //   if( type === "datasource" ){
    //     rpc.AccessConfig.createDatasourceDialog();
    //   } else {
    //     let msg = this.tr("Please enter the id of the new '%1'-Object", type );
    //     let name = await this.getApplication().prompt(msg);
    //     if (name) {
    //       await rightListStore.execute("add", [type, name]);
    //       rightListStore.reload();
    //     }
    //   }
    // });
    //
    // // "Delete" button
    // let button8 = new qx.ui.form.Button();
    // button8.setEnabled(false);
    // button8.setIcon("bibliograph/icon/button-minus.png");
    // composite7.add(button8);
    // rightList.getSelection().addListener("change", () => {
    //   button8.setEnabled(rightList.getSelection().getLength() > 0);
    // });
    // button8.addListener("execute", async () => {
    //   let selection = rightList.getSelection();
    //   let names = [];
    //   let types = [];
    //   selection.forEach(function (item) {
    //     names.push(item.getValue());
    //     types.push(item.getType());
    //   });
    //   let msg = this.tr("Do you really want to delete the objects '%1'?", names.join(", "));
    //   if (await this.getApplication().confirm(msg)) {
    //       rightListStore.execute("delete", [types[0], names], function () {
    //         rightListStore.reload();
    //       });
    //   });
    // });

    // "Edit" button
    // let button9 = new qx.ui.form.Button(null, "bibliograph/icon/button-edit.png", null);
    // button9.setEnabled(false);
    // button9.setIcon("bibliograph/icon/button-edit.png");
    // composite7.add(button9);
    // rightList.getSelection().addListener("change", () => {
    //   button9.setEnabled(rightList.getSelection().getLength() > 0);
    // });
    // button9.addListener("execute", function (e) {
    //   if( ! rightList.getSelection().length ) return;
    //   let itemModel = rightList.getSelection().getItem(0);
    //   let type = itemModel.getType();
    //   let name = itemModel.getValue();
    //   // this triggers a server dialog response
    //   this.getApplication().showPopup(this.tr("Loading data ..."));
    //   rightListStore.execute("edit", [type, name], function () {
    //     this.getApplication().hidePopup();
    //   }, this);
    // }, this);

    // "Reload" button
    let rightReloadBtn = new qx.ui.form.Button();
    rightReloadBtn.setIcon("bibliograph/icon/button-reload.png");
    rightBtnPane.add(rightReloadBtn);
    this.addOwnedQxObject(rightReloadBtn, "right-reload-btn");
    rightReloadBtn.addListener("execute", function (e) {
      rightListStore.reload();
    }, this);
  }
});
