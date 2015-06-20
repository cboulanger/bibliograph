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

/*global qx qcl bibliograph*/

/**
 * The folder tree view
 * @asset(bibliograph/icon/button-plus.png)
 * @asset(bibliograph/icon/button-reload.png)
 * @asset(bibliograph/icon/button-settings-up.png)
 * @asset(bibliograph/icon/button-minus.png)
 * @ignore(qcl.bool2visibility)
 */
qx.Class.define("bibliograph.ui.folder.TreeViewUi",
{
  extend : bibliograph.ui.folder.TreeView,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      // Manager shortcuts
      var app = qx.core.Init.getApplication();
      var permMgr = app.getAccessManager().getPermissionManager();
      var confMgr = app.getConfigManager();
      
      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite1 = this;
      this.setLayout(qxVbox1)
      
      // messages
      qx.event.message.Bus.getInstance().subscribe("authenticated", function(e) {
        this.reload();
      }, this)
      qx.event.message.Bus.getInstance().subscribe("loggedOut", function(e) {
        this.reload();
      }, this)
      
      // tree widget
      var qxVbox2 = new qx.ui.layout.VBox(null, null, null);
      var treeWidgetContainer = new qx.ui.container.Composite();
      treeWidgetContainer.setLayout(qxVbox2)
      treeWidgetContainer.setAllowStretchY(true);
      treeWidgetContainer.setHeight(null);
      qxComposite1.add(treeWidgetContainer, {
        flex : 1
      });
      this.setTreeWidgetContainer(treeWidgetContainer);
      
      var qclAccess1 = qcl.access.PermissionManager.getInstance();
      var qclPermission1 = qclAccess1.create("allowEditFolder");
      qclPermission1.setGranted(true);
      var qclDependency1 = qcl.access.PermissionManager.getInstance().create("folder.edit");
      qclPermission1.addCondition(function() {
        return qclDependency1.getState();
      });
      qclDependency1.addListener("changeState", function() {
        qclPermission1.update();
      }, this);
      this.addListener("changeSelectedNode", function() {
        qclPermission1.update();
      }, this);
      qclPermission1.addCondition(function() {
        return this.getSelectedNode() !== null;
      }, this);
      var qclPermission2 = qclAccess1.create("allowAddFolder");
      qclPermission2.setGranted(true);
      var qclDependency2 = qcl.access.PermissionManager.getInstance().create("folder.add");
      qclPermission2.addCondition(function() {
        return qclDependency2.getState();
      });
      qclDependency2.addListener("changeState", function() {
        qclPermission2.update();
      }, this);
      this.addListener("changeSelectedNode", function() {
        qclPermission2.update();
      }, this);
      qclPermission2.addCondition(function() {
        return this.getSelectedNode() !== null;
      }, this);
      var qclPermission3 = qclAccess1.create("allowRemoveFolder");
      qclPermission3.setGranted(true);
      var qclDependency3 = qcl.access.PermissionManager.getInstance().create("folder.remove");
      qclPermission3.addCondition(function() {
        return qclDependency3.getState();
      });
      qclDependency3.addListener("changeState", function() {
        qclPermission3.update();
      }, this);
      this.addListener("changeSelectedNode", function() {
        qclPermission3.update();
      }, this);
      qclPermission3.addCondition(function() {
        return this.getSelectedNode() !== null;
      }, this);
      var qclPermission4 = qclAccess1.create("allowMoveFolder");
      qclPermission4.setGranted(true);
      var qclDependency4 = qcl.access.PermissionManager.getInstance().create("folder.move");
      qclPermission4.addCondition(function() {
        return qclDependency4.getState();
      });
      qclDependency4.addListener("changeState", function() {
        qclPermission4.update();
      }, this);
      this.addListener("changeSelectedNode", function() {
        qclPermission4.update();
      }, this);
      qclPermission4.addCondition(function() {
        return this.getSelectedNode() !== null;
      }, this);
      
      // Menu bar
      var qxMenuBar1 = new qx.ui.menubar.MenuBar();
      qxComposite1.add(qxMenuBar1);
      
      // Add button
      var qxMenuBarButton1 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-plus.png");
      qxMenuBar1.add(qxMenuBarButton1);
      qxMenuBarButton1.addListener("click", this._addFolderDialog, this);
      permMgr.create("folder.add").bind("state", qxMenuBarButton1, "visibility", {
        converter : qcl.bool2visibility
      });
      permMgr.create("allowAddFolder").bind("state", qxMenuBarButton1, "enabled");
      
      // Remove button
      var qxMenuBarButton2 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-minus.png", null);
      qxMenuBar1.add(qxMenuBarButton2);
      qxMenuBarButton2.addListener("click", this._removeFolderDialog, this);
      permMgr.create("folder.remove").bind("state", qxMenuBarButton2, "visibility", {
        converter : qcl.bool2visibility
      });
      permMgr.create("allowAddFolder").bind("state", qxMenuBarButton2, "enabled");
      
      // reload
      var qxMenuButton5 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-reload.png", null, null);
      qxMenuBar1.add(qxMenuButton5);
      qxMenuButton5.addListener("execute", function(e)
      {
        this.clearTreeCache();
        this.reload();
      }, this);      
      
      // Settings button/menu
      var settingsBtn = new qx.ui.menubar.Button(null, "bibliograph/icon/button-settings-up.png", null);
      qxMenuBar1.add(settingsBtn);
      var settingsMenu = new qx.ui.menu.Menu();
      settingsBtn.setMenu(settingsMenu);
      settingsMenu.setWidgetId("bibliograph/folder-settings-menu");
      
      var qxMenuButton1 = new qx.ui.menu.Button(this.tr('Empty trash...'), null, null, null);
      qxMenuButton1.setLabel(this.tr('Empty trash...'));
      settingsMenu.add(qxMenuButton1);
      qxMenuButton1.addListener("execute", this._emptyTrashDialog, this);
      permMgr.create("trash.empty").bind("state", qxMenuButton1, "visibility", {
        converter : qcl.bool2visibility
      });
      
      var qxMenuButton2 = new qx.ui.menu.Button(this.tr('Move folder...'));
      settingsMenu.add(qxMenuButton2);
      qxMenuButton2.addListener("execute", this._moveFolderDialog, this);
      permMgr.create("allowMoveFolder").bind("state", qxMenuButton2, "enabled");
      permMgr.create("folder.move").bind("state", qxMenuButton2, "visibility", {
        converter : qcl.bool2visibility
      });
      
      var qxMenuButton3 = new qx.ui.menu.Button(this.tr('Edit folder data'));
      settingsMenu.add(qxMenuButton3);
      qxMenuButton3.addListener("execute", this._editFolder, this);
      permMgr.create("allowEditFolder").bind("state", qxMenuButton3, "enabled", {

      });
      permMgr.create("folder.edit").bind("state", qxMenuButton3, "visibility", {
        converter : qcl.bool2visibility
      });
      
      var qxMenuButton4 = new qx.ui.menu.Button(this.tr('Change visibility'), null, null, null);
      qxMenuButton4.setLabel(this.tr('Change visibility'));
      settingsMenu.add(qxMenuButton4);
      qxMenuButton4.addListener("execute", this._changePublicState, this);
      permMgr.create("allowEditFolder").bind("state", qxMenuButton4, "enabled");
      permMgr.create("folder.edit").bind("state", qxMenuButton4, "visibility", {
        converter : qcl.bool2visibility
      });
      


      /*
       * Status label
       */
      var _statusLabel = new qx.ui.basic.Label(null);
      this._statusLabel = _statusLabel;
      _statusLabel.setPadding(3);
      _statusLabel.setRich(true);
      qxMenuBar1.add(_statusLabel, {
        flex : 1
      });
    }
  }
});
