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

/*global qx qcl bibliograph rssfolder*/

/**
 * The UI to import references from external databases
 */
qx.Class.define("rssfolder.ImportWindowUi",
{
  extend : rssfolder.ImportWindow,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      // window
      var importWindow = this;
      importWindow.setCaption(this.tr('Import from RSS Feed'));
      importWindow.setShowMinimize(false);
      importWindow.setWidth(700);
      importWindow.setHeight(500);
      qx.event.message.Bus.getInstance().subscribe("logout", function(e) {
        importWindow.close()
      }, this)
      importWindow.addListener("appear", function(e) {
        importWindow.center();
      }, this);
      var qxVbox1 = new qx.ui.layout.VBox(5, null, null);
      qxVbox1.setSpacing(5);
      importWindow.setLayout(qxVbox1);
      
      // toolbar
      var toolBar = new qx.ui.toolbar.ToolBar();
      this.toolBar = toolBar;
      toolBar.setSpacing(10);
      importWindow.add(toolBar);
      
      // feed url
      var feedUrlTextField = new qx.ui.form.TextField;
      this.feedUrlTextField = feedUrlTextField;
      feedUrlTextField.setLiveUpdate(true);
      toolBar.add(feedUrlTextField,{flex:1});
      
      // load feed button
      var loadFeedBtn = new qx.ui.form.Button(this.tr("Load feed"));
      this.loadFeedBtn = loadFeedBtn;
      toolBar.add(loadFeedBtn);
      
      loadFeedBtn.addListener("execute", this._on_loadFeedBtn_execute, this);
      feedUrlTextField.bind("value",loadFeedBtn,"enabled",{
        converter : function(v){ return v ? true : false }
      });

      // stack
      var qxStack1 = new qx.ui.container.Stack();
      importWindow.add(qxStack1, {
        flex : 1
      });
      
      /*
       * Listview
       */
      var listView = new bibliograph.ui.reference.ListView();
      this.listView = listView;
      listView.setServiceName("bibliograph.import");
      listView.setDatasource("bibliograph_import");
      listView.setDecorator("main");
      listView.setModelType("reference");
      
      qxStack1.add(listView);
      
      /*
       * footer
       */
      var qxHbox1 = new qx.ui.layout.HBox(5);
      var qxComposite1 = new qx.ui.container.Composite();
      qxComposite1.setLayout(qxHbox1)
      importWindow.add(qxComposite1);
      qxHbox1.setSpacing(5);
      
      /*
       * Status label
       */
      var statusTextLabel = new qx.ui.basic.Label(null);
      this.listView._statusLabel = statusTextLabel; // todo this is a hack
      qxComposite1.add(statusTextLabel);
      this.listView.bind("store.model.statusText", statusTextLabel, "value");
      
      var qxSpacer1 = new qx.ui.core.Spacer(null, null);
      qxComposite1.add(qxSpacer1, { flex : 10 });
      
      /*
       * select all button
       */
      var selectAllButton = new qx.ui.form.Button(this.tr('Select all'), null, null);
      this.selectAllButton = selectAllButton;
      selectAllButton.setEnabled(false);
      selectAllButton.setLabel(this.tr('Select all'));
      qxComposite1.add(selectAllButton);
      selectAllButton.addListener("execute", function(e) {
        listView.selectAll()
      }, this);
      
      
      /*
       * import selected button
       */
      var importButton = new qx.ui.form.Button(this.tr('Import selected records'), null, null);
      this.importButton = importButton;
      importButton.setEnabled(false);
      importButton.setLabel(this.tr('Import selected records'));
      qxComposite1.add(importButton);
      listView.bind("selectedIds", importButton, "enabled", {
        converter : function( ids ){ ids.length > 0 ? true : false }
      } );
      importButton.addListener("execute", function(e) {
        this.importSelected()
      }, this);
      
      /*
       * close button
       */
      var qxButton1 = new qx.ui.form.Button(this.tr('Close'), null, null);
      qxButton1.setLabel(this.tr('Close'));
      qxComposite1.add(qxButton1);
      qxButton1.addListener("execute", function(e) {
        this.close()
      }, this);
    }
  }
});
