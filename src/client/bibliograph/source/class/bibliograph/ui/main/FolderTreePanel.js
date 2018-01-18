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
  
    var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
    var qxComposite1 = this;
    this.setLayout(qxVbox1)

    // menu bar
    var qxMenuBar1 = new qx.ui.menubar.MenuBar();
    qxMenuBar1.setHeight(22);
    qxComposite1.add(qxMenuBar1);
    var titleLabel = new qx.ui.basic.Label(null);
    this.titleLabel = titleLabel;
    titleLabel.setPadding(3);
    titleLabel.setRich(true);
    qxMenuBar1.add(titleLabel);

    // tree widget
    var treeWidget = new bibliograph.ui.folder.TreeViewUi();
    treeWidget.setShowColumnHeaders(true);
    treeWidget.setWidgetId("bibliograph/mainFolderTree");
    treeWidget.setWidth(200);
    treeWidget.setColumnHeaders([this.tr('Folders'), '#']);
    qxComposite1.add(treeWidget, { flex : 1 });
    // bind tree widget properties
    this.getApplication().bind("datasource", treeWidget, "datasource");
    treeWidget.bind("nodeId", this.getApplication(), "folderId" );
    this.getApplication().bind("folderId", treeWidget, "nodeId");
    
    // @todo: drag & drop
    // qx.core.Init.getApplication()
    //   .getAccessManager()
    //   .getPermissionManager()
    //   .create("folder.move").bind("state", treeWidget, "enableDragDrop");
    
    // reset selection if a user executes a search query
    qx.event.message.Bus.getInstance().subscribe("bibliograph.userquery", function() {
      try {
        treeWidget.getTree().resetSelection();
      }catch (e) {}
    }, this);
  }
});
