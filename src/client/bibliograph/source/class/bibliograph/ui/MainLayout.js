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
      let app = qx.core.Init.getApplication();
      let bus = qx.event.message.Bus.getInstance();

      let vbox1 = new qx.ui.layout.VBox(null, null, null);
      let composite1 = new qx.ui.container.Composite();
      composite1.setLayout(vbox1);
      this.getRoot().add(composite1, { edge: 0 });

      // Toolbar
      let toolbar = new bibliograph.ui.main.Toolbar();
      composite1.add(toolbar);

      // Horizontal splitpane
      let hsplit1 = new qx.ui.splitpane.Pane("horizontal");
      hsplit1.setOrientation("horizontal");
      composite1.add(hsplit1, { flex: 1 });
      let vbox2 = new qx.ui.layout.VBox(null, null, null);
      let composite2 = new qx.ui.container.Composite();
      composite2.setLayout(vbox2);
      hsplit1.add(composite2, 1);

      // Folder Tree
      composite2.add(bibliograph.ui.main.FolderTreePanel.getInstance(), { flex: 1 });

      // Vertical splitpane
      let vsplit1 = new qx.ui.splitpane.Pane("vertical");
      vsplit1.setOrientation("vertical");
      vsplit1.setDecorator(null);
      hsplit1.add(vsplit1, 3);

      // Reference table view
      let tableview = new bibliograph.ui.main.ReferenceTableView();
      vsplit1.add(tableview);

      // Item view
      let itemview = new bibliograph.ui.main.ItemViewUi();
      itemview.setWidgetId("app/itemview");
      vsplit1.add(itemview);
      itemview.bind("view", this.getApplication(), "itemView", {});
      this.getApplication().bind("itemView", itemview, "view", {});
    }
  }
});
