/* ************************************************************************

 Bibliograph: Online Collaborative Reference Management

 Copyright:
 2007-2014 Christian Boulanger

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
      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite1 = this;
      this.setLayout(qxVbox1)
      qx.event.message.Bus.getInstance().subscribe("authenticated", function(e) {
        this.reload();
      }, this)
      qx.event.message.Bus.getInstance().subscribe("loggedOut", function(e) {
        this.reload();
      }, this)
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
      var qxMenuBar1 = new qx.ui.menubar.MenuBar();
      qxComposite1.add(qxMenuBar1);
      var qxMenuBarButton1 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-plus.png", null);
      qxMenuBarButton1.setIcon("bibliograph/icon/button-plus.png");
      qxMenuBar1.add(qxMenuBarButton1);
      qxMenuBarButton1.addListener("click", this._addFolderDialog, this);
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("folder.add").bind("state", qxMenuBarButton1, "visibility", {
        converter : qcl.bool2visibility
      });
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("allowAddFolder").bind("state", qxMenuBarButton1, "enabled", {

      });
      var qxMenuBarButton2 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-minus.png", null);
      qxMenuBarButton2.setIcon("bibliograph/icon/button-minus.png");
      qxMenuBar1.add(qxMenuBarButton2);
      qxMenuBarButton2.addListener("click", this._removeFolderDialog, this);
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("folder.remove").bind("state", qxMenuBarButton2, "visibility", {
        converter : qcl.bool2visibility
      });
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("allowAddFolder").bind("state", qxMenuBarButton2, "enabled", {

      });
      var qxMenuBarButton3 = new qx.ui.menubar.Button(null, "bibliograph/icon/button-settings-up.png", null);
      qxMenuBarButton3.setIcon("bibliograph/icon/button-settings-up.png");
      qxMenuBar1.add(qxMenuBarButton3);
      var qxMenu1 = new qx.ui.menu.Menu();
      qxMenuBarButton3.setMenu(qxMenu1);
      var qxMenuButton1 = new qx.ui.menu.Button(this.tr('Empty trash...'), null, null, null);
      qxMenuButton1.setLabel(this.tr('Empty trash...'));
      qxMenu1.add(qxMenuButton1);
      qxMenuButton1.addListener("execute", this._emptyTrashDialog, this);
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("trash.empty").bind("state", qxMenuButton1, "visibility", {
        converter : qcl.bool2visibility
      });
      var qxMenuButton2 = new qx.ui.menu.Button(this.tr('Move folder...'), null, null, null);
      qxMenuButton2.setLabel(this.tr('Move folder...'));
      qxMenu1.add(qxMenuButton2);
      qxMenuButton2.addListener("execute", this._moveFolderDialog, this);
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("allowMoveFolder").bind("state", qxMenuButton2, "enabled", {

      });
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("folder.move").bind("state", qxMenuButton2, "visibility", {
        converter : qcl.bool2visibility
      });
      var qxMenuButton3 = new qx.ui.menu.Button(this.tr('Edit folder data'), null, null, null);
      qxMenuButton3.setLabel(this.tr('Edit folder data'));
      qxMenu1.add(qxMenuButton3);
      qxMenuButton3.addListener("execute", this._editFolder, this);
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("allowEditFolder").bind("state", qxMenuButton3, "enabled", {

      });
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("folder.edit").bind("state", qxMenuButton3, "visibility", {
        converter : qcl.bool2visibility
      });
      var qxMenuButton4 = new qx.ui.menu.Button(this.tr('Change visibility'), null, null, null);
      qxMenuButton4.setLabel(this.tr('Change visibility'));
      qxMenu1.add(qxMenuButton4);
      qxMenuButton4.addListener("execute", this._changePublicState, this);
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("allowEditFolder").bind("state", qxMenuButton4, "enabled", {

      });
      qx.core.Init.getApplication().getAccessManager().getPermissionManager().create("folder.edit").bind("state", qxMenuButton4, "visibility", {
        converter : qcl.bool2visibility
      });
      var qxMenuButton5 = new qx.ui.menu.Button(this.tr('Reload'), "bibliograph/icon/button-reload.png", null, null);
      qxMenuButton5.setLabel(this.tr('Reload'));
      qxMenuButton5.setIcon("bibliograph/icon/button-reload.png");
      qxMenu1.add(qxMenuButton5);
      qxMenuButton5.addListener("execute", function(e)
      {
        this.clearTreeCache();
        this.reload();
      }, this);

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
