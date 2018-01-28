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
 * This class instantiates the main application ui
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
      var app = qx.core.Init.getApplication();
      var bus = qx.event.message.Bus.getInstance();

      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite1 = new qx.ui.container.Composite();
      qxComposite1.setLayout(qxVbox1);
      this.getRoot().add(qxComposite1, {
        edge: 0
      });

      // Toolbar
      var ui_mainToolbar1 = new bibliograph.ui.main.Toolbar();
      qxComposite1.add(ui_mainToolbar1);

      // Horizontal splitpane
      var qxHsplit1 = new qx.ui.splitpane.Pane("horizontal");
      qxHsplit1.setOrientation("horizontal");
      qxComposite1.add(qxHsplit1, { flex: 1 });
      var qxVbox2 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite2 = new qx.ui.container.Composite();
      qxComposite2.setLayout(qxVbox2);
      qxHsplit1.add(qxComposite2, 1);

      // Folder Tree
      qxComposite2.add(bibliograph.ui.main.FolderTreePanel.getInstance(), { flex: 1 });

      // Vertical splitpane
      var qxVsplit1 = new qx.ui.splitpane.Pane("vertical");
      qxVsplit1.setOrientation("vertical");
      qxVsplit1.setDecorator(null);
      qxHsplit1.add(qxVsplit1, 3);

      // Reference Listview
      var ui_mainReferenceListView1 = new bibliograph.ui.main.ReferenceListView();
      qxVsplit1.add(ui_mainReferenceListView1);

      // Item view
      var ui_mainItemView1 = new bibliograph.ui.main.ItemViewUi();
      ui_mainItemView1.setWidgetId("bibliograph/itemView");
      qxVsplit1.add(ui_mainItemView1);
      ui_mainItemView1.bind("view", this.getApplication(), "itemView", {});
      this.getApplication().bind("itemView", ui_mainItemView1, "view", {});
    }
  }
});
