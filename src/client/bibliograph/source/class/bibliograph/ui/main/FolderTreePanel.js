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
    /** @var {bibliograph.ui.main.MultipleTreeView} */
    treeWidget : null,
    titleLabel : null,
  
    /** @var int */
    __startSearchIndex: 0,
    
    /**
     * Create the UI
     */
    createUI : function()
    {
      // layout
      let vbox1 = new qx.ui.layout.VBox(null, null, null);
      this.setLayout(vbox1);
      
      // search bar
      let searchBox = new qx.ui.form.TextField();
      searchBox.setHeight(22);
      this.add(searchBox);
      searchBox.addListener("keypress", e => {
        if (e.getKeyIdentifier() === "Enter") {
          this.search(searchBox.getValue());
        }
      });
  
      // menu bar
      // let headerMenu = new qx.ui.menubar.MenuBar();
      // headerMenu.setHeight(22);
      // this.add(headerMenu);
      
      // title label
      let titleLabel = new qx.ui.basic.Label(null);
      this.titleLabel = titleLabel;
      titleLabel.setPadding(3);
      titleLabel.setRich(true);
      //headerMenu.add(titleLabel);
  
      // multiple tree widget
      let mTree = new bibliograph.ui.main.MultipleTreeView();
      mTree.setShowColumnHeaders(true);
      mTree.setWidgetId("app/treeview");
      mTree.setWidth(200);
      mTree.setColumnHeaders([this.tr('Folders'), '#']);
      
      // bind tree widget properties
      this.getApplication().bind("datasource", mTree, "datasource");
      mTree.bind("nodeId", this.getApplication(), "folderId" );
      this.getApplication().bind("folderId", mTree, "nodeId");
      this.treeWidget = mTree;
      
      // @todo convert to new permission API
      qx.core.Init.getApplication()
        .getAccessManager()
        .getPermissionManager()
        .create("folder.move").bind("state", mTree, "enableDragDrop");
      
      // tree widget container (tree is not added directly to the parent,
      // but is added to the container once it is set up.
      let vbox2 = new qx.ui.layout.VBox();
      let mTreeContainer = new qx.ui.container.Composite();
      mTreeContainer.setLayout(vbox2);
      mTreeContainer.setAllowStretchY(true);
      mTreeContainer.setHeight(null);
      this.add(mTreeContainer, {flex: 1});
      mTree.setTreeWidgetContainer(mTreeContainer);
    
      // footer menu
      let footerMenu = new qx.ui.menubar.MenuBar();
      this.add(footerMenu);
      footerMenu.add(mTree.createAddFolderButton(true));
      footerMenu.add(mTree.createRemoveButton(true));
      footerMenu.add(mTree.createReloadButton());

      // Settings button/menu
      let settingsBtn = new qx.ui.menubar.Button(null, "bibliograph/icon/button-settings-up.png");
      footerMenu.add(settingsBtn);
      let settingsMenu =  new qx.ui.menu.Menu();
      settingsMenu.setWidgetId("app/treeview/settings-menu");
      settingsBtn.setMenu(settingsMenu);
      settingsMenu.add(mTree.createAddTopFolderButton());
      settingsMenu.add(mTree.createSaveSearchFolderButton());
      settingsMenu.add(mTree.createEmptyTrashButton());
      settingsMenu.add(mTree.createEditButton());
      settingsMenu.add(mTree.createVisibilityButton());
      settingsMenu.add(mTree.createMoveButton());
      settingsMenu.add(mTree.createCopyButton());
      settingsMenu.add(mTree.createPasteButton());
    
      // Status label
      let _statusLabel = new qx.ui.basic.Label(null);
      mTree.setStatusLabel(_statusLabel);
      _statusLabel.setPadding(3);
      _statusLabel.setRich(true);
      _statusLabel.setTextColor("#808080");
      footerMenu.add(_statusLabel, {flex: 1});
    },
    
    search : function(searchtext){
      /** @var {qcl.ui.treevirtual.DragDropTree} */
      let tree = this.treeWidget.getTree();
      let model = tree.getDataModel();
      let data = model.getData();
  
      // search the tree @todo make this async for really large trees
      let node, id, found = false;
      for (id= this.__startSearchIndex; id < data.length; id++ ){
        node = data[id];
        if (node.label.toLocaleLowerCase().includes(searchtext.toLocaleLowerCase())) {
          found = true;
          this.__startSearchIndex = id+1;
          break;
        }
      }
      if (!found) {
        if (this.__startSearchIndex === 0){
          this.treeWidget.showMessage(this.tr('No match for "%1"', searchtext));
          this.__startSearchIndex = 0;
          return;
        }
        this.__startSearchIndex = 0;
        return this.search(searchtext);
      }
  
      // open the tree so that the node is rendered
      for (let parentId = node.parentNodeId; parentId; parentId = node.parentNodeId) {
        node = tree.nodeGet(parentId);
        model.setState(node, {bOpened: true});
      }
      model.setData();
      // we need a timeout because tree rendering also uses timeouts, so this is not synchronous
      qx.event.Timer.once(() => {
        let row = model.getRowFromNodeId(id);
        if (row) {
          tree.getSelectionModel().resetSelection();
          tree.getSelectionModel().setSelectionInterval(row, row);
        }
      }, this, 500);
    }
  }
});
