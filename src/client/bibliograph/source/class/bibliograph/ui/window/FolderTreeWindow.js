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

/**
 *
 */
qx.Class.define("bibliograph.ui.window.FolderTreeWindow",
{
  extend : qx.ui.window.Window,
  
  events : {
    "nodeSelected" : "qx.event.type.Data"
  },
  
  construct : function()
  {
    this.base(arguments);
    this.createUi();
  },  
  
  members : {
  
    createUi: function () 
    {
      this.setWidth(300);
      this.setHeight(400);
      this.setCaption("Please select a folder");
      let grow1 = new qx.ui.layout.Grow();
      this.setLayout(grow1);
    
      // blocker
      let root = qx.core.Init.getApplication().getRoot();
      this.__blocker = new qx.ui.core.Blocker(root);
      this.__blocker.setOpacity(0.5);
      this.__blocker.setColor("black");
    
      // events
      this.addListener("appear", function (e) {
        this.center();
        this.__blocker.blockContent(this.getZIndex() - 1);
      }, this);
    
      this.addListener("disappear", function (e) {
        this.__blocker.unblock();
      }, this);
    
      qx.event.message.Bus.getInstance()
        .subscribe(bibliograph.AccessManager.messages.LOGOUT, ()=> this.close());
    
      // tree widget
      let treeWidget = new qcl.ui.treevirtual.MultipleTreeView();
      this.treeWidget = treeWidget;
      treeWidget.setColumnHeaders(['Folders', '#']);
      treeWidget.setModelType("folder");
      treeWidget.setServiceName("folder");
      this.add(treeWidget);
      
      // connect datasource
      treeWidget.addListener("appear", function (e) {
        this.treeWidget.setDatasource(this.getApplication().getDatasource());
      }, this);
    
      // tree container
      let vbox1 = new qx.ui.layout.VBox(5, null, null);
      vbox1.setSpacing(5);
      treeWidget.setLayout(vbox1);
      let vbox2 = new qx.ui.layout.VBox(null, null, null);
      let treeWidgetContainer = new qx.ui.container.Composite();
      treeWidgetContainer.setLayout(vbox2)
      treeWidgetContainer.setHeight(null);
      treeWidget.add(treeWidgetContainer, { flex: 1  });
      this.treeWidget.setTreeWidgetContainer(treeWidgetContainer);
      
      // buttons pane
      let hbox1 = new qx.ui.layout.HBox(5, null, null);
      let composite1 = new qx.ui.container.Composite();
      composite1.setLayout(hbox1)
      treeWidget.add(composite1);
      hbox1.setSpacing(5);
    
      // Reload
      let button1 = new qx.ui.form.Button();
      button1.setIcon("bibliograph/icon/button-reload.png");
      composite1.add(button1);
      button1.addListener("execute", function (e) {
        //this.treeWidget.clearTreeCache();
        this.treeWidget.reload();
      }, this);
    
      // Cancel
      let button2 = new qx.ui.form.Button();
      button2.setLabel(this.tr('Cancel'));
      composite1.add(button2);
      button2.addListener("execute", function (e) {
        this.hide();
      }, this);
    
      // Select
      let button3 = new qx.ui.form.Button();
      button3.setLabel(this.tr('Select'));
      composite1.add(button3);
      button3.addListener("execute", function (e) {
        this.hide();
        this.fireDataEvent("nodeSelected", treeWidget.getSelectedNode());
      }, this);
    }
  }
});
