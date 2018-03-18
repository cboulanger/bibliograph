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
 */
qx.Class.define("bibliograph.ui.folder.TreeViewUi", {
  extend: bibliograph.ui.folder.TreeView,
  construct: function () {
    this.base(arguments);

    // Manager shortcuts
    let app = qx.core.Init.getApplication();
    let permMgr = app.getAccessManager().getPermissionManager();
    let confMgr = app.getConfigManager();

    let vbox1 = new qx.ui.layout.VBox(null, null, null);
    let composite1 = this;
    this.setLayout(vbox1);

    // messages
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.LOGIN, ()=> this.reload());
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.LOGOUT, ()=> this.reload());

    // tree widget
    let vbox2 = new qx.ui.layout.VBox();
    let treeWidgetContainer = new qx.ui.container.Composite();
    treeWidgetContainer.setLayout(vbox2);
    treeWidgetContainer.setAllowStretchY(true);
    treeWidgetContainer.setHeight(null);
    composite1.add(treeWidgetContainer, {flex: 1});
    this.setTreeWidgetContainer(treeWidgetContainer);

    // permissions
    let permission1 = permMgr.create("allowEditFolder");
    permission1.setGranted(true);
    let dependency1 = permMgr.create("folder.edit");
    permission1.addCondition(() => dependency1.getState());
    dependency1.addListener("changeState", () => permission1.update());
    this.addListener("changeSelectedNode", () => permission1.update());
    permission1.addCondition(function () {
      return this.getSelectedNode() !== null;
    }, this);
    let permission2 = permMgr.create("allowAddFolder");
    permission2.setGranted(true);
    let dependency2 = permMgr.create("folder.add");
    permission2.addCondition(function () {
      return dependency2.getState();
    });
    dependency2.addListener("changeState", () => permission2.update());
    this.addListener("changeSelectedNode", () => permission2.update());
    permission2.addCondition(function () {
      return this.getSelectedNode() !== null;
    }, this);
    let permission3 = permMgr.create("allowRemoveFolder");
    permission3.setGranted(true);
    let dependency3 = permMgr.create("folder.remove");
    permission3.addCondition(function () {
      return dependency3.getState();
    });
    dependency3.addListener("changeState", () => permission3.update());
    this.addListener("changeSelectedNode", () => permission3.update());
    permission3.addCondition(function () {
      return this.getSelectedNode() !== null;
    }, this);
    let permission4 = permMgr.create("allowMoveFolder");
    permission4.setGranted(true);
    let dependency4 = permMgr.create("folder.move");
    permission4.addCondition(function () {
      return dependency4.getState();
    });
    dependency4.addListener("changeState", () => permission4.update());
    this.addListener("changeSelectedNode", () => permission4.update());
    permission4.addCondition(function () {
      return this.getSelectedNode() !== null;
    }, this);

    // Menu bar
    let menuBar1 = new qx.ui.menubar.MenuBar();
    composite1.add(menuBar1);

    // Add button
    let menuBarButton1 = new qx.ui.menubar.Button(null,"bibliograph/icon/button-plus.png");
    menuBar1.add(menuBarButton1);
    menuBarButton1.addListener("click", this._addFolderDialog, this);
    permMgr.create("folder.add").bind("state", menuBarButton1, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });
    permMgr.create("allowAddFolder").bind("state", menuBarButton1, "enabled");

    // Remove button
    let menuBarButton2 = new qx.ui.menubar.Button(null,"bibliograph/icon/button-minus.png");
    menuBar1.add(menuBarButton2);
    menuBarButton2.addListener("click", this._removeFolderDialog, this);
    permMgr
    .create("folder.remove")
    .bind("state", menuBarButton2, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });
    permMgr.create("allowAddFolder").bind("state", menuBarButton2, "enabled");

    // reload
    let menuButton5 = new qx.ui.menubar.Button(null,"bibliograph/icon/button-reload.png");
    menuBar1.add(menuButton5);
    menuButton5.addListener("execute", () => this.reload() );

    // Settings button/menu
    let settingsBtn = new qx.ui.menubar.Button(null, "bibliograph/icon/button-settings-up.png" );
    menuBar1.add(settingsBtn);
    let settingsMenu = new qx.ui.menu.Menu();
    settingsBtn.setMenu(settingsMenu);
    settingsMenu.setWidgetId("bibliograph/folder-settings-menu");

    let menuButton1 = new qx.ui.menu.Button(this.tr("Empty trash..."));
    menuButton1.setLabel(this.tr("Empty trash..."));
    settingsMenu.add(menuButton1);
    menuButton1.addListener("execute", this._emptyTrashDialog, this);
    permMgr.create("trash.empty").bind("state", menuButton1, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });

    let menuButton2 = new qx.ui.menu.Button(this.tr("Move folder..."));
    settingsMenu.add(menuButton2);
    menuButton2.addListener("execute", this._moveFolderDialog, this);
    permMgr.create("allowMoveFolder").bind("state", menuButton2, "enabled");
    permMgr.create("folder.move").bind("state", menuButton2, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });

    let menuButton3 = new qx.ui.menu.Button(this.tr("Edit folder data"));
    settingsMenu.add(menuButton3);
    menuButton3.addListener("execute", this._editFolder, this);
    permMgr.create("allowEditFolder").bind("state", menuButton3, "enabled");
    permMgr.create("folder.edit").bind("state", menuButton3, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });

    let menuButton4 = new qx.ui.menu.Button(this.tr("Change visibility"));
    menuButton4.setLabel(this.tr("Change visibility"));
    settingsMenu.add(menuButton4);
    menuButton4.addListener("execute", this._changePublicState, this);
    permMgr.create("allowEditFolder").bind("state", menuButton4, "enabled");
    permMgr.create("folder.edit").bind("state", menuButton4, "visibility", {
      converter: bibliograph.Utils.bool2visibility
    });

    // Status label
    let _statusLabel = new qx.ui.basic.Label(null);
    this._statusLabel = _statusLabel;
    _statusLabel.setPadding(3);
    _statusLabel.setRich(true);
    menuBar1.add(_statusLabel, {flex: 1});
  }
});
