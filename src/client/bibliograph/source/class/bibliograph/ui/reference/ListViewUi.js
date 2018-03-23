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

/* global qx qcl dialog bibliograph */

/**
 * @ignore(bibliograph.Utils.bool2visibility)
 **/
qx.Class.define("bibliograph.ui.reference.ListViewUi",
{
  extend: bibliograph.ui.reference.ListView,
  construct: function () {
    this.base(arguments);

    let app = qx.core.Init.getApplication();
    let permissionManager = app.getAccessManager().getPermissionManager();

    // Main view & Layout
    let qxVbox1 = new qx.ui.layout.VBox(null, null, null);
    let qxComposite1 = this;
    this.setLayout(qxVbox1);

    // Permissions
    let qclAccess1 = qcl.access.PermissionManager.getInstance();
    let qclPermission1 = qclAccess1.create("allowRemoveReference");
    qclPermission1.setGranted(true);
    let qclDependency1 = qcl.access.PermissionManager.getInstance().create("reference.remove");
    qclPermission1.addCondition(function () {
      return qclDependency1.getState();
    });
    qclDependency1.addListener("changeState", function () {
      qclPermission1.update();
    }, this);
    this.addListener("changeSelectedIds", function () {
      qclPermission1.update();
    }, this);
    qclPermission1.addCondition(function () {
      return (this.getSelectedIds().length > 0);
    }, this);
    let qclAccess2 = qcl.access.PermissionManager.getInstance();
    let qclPermission2 = qclAccess2.create("allowMoveReference");
    qclPermission2.setGranted(true);
    let qclDependency2 = qcl.access.PermissionManager.getInstance().create("reference.move");
    qclPermission2.addCondition(function () {
      return qclDependency2.getState();
    });
    qclDependency2.addListener("changeState", function () {
      qclPermission2.update();
    }, this);
    this.addListener("changeSelectedIds", function () {
      qclPermission2.update();
    }, this);
    qclPermission2.addCondition(function () {
      return (this.getSelectedIds().length > 0);
    }, this);
    let qclAccess3 = qcl.access.PermissionManager.getInstance();
    let qclPermission3 = qclAccess3.create("allowExportReference");
    qclPermission3.setGranted(true);
    let qclDependency3 = qcl.access.PermissionManager.getInstance().create("reference.export");
    qclPermission3.addCondition(function () {
      return qclDependency3.getState();
    });
    qclDependency3.addListener("changeState", function () {
      qclPermission3.update();
    }, this);
    this.addListener("changeSelectedIds", function () {
      qclPermission3.update();
    }, this);
    qclPermission3.addCondition(function () {
      return (this.getSelectedIds().length > 0);
    }, this);

    // Layout
    let qxVbox2 = new qx.ui.layout.VBox(null, null, null);
    let contentPane = new qx.ui.container.Composite();
    contentPane.setLayout(qxVbox2);
    this.contentPane = contentPane;
    qxComposite1.add(contentPane, {
      flex: 1
    });

    // Menu bar
    let qxMenuBar1 = new qx.ui.menubar.MenuBar();
    qxMenuBar1.setHeight(22);
    contentPane.add(qxMenuBar1);
    let referenceViewLabel = new qx.ui.basic.Label(null);
    this.referenceViewLabel = referenceViewLabel;
    referenceViewLabel.setPadding(3);
    referenceViewLabel.setRich(true);
    qxMenuBar1.add(referenceViewLabel);
    let tableContainer = new qx.ui.container.Stack();
    contentPane.add(tableContainer, {flex: 1});
    this.setTableContainer(tableContainer);
    let menuBar = new qx.ui.menubar.MenuBar();
    this.menuBar = menuBar;
    menuBar.setHeight(18);
    contentPane.add(menuBar);

    // "Add Reference" menubar button
    let listViewAddMenuButton = new qx.ui.menubar.Button();
    listViewAddMenuButton.setWidth(16);
    listViewAddMenuButton.setIcon("bibliograph/icon/button-plus.png");
    listViewAddMenuButton.setEnabled(false);
    listViewAddMenuButton.setHeight(16);
    menuBar.add(listViewAddMenuButton);
    permissionManager.create("reference.add").bind("state", listViewAddMenuButton, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });

    // access
    this.listViewAddMenuButton = listViewAddMenuButton;

    // Window to choose reference type
    let win = new qx.ui.window.Window(this.tr("Create new reference type"));
    win.setLayout(new qx.ui.layout.VBox(5));
    win.set({
      height: 300, width: 200,
      showMinimize: false, showMaximize: false,
      modal: true
    });

    // blocker
    win.addListener("appear", function () {
      win.center();
      app.getBlocker().blockContent(win.getZIndex() - 1);
    }, this);
    win.addListener("disappear", function () {
      app.getBlocker().unblock();
    }, this);

    listViewAddMenuButton.addListener("execute", win.open, win);
    app.getRoot().add(win);
    this.chooseRefTypeWin = win;

    // List widget, will be populated later
    let list = new qx.ui.list.List();
    list.set({
      iconPath: "icon", labelPath: "label"
    });
    win.add(list, {flex: 1});
    this.chooseRefTypeList = list;

    // OK button
    let okButton = new qx.ui.form.Button(this.tr("Create"));
    okButton.addListener("execute", function () {
      let type = list.getSelection().getItem(0).getValue();
      qx.lang.Function.delay(function () {
        win.close();
        app.setItemView("referenceEditor-main");
        this.createReference(type);
      }, 100, this);
    }, this);
    win.add(okButton);

    // Cancel button
    let cancelButton = new qx.ui.form.Button(this.tr("Cancel"));
    cancelButton.addListener("execute", function () {
      win.close();
    }, this);
    win.add(cancelButton);

    // Remove button
    let qxMenuBarButton1 = new qx.ui.menubar.Button();
    qxMenuBarButton1.setWidth(16);
    qxMenuBarButton1.setHeight(16);
    qxMenuBarButton1.setEnabled(false);
    qxMenuBarButton1.setIcon("bibliograph/icon/button-minus.png");
    menuBar.add(qxMenuBarButton1);
    qxMenuBarButton1.addListener("click", this._removeReference, this);
    permissionManager.create("allowRemoveReference").bind("state", qxMenuBarButton1, "enabled");
    permissionManager.create("reference.remove").bind("state", qxMenuBarButton1, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });

    // Reload Button
    let qxMenuButton8 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-reload.png", null, null);
    menuBar.add(qxMenuButton8);
    qxMenuButton8.addListener("execute", function (e) {
      this.reload()
    }, this);

    // Options
    let qxMenuBarButton2 = new qx.ui.menubar.Button();
    qxMenuBarButton2.setWidth(16);
    qxMenuBarButton2.setHeight(16);
    qxMenuBarButton2.setIcon("bibliograph/icon/button-settings-up.png");
    menuBar.add(qxMenuBarButton2);
    let qxMenu1 = new qx.ui.menu.Menu();
    qxMenu1.setPosition("top-left");
    qxMenuBarButton2.setMenu(qxMenu1);

    // Move references
    let qxMenuButton1 = new qx.ui.menu.Button(this.tr('Move reference(s)...'));
    qxMenu1.add(qxMenuButton1);
    qxMenuButton1.addListener("execute", ()=>this._moveReference());
    permissionManager.create("allowMoveReference")
    .bind("state", qxMenuButton1, "enabled");
    permissionManager.create("reference.move")
    .bind("state", qxMenuButton1, "visibility", {converter: bibliograph.Utils.bool2visibility});

    // Copy references
    let qxMenuButton2 = new qx.ui.menu.Button(this.tr('Copy reference(s)...'));
    qxMenu1.add(qxMenuButton2);
    qxMenuButton2.addListener("execute", ()=>this._copyReference());
    permissionManager.create("allowMoveReference")
    .bind("state", qxMenuButton2, "enabled");
    permissionManager.create("reference.move")
    .bind("state", qxMenuButton2, "visibility", {converter: bibliograph.Utils.bool2visibility});

    // Export menu
    let qxMenuButton3 = new qx.ui.menu.Button(this.tr('Export references'));
    qxMenu1.add(qxMenuButton3);
    permissionManager.create("reference.export")
    .bind("state", qxMenuButton3, "visibility", {converter: bibliograph.Utils.bool2visibility});
    let qxMenu2 = new qx.ui.menu.Menu();
    qxMenuButton3.setMenu(qxMenu2);

    // Export selected references
    let qxMenuButton4 = new qx.ui.menu.Button(this.tr('Export selected references'));
    qxMenu2.add(qxMenuButton4);
    permissionManager.create("allowExportReference")
    .bind("state", qxMenuButton4, "enabled");
    qxMenuButton4.addListener("execute", function (e) {
      this.exportSelected();
    }, this);

    // Export folder
    let qxMenuButton5 = new qx.ui.menu.Button(this.tr('Export folder'));
    qxMenu2.add(qxMenuButton5);
    qxMenuButton5.addListener("execute", function (e) {
      this.exportFolder();
    }, this);


    // Edit menu
    let qxMenuButton6 = new qx.ui.menu.Button();
    qxMenuButton6.setLabel(this.tr('Edit references'));
    qxMenu1.add(qxMenuButton6);
    permissionManager.create("reference.edit")
    .bind("state", qxMenuButton6, "visibility", {converter: bibliograph.Utils.bool2visibility});
    let qxMenu3 = new qx.ui.menu.Menu();
    qxMenuButton6.setMenu(qxMenu3);

    // Find/Replace Button
    let findReplBtn = new qx.ui.menu.Button();
    findReplBtn.setLabel(this.tr('Find/Replace'));
    qxMenu3.add(findReplBtn);
    permissionManager.create("reference.batchedit")
    .bind("state", findReplBtn, "visibility", {converter: bibliograph.Utils.bool2visibility});
    findReplBtn.addListener("execute", function (e) {
      this.findReplace()
    }, this);

    // Empty folder Button
    let emptyFldContBtn = new qx.ui.menu.Button();
    emptyFldContBtn.setLabel(this.tr('Make folder empty'));
    qxMenu3.add(emptyFldContBtn);
    permissionManager.create("reference.batchedit")
    .bind("state", emptyFldContBtn, "visibility", {converter: bibliograph.Utils.bool2visibility});
    emptyFldContBtn.addListener("execute", function (e) {
      this.emptyFolder()
    }, this);


    // Status bar
    let statusLabel = new qx.ui.basic.Label(null);
    this._statusLabel = statusLabel;
    statusLabel.setTextColor("#808080");
    statusLabel.setMargin(5);
    menuBar.add(statusLabel);
    this.bind("store.model.statusText", statusLabel, "value");
    statusLabel.addListener("changeValue", function (e) {
      qx.util.TimerManager.getInstance().start(function (value) {
        if (statusLabel.getValue() === value) statusLabel.setValue("");
      }, null, this, e.getData(), 5000);
    }, this);
  }
});
