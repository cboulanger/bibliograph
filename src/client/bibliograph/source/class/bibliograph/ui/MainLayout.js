/* ************************************************************************

 Bibliograph: Online Collaborative Reference Management

 Copyright:
 2007-2017 Christian Boulanger

 License:
 LGPL: http://www.gnu.org/licenses/lgpl.html
 EPL: http://www.eclipse.org/org/documents/epl-v10.php
 See the LICENSE file in the project's top-level directory for details.

 Authors:
 * Christian Boulanger (cboulanger)

 ************************************************************************ */
/*global bibliograph qx qcl dialog*/

/**
 * This class instantiates the main application ui and sets up bindings
 * between the widgets and the main application state
 */
qx.Class.define("bibliograph.ui.MainLayout", {
  extend: qx.core.Object,
  type: "singleton",
  members: {

    /**
     * shorthand
     */
    getRoot: function() {
      return this.getApplication().getRoot();
    },

    /**
     * Create the main layout
     */
    create: function() {
      let appContainer = new qx.ui.container.Composite(new qx.ui.layout.VBox());
      this.getRoot().add(appContainer, { edge: 0 });

      // Toolbar
      let toolbar = bibliograph.ui.main.Toolbar.getInstance();
      appContainer.add(toolbar);
      toolbar.setQxObjectId("toolbar");
      qx.core.Id.getInstance().register(toolbar);
      toolbar.setWidgetId("app/toolbar"); // to be removed

      // Horizontal splitpane
      let hsplit = new qx.ui.splitpane.Pane("horizontal");
      hsplit.setOrientation("horizontal");
      appContainer.add(hsplit, { flex: 1 });
      hsplit.setQxObjectId("horizontal-splitpane");
      qx.core.Id.getInstance().register(hsplit);
      
      let leftPane = new qx.ui.container.Composite(new qx.ui.layout.VBox());
      hsplit.add(leftPane, 1);

      // Folder Tree
      let folderTreePanel = bibliograph.ui.main.FolderTreePanel.getInstance();
      folderTreePanel.setQxObjectId("folder-tree-panel");
      qx.core.Id.getInstance().register(folderTreePanel);
      leftPane.add(folderTreePanel, { flex: 1 });

      // Vertical splitpane
      let vsplit = new qx.ui.splitpane.Pane("vertical");
      vsplit.setOrientation("vertical");
      vsplit.setDecorator(null);
      vsplit.setQxObjectId("vertical-splitpane");
      hsplit.add(vsplit, 3);
      qx.core.Id.getInstance().register(vsplit);

      // Reference table view
      let tableview = new bibliograph.ui.main.ReferenceTableView();
      tableview.setQxObjectId("table-view");
      qx.core.Id.getInstance().register(tableview);
      vsplit.add(tableview);

      // Item view
      let itemview = new bibliograph.ui.main.ItemViewUi();
      itemview.setQxObjectId("item-view");
      qx.core.Id.getInstance().register(itemview);
      vsplit.add(itemview);
      itemview.bind("view", this.getApplication(), "itemView", {});
      this.getApplication().bind("itemView", itemview, "view", {});
    }
  }
});
