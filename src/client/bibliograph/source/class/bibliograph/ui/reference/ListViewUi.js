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
    
    // Main view & Layout
    let vbox1 = new qx.ui.layout.VBox(null, null, null);
    let composite1 = this;
    this.setLayout(vbox1);
    
    // Layout
    let vbox2 = new qx.ui.layout.VBox(null, null, null);
    let contentPane = new qx.ui.container.Composite();
    contentPane.setLayout(vbox2);
    this.contentPane = contentPane;
    composite1.add(contentPane, { flex: 1 });

    // Menu bar
    let menuBar1 = new qx.ui.menubar.MenuBar();
    menuBar1.setHeight(22);
    contentPane.add(menuBar1);
    let referenceViewLabel = new qx.ui.basic.Label(null);
    this.referenceViewLabel = referenceViewLabel;
    referenceViewLabel.setPadding(3);
    referenceViewLabel.setRich(true);
    menuBar1.add(referenceViewLabel);
    
    // Table container, table will be inserted here
    let tableContainer = new qx.ui.container.Stack();
    contentPane.add(tableContainer, {flex: 1});
    this.setTableContainer(tableContainer);
    let menuBar = new qx.ui.menubar.MenuBar();
    this.menuBar = menuBar;
    menuBar.setHeight(18);
    contentPane.add(menuBar);

    // "Add Reference" menubar button
    let listViewAddMenuButton = new qx.ui.menubar.Button();
    listViewAddMenuButton.set({
      width: 16,
      height: 16,
      icon: "bibliograph/icon/button-plus.png",
      enabled : false
    });
    menuBar.add(listViewAddMenuButton);
    this.bindVisibility(this.permissions.add_reference, listViewAddMenuButton);
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
    let menuBarButton1 = new qx.ui.menubar.Button();
    menuBarButton1.set({
      width: 16,
      height: 16,
      enabled : false,
      icon : "bibliograph/icon/button-minus.png"
    });
    menuBarButton1.addListener("click", this._removeReference, this);
    this.bindEnabled(this.permissions.remove_selected_references, menuBarButton1);
    this.bindVisibility(this.permissions.remove_reference, menuBarButton1);

    // Reload Button
    let menuButton8 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-reload.png", null, null);
    menuBar.add(menuButton8);
    menuButton8.addListener("execute", function (e) {
      this.reload()
    }, this);

    // Options
    let menuBarButton2 = new qx.ui.menubar.Button();
    menuBarButton2.set({
      width:16,
      height:16,
      icon: "bibliograph/icon/button-settings-up.png"
    });
    menuBar.add(menuBarButton2);
    let menu1 = new qx.ui.menu.Menu();
    menu1.setPosition("top-left");
    menuBarButton2.setMenu(menu1);

    // Move references
    let menuButton1 = new qx.ui.menu.Button(this.tr('Move reference(s)...'));
    menu1.add(menuButton1);
    menuButton1.addListener("execute", ()=>this._moveReference());
    this.bindEnabled(this.permissions.move_selected_references, menuButton1);
    this.bindVisibility(this.permissions.move_reference, menuButton1);

    // Copy references
    let menuButton2 = new qx.ui.menu.Button(this.tr('Copy reference(s)...'));
    menu1.add(menuButton2);
    menuButton2.addListener("execute", ()=>this._copyReference());
    this.bindEnabled(this.permissions.move_selected_references, menuButton2);
    this.bindVisibility(this.permissions.move_reference, menuButton2);
    
    // Export menu
    let menuButton3 = new qx.ui.menu.Button(this.tr('Export references'));
    menu1.add(menuButton3);
    this.bindVisibility(this.permissions.export_references, menuButton2);
    
    let menu2 = new qx.ui.menu.Menu();
    menuButton3.setMenu(menu2);

    // Export selected references
    let menuButton4 = new qx.ui.menu.Button(this.tr('Export selected references'));
    menu2.add(menuButton4);
    menuButton4.addListener("execute", () => this.exportSelected());
    this.bindEnabled(this.permissions.export_selected_references, menuButton4);

    // Export folder
    let menuButton5 = new qx.ui.menu.Button(this.tr('Export folder'));
    menu2.add(menuButton5);
    menuButton5.addListener("execute", () => this.exportFolder() );
    this.bindEnabled(this.permissions.export_folder, menuButton5);

    // Edit menu
    let menuButton6 = new qx.ui.menu.Button();
    menuButton6.setLabel(this.tr('Edit references'));
    menu1.add(menuButton6);
    this.bindVisibility(this.permissions.edit_reference, menuButton6);
    let menu3 = new qx.ui.menu.Menu();
    menuButton6.setMenu(menu3);

    // Find/Replace Button
    let findReplBtn = new qx.ui.menu.Button();
    findReplBtn.setLabel(this.tr('Find/Replace'));
    menu3.add(findReplBtn);
    findReplBtn.addListener("execute", () => this.findReplace());
    this.bindVisibility(this.permissions.batch_edit_reference,findReplBtn);

    // Empty folder Button
    let emptyFldContBtn = new qx.ui.menu.Button();
    emptyFldContBtn.setLabel(this.tr('Make folder empty'));
    menu3.add(emptyFldContBtn);
    emptyFldContBtn.addListener("execute", () => this.emptyFolder());
    this.bindVisibility(this.permissions.batch_edit_reference,emptyFldContBtn);

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