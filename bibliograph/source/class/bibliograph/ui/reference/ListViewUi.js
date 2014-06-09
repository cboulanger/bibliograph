/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2014 Christian Boulanger
 *
 * License: LGPL: http://www.gnu.org/licenses/lgpl.html EPL:
 * http://www.eclipse.org/org/documents/epl-v10.php See the LICENSE file in the
 * project's top-level directory for details.
 *
 * Authors: Christian Boulanger (cboulanger)
 *
 ******************************************************************************/

/*global qx qcl bibliograph*/

/**
 * @asset(bibliograph/icon/button-plus.png)
 * @asset(bibliograph/icon/button-reload.png)
 * @asset(bibliograph/icon/button-settings-up.png)
 * @asset(bibliograph/icon/button-minus.png)
 * @ignore(qcl.bool2visibility)
 **/
qx.Class.define("bibliograph.ui.reference.ListViewUi",
{
  extend : bibliograph.ui.reference.ListView,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      var app = qx.core.Init.getApplication();
      var permissionManager = app.getAccessManager().getPermissionManager();

      /*
       * Main view & Layout
       */
      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite1 = this;
      this.setLayout(qxVbox1);

      /*
       * Permissions
       */
      var qclAccess1 = qcl.access.PermissionManager.getInstance();
      var qclPermission1 = qclAccess1.create("allowRemoveReference");
      qclPermission1.setGranted(true);
      var qclDependency1 = qcl.access.PermissionManager.getInstance().create("reference.remove");
      qclPermission1.addCondition(function() {
        return qclDependency1.getState();
      });
      qclDependency1.addListener("changeState", function() {
        qclPermission1.update();
      }, this);
      this.addListener("changeSelectedIds", function() {
        qclPermission1.update();
      }, this);
      qclPermission1.addCondition(function() {
        return (this.getSelectedIds().length > 0);
      }, this);
      var qclAccess2 = qcl.access.PermissionManager.getInstance();
      var qclPermission2 = qclAccess2.create("allowMoveReference");
      qclPermission2.setGranted(true);
      var qclDependency2 = qcl.access.PermissionManager.getInstance().create("reference.move");
      qclPermission2.addCondition(function() {
        return qclDependency2.getState();
      });
      qclDependency2.addListener("changeState", function() {
        qclPermission2.update();
      }, this);
      this.addListener("changeSelectedIds", function() {
        qclPermission2.update();
      }, this);
      qclPermission2.addCondition(function() {
        return (this.getSelectedIds().length > 0);
      }, this);
      var qclAccess3 = qcl.access.PermissionManager.getInstance();
      var qclPermission3 = qclAccess3.create("allowExportReference");
      qclPermission3.setGranted(true);
      var qclDependency3 = qcl.access.PermissionManager.getInstance().create("reference.export");
      qclPermission3.addCondition(function() {
        return qclDependency3.getState();
      });
      qclDependency3.addListener("changeState", function() {
        qclPermission3.update();
      }, this);
      this.addListener("changeSelectedIds", function() {
        qclPermission3.update();
      }, this);
      qclPermission3.addCondition(function() {
        return (this.getSelectedIds().length > 0);
      }, this);

      /*
       * Layout
       */
      var qxVbox2 = new qx.ui.layout.VBox(null, null, null);
      var contentPane = new qx.ui.container.Composite();
      contentPane.setLayout(qxVbox2);
      this.contentPane = contentPane;
      qxComposite1.add(contentPane, {
        flex : 1
      });

      /*
       * Menu bar
       */
      var qxMenuBar1 = new qx.ui.menubar.MenuBar();
      qxMenuBar1.setHeight(22);
      contentPane.add(qxMenuBar1);
      var referenceViewLabel = new qx.ui.basic.Label(null);
      this.referenceViewLabel = referenceViewLabel;
      referenceViewLabel.setPadding(3);
      referenceViewLabel.setRich(true);
      qxMenuBar1.add(referenceViewLabel);
      var tableContainer = new qx.ui.container.Stack();
      contentPane.add(tableContainer, { flex : 1 });
      this.setTableContainer(tableContainer);
      var menuBar = new qx.ui.menubar.MenuBar();
      this.menuBar = menuBar;
      menuBar.setHeight(18);
      contentPane.add(menuBar);

      /*
       * "Add Reference" menubar button
       */
      var listViewAddMenuButton = new qx.ui.menubar.Button();
      listViewAddMenuButton.setWidth(16);
      listViewAddMenuButton.setIcon("bibliograph/icon/button-plus.png");
      listViewAddMenuButton.setEnabled(false);
      listViewAddMenuButton.setHeight(16);
      menuBar.add(listViewAddMenuButton);
      permissionManager.create("reference.add").bind("state", listViewAddMenuButton, "visibility", {
        converter : qcl.bool2visibility
      });

      // access
      this.listViewAddMenuButton = listViewAddMenuButton;

      /*
       * Window to choose reference type
       */
      var win = new qx.ui.window.Window(this.tr("Create new reference type"));
      win.setLayout(new qx.ui.layout.VBox(5));
      win.set({
        height: 300, width : 200,
        showMinimize : false, showMaximize : false,
        modal : true
      });

      // blocker
      win.addListener("appear", function(){
        win.center();
        app.getBlocker().blockContent(win.getZIndex()-1);
      },this);
      win.addListener("disappear",function(){
        app.getBlocker().unblock();
      },this);

      listViewAddMenuButton.addListener("execute", win.open, win);
      app.getRoot().add(win);
      this.chooseRefTypeWin = win;

      /*
       * List widget, will be populated later
       */
      var list = new qx.ui.list.List();
      list.set({
        iconPath : "icon", labelPath : "label"
      });
      win.add(list,{flex:1});
      this.chooseRefTypeList = list;

      /*
       * OK button
       */
      var okButton = new qx.ui.form.Button( this.tr("Create") );
      okButton.addListener("execute", function() {
        var type = list.getSelection().getItem(0).getValue();
        qx.lang.Function.delay(function(){
          win.close();
          app.setItemView("referenceEditor-main");
          this.createReference(type);
        },100,this);
      }, this);
      win.add(okButton);

      /*
       * Cancel button
       */
      var cancelButton = new qx.ui.form.Button(this.tr("Cancel"));
      cancelButton.addListener("execute",function(){
        win.close();
      },this);
      win.add(cancelButton);

      /*
       * Remove button
       */
      var qxMenuBarButton1 = new qx.ui.menubar.Button();
      qxMenuBarButton1.setWidth(16);
      qxMenuBarButton1.setHeight(16);
      qxMenuBarButton1.setEnabled(false);
      qxMenuBarButton1.setIcon("bibliograph/icon/button-minus.png");
      menuBar.add(qxMenuBarButton1);
      qxMenuBarButton1.addListener("click", this._removeReference, this);
      permissionManager.create("allowRemoveReference").bind("state", qxMenuBarButton1, "enabled", {

      });
      permissionManager.create("reference.remove").bind("state", qxMenuBarButton1, "visibility", {
        converter : qcl.bool2visibility
      });

      /*
       * Options
       */
      var qxMenuBarButton2 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-settings-up.png", null);
      qxMenuBarButton2.setWidth(16);
      qxMenuBarButton2.setHeight(16);
      qxMenuBarButton2.setIcon("bibliograph/icon/button-settings-up.png");
      menuBar.add(qxMenuBarButton2);
      var qxMenu1 = new qx.ui.menu.Menu();
      qxMenu1.setPosition("top-left");
      qxMenuBarButton2.setMenu(qxMenu1);

      /*
       * Move references
       */
      var qxMenuButton1 = new qx.ui.menu.Button(this.tr('Move reference(s)...'), null, null, null);
      qxMenuButton1.setLabel(this.tr('Move reference(s)...'));
      qxMenu1.add(qxMenuButton1);
      qxMenuButton1.addListener("execute", this._moveReference, this);
      permissionManager.create("allowMoveReference")
          .bind("state", qxMenuButton1, "enabled");
      permissionManager.create("reference.move")
          .bind("state", qxMenuButton1, "visibility", { converter : qcl.bool2visibility });

      /*
       * Copy references
       */
      var qxMenuButton2 = new qx.ui.menu.Button(this.tr('Copy reference(s)...'), null, null, null);
      qxMenuButton2.setLabel(this.tr('Copy reference(s)...'));
      qxMenu1.add(qxMenuButton2);
      qxMenuButton2.addListener("execute", this._copyReference, this);
      permissionManager.create("allowMoveReference")
          .bind("state", qxMenuButton2, "enabled");
      permissionManager.create("reference.move")
          .bind("state", qxMenuButton2, "visibility", { converter : qcl.bool2visibility });

      /*
       * Export menu
       */
      var qxMenuButton3 = new qx.ui.menu.Button(this.tr('Export references'), null, null, null);
      qxMenuButton3.setLabel(this.tr('Export references'));
      qxMenu1.add(qxMenuButton3);
      permissionManager.create("reference.export")
          .bind("state", qxMenuButton3, "visibility", { converter : qcl.bool2visibility });
      var qxMenu2 = new qx.ui.menu.Menu();
      qxMenuButton3.setMenu(qxMenu2);

      /*
       * Export selected references
       */
      var qxMenuButton4 = new qx.ui.menu.Button(this.tr('Export selected references'), null, null, null);
      qxMenuButton4.setLabel(this.tr('Export selected references'));
      qxMenu2.add(qxMenuButton4);
      permissionManager.create("allowExportReference")
          .bind("state", qxMenuButton4, "enabled");
      qxMenuButton4.addListener("execute", function(e) {
        this.exportSelected();
      }, this);

      /*
       * Export folder
       */
      var qxMenuButton5 = new qx.ui.menu.Button(this.tr('Export folder'), null, null, null);
      qxMenuButton5.setLabel(this.tr('Export folder'));
      qxMenu2.add(qxMenuButton5);
      qxMenuButton5.addListener("execute", function(e) {
        this.exportFolder();
      }, this);

      /*
       * Edit menu
       */
      var qxMenuButton6 = new qx.ui.menu.Button(this.tr('Edit references'), null, null, null);
      qxMenuButton6.setLabel(this.tr('Edit references'));
      qxMenu1.add(qxMenuButton6);
      permissionManager.create("reference.edit")
          .bind("state", qxMenuButton6, "visibility", { converter : qcl.bool2visibility });
      var qxMenu3 = new qx.ui.menu.Menu();
      qxMenuButton6.setMenu(qxMenu3);

      /*
       * Find/Replace Button
       */
      var qxMenuButton7 = new qx.ui.menu.Button(this.tr('Find/Replace'), null, null, null);
      qxMenuButton7.setLabel(this.tr('Find/Replace'));
      qxMenu3.add(qxMenuButton7);
      permissionManager.create("reference.batchedit")
          .bind("state", qxMenuButton7, "visibility", { converter : qcl.bool2visibility });
      qxMenuButton7.addListener("execute", function(e) {
        this.findReplace()
      }, this);


      /*
       * Reload Button
       */
      var qxMenuButton8 = new qx.ui.menu.Button(this.tr('Reload'), "bibliograph/icon/button-reload.png", null, null);
      qxMenuButton8.setLabel(this.tr('Reload'));
      qxMenuButton8.setIcon("bibliograph/icon/button-reload.png");
      qxMenu1.add(qxMenuButton8);
      qxMenuButton8.addListener("execute", function(e) {
        this.reload()
      }, this);

      /*
       * Status bar
       */
      var statusLabel = new qx.ui.basic.Label(null);
      this._statusLabel = statusLabel;
      statusLabel.setTextColor("#808080");
      statusLabel.setMargin(5);
      menuBar.add(statusLabel);
      this.bind("store.model.statusText", statusLabel, "value");
      statusLabel.addListener("changeValue", function(e) {
        qx.util.TimerManager.getInstance().start(function(value) {
          if (statusLabel.getValue() == value)statusLabel.setValue("");
        }, null, this, e.getData(), 5000);
      }, this);
    }
  }
});
