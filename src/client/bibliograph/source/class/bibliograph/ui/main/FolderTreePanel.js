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

/*global qx qcl*/

/**
 * This UI class creates a panel containing a header menu bar, a tree widget and a 
 * footer with action buttons. The menu bar contains a title label.
 */
qx.Class.define("bibliograph.ui.main.FolderTreePanel",
{
  extend : qx.ui.container.Composite,
  type : "singleton",
  
  construct : function()
  {
    this.base(arguments);
    this.createUI();
  
    // reset selection if a user executes a search query
    qx.event.message.Bus.getInstance().subscribe("bibliograph.userquery", function() {
      try {
        this.treeWidget.getTree().resetSelection();
      }catch (e) {}
    }, this);
  },
  
  members : 
  {
    treeWidget : null,
    titleLabel : null,
    
    /**
     * Create the UI
     */
    createUI : function()
    {
      // layout
      let vbox1 = new qx.ui.layout.VBox(null, null, null);
      this.setLayout(vbox1);
  
      // menu bar
      let headerMenu = new qx.ui.menubar.MenuBar();
      headerMenu.setHeight(22);
      this.add(headerMenu);
      
      // title label
      let titleLabel = new qx.ui.basic.Label(null);
      this.titleLabel = titleLabel;
      titleLabel.setPadding(3);
      titleLabel.setRich(true);
      headerMenu.add(titleLabel);
  
      // tree widget
      let treeWidget = new bibliograph.ui.main.TreeView();
      treeWidget.setShowColumnHeaders(true);
      treeWidget.setWidgetId("app/treeview");
      treeWidget.setWidth(200);
      treeWidget.setColumnHeaders([this.tr('Folders'), '#']);
      this.add(treeWidget, { flex : 1 });
      // bind tree widget properties
      this.getApplication().bind("datasource", treeWidget, "datasource");
      treeWidget.bind("nodeId", this.getApplication(), "folderId" );
      this.getApplication().bind("folderId", treeWidget, "nodeId");
      this.treeWidget = treeWidget;
      
      // @todo: drag & drop
      // qx.core.Init.getApplication()
      //   .getAccessManager()
      //   .getPermissionManager()
      //   .create("folder.move").bind("state", treeWidget, "enableDragDrop");
      
      // tree widget container (tree is not added directly to the parent,
      // but is added to the container once it is set up.
      let vbox2 = new qx.ui.layout.VBox();
      let treeWidgetContainer = new qx.ui.container.Composite();
      treeWidgetContainer.setLayout(vbox2);
      treeWidgetContainer.setAllowStretchY(true);
      treeWidgetContainer.setHeight(null);
      this.add(treeWidgetContainer, {flex: 1});
      treeWidget.setTreeWidgetContainer(treeWidgetContainer);
    
      // footer menu
      let footerMenu = new qx.ui.menubar.MenuBar();
      this.add(footerMenu);
      footerMenu.add(treeWidget.createAddButton(true));
      footerMenu.add(treeWidget.createRemoveButton(true));
      footerMenu.add(treeWidget.createReloadButton());

      // Settings button/menu
      let settingsBtn = new qx.ui.menubar.Button(null, "bibliograph/icon/button-settings-up.png");
      footerMenu.add(settingsBtn);
      let settingsMenu =  new qx.ui.menu.Menu();
      settingsMenu.setWidgetId("app/treeview/settings-menu");
      settingsBtn.setMenu(settingsMenu);
      settingsMenu.add(treeWidget.createEmptyTrashButton());
      settingsMenu.add(treeWidget.createEditButton());
      settingsMenu.add(treeWidget.createVisibilityButton());
      settingsMenu.add(treeWidget.createMoveButton());
    
      // Status label
      let _statusLabel = new qx.ui.basic.Label(null);
      this._statusLabel = _statusLabel;
      _statusLabel.setPadding(3);
      _statusLabel.setRich(true);
      footerMenu.add(_statusLabel, {flex: 1});
    }
  }
});
