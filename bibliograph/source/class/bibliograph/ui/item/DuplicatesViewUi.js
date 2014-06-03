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
      qxComposite1.add(table, {
        flex : 1
      });

      // ------
      var qxTableModel1 = new qx.ui.table.model.Simple();
      qxTableModel1.setColumns(["RID", this.tr('Type'), this.tr('Author'), this.tr('Date'), this.tr('Title'), "%"], ["refId", "type", "author", "date", "title", "score"]);

      // -------
      table.setTableModel(qxTableModel1);
      table.getTableColumnModel().setColumnVisible(0, false);
      table.getTableColumnModel().setColumnWidth(0, 50);
      table.getTableColumnModel().getBehavior().setWidth(0, 50);
      table.getTableColumnModel().setColumnWidth(1, 150);
      table.getTableColumnModel().getBehavior().setWidth(1, 150);
      table.getTableColumnModel().setColumnWidth(3, 50);
      table.getTableColumnModel().getBehavior().setWidth(3, 50);
      table.getTableColumnModel().setColumnWidth(5, 30);
      table.getTableColumnModel().getBehavior().setWidth(5, 30);
      this.getApplication().addListener("changeModelId", this.reloadData, this);
      table.addListener("appear", this._on_appear, this);
      var qxHbox1 = new qx.ui.layout.HBox(5, null, null);
      var qxComposite2 = new qx.ui.container.Composite();
      qxComposite2.setLayout(qxHbox1)
      qxComposite2.setEnabled(false);
      qxComposite1.add(qxComposite2);
      qxHbox1.setSpacing(5);

      /*
       * Display duplicate
       */
      var qxButton1 = new qx.ui.form.Button(this.tr('Display Duplicate'), null, null);
      qxButton1.setEnabled(true);
      //table.addListener("cellClick",function(e){},this);
      qxComposite2.add(qxButton1);
      //permMgr.create("reference.remove").bind("state", qxButton1, "enabled");
      qxButton1.addListener("execute", this._displayDuplicate, this);

      /*
       * Delete Duplicate
       */
      var qxButton2 = new qx.ui.form.Button(this.tr('Delete Duplicate'), null, null);
      qxButton2.setEnabled(false);
      qxComposite2.add(qxButton2);
      permMgr.create("reference.remove").bind("state", qxButton2, "enabled");
      qxButton2.addListener("execute", this._deleteDuplicate, this);
    }
  }
});
