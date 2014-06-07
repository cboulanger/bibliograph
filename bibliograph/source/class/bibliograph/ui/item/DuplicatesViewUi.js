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

/**
 * The duplicate references view
 */
qx.Class.define("bibliograph.ui.item.DuplicatesViewUi",
{
  extend : bibliograph.ui.item.DuplicatesView,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      var app = qx.core.Init.getApplication();
      var permMgr = app.getAccessManager().getPermissionManager();

      var qxVbox1 = new qx.ui.layout.VBox(5, null, null);
      var qxComposite1 = this;
      this.setLayout(qxVbox1)
      qxComposite1.setPadding(5);
      qxVbox1.setSpacing(5);
      var qxLabel1 = new qx.ui.basic.Label(this.tr('This table shows potential duplicates of the current record.'));
      qxComposite1.add(qxLabel1);
      
      /*
       * Table
       */
      var table = new qx.ui.table.Table(null, {
        tableColumnModel : function(obj) {
          return new qx.ui.table.columnmodel.Resize(obj);
        }
      });
      this.duplicatesTable = table;
      table.setShowCellFocusIndicator(false);
      table.setStatusBarVisible(false);
      table.getSelectionModel().setSelectionMode(qx.ui.table.selection.Model.SINGLE_SELECTION);
      table.setKeepFirstVisibleRowComplete(true);
      qxComposite1.add(table, { flex : 1 });

      var qxTableModel1 = new qx.ui.table.model.Simple();
      qxTableModel1.setColumns([
        "RID", 
        this.tr('Type'), 
        this.tr('Author'), 
        this.tr('Date'), 
        this.tr('Title'), 
        "%"], 
        ["refId", "type", "author", "date", "title", "score"]
      );
      table.setTableModel(qxTableModel1);
      table.getTableColumnModel().setColumnVisible(0, false);
      table.getTableColumnModel().setColumnWidth(0, 50);
      table.getTableColumnModel().getBehavior().setWidth(0, 50);
      table.getTableColumnModel().setColumnWidth(1, 150);
      table.getTableColumnModel().getBehavior().setWidth(1, 150);
      table.getTableColumnModel().setColumnWidth(3, 50);
      table.getTableColumnModel().getBehavior().setWidth(3, 50);
      table.getTableColumnModel().setColumnWidth(5, 50);
      table.getTableColumnModel().getBehavior().setWidth(5, 50);
      this.getApplication().addListener("changeModelId", this.reloadData, this);
      table.addListener("appear", this._on_appear, this);
      var qxHbox1 = new qx.ui.layout.HBox(5, null, null);
      var footer = new qx.ui.container.Composite();
      footer.setLayout(qxHbox1)
      qxComposite1.add(footer);
      qxHbox1.setSpacing(5);

      /*
       * Button to display selected duplicate
       */
      var qxButton1 = new qx.ui.form.Button(this.tr('Display'), null, null);
      qxButton1.setEnabled(true);
      //table.addListener("cellClick",function(e){},this);
      footer.add(qxButton1);
      //permMgr.create("reference.remove").bind("state", qxButton1, "enabled");
      qxButton1.addListener("execute", this._displayDuplicate, this);

      /*
       * Button to delete selected duplicate
       */
      var qxButton2 = new qx.ui.form.Button(this.tr('Delete'), null, null);
      qxButton2.setEnabled(false);
      footer.add(qxButton2);
      permMgr.create("reference.remove").bind("state", qxButton2, "enabled");
      qxButton2.addListener("execute", this._deleteDuplicate, this);
      
      /*
       * Spinner to change score threshold
       */
      var label = new qx.ui.basic.Label(this.tr("Score threshold to count as duplicate:"));
      label.setAlignY("middle");
      footer.add( label );
      var spinner = new qx.ui.form.Spinner();
      spinner.set({
        maximum: 100,
        minimum: 10,
        singleStep : 10
      });
      footer.add(spinner);
      spinner.addListener("changeValue", function(e){
        var value = e.getData();
        // small timeout to prevent to many reloads
        qx.event.Timer.once(function(){
          if ( value != spinner.getValue() )return;
          this._reloadData();
        }, this, 500);
      }, this);
      
      // bind to config value
      var app = qx.core.Init.getApplication();
      var confMgr = app.getConfigManager();      
      confMgr.addListener("ready", function() {
        confMgr.bindKey("bibliograph.duplicates.threshold", spinner, "value", true);
      });      
      
    }
  }
});
