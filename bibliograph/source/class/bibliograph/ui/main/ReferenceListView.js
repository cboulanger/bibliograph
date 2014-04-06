/**
 * The main list of references
 */
qx.Class.define("bibliograph.ui.main.ReferenceListView",
{
  extend : qx.ui.container.Composite,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      var qxHbox1 = new qx.ui.layout.HBox(null, null, null);
      var qxComposite1 = this;
      this.setLayout(qxHbox1)
      var mainListView = new bibliograph.ui.reference.ListViewUi();
      this.mainListView = mainListView;
      mainListView.setWidgetId("mainListView");
      mainListView.setAllowStretchY(true);
      qxComposite1.add(mainListView, {
        flex : 1
      });
      this.getApplication().bind("datasource", mainListView, "datasource", {

      });
      this.getApplication().bind("query", mainListView, "query", {

      });
      this.getApplication().bind("folderId", mainListView, "folderId", {

      });
      this.getApplication().bind("modelType", mainListView, "modelType", {

      });
      mainListView.bind("modelId", this.getApplication(), "modelId", {

      });
      mainListView.bind("selectedIds", this.getApplication(), "selectedIds", {

      });
      this.getApplication().bind("selectedIds", mainListView, "selectedIds", {

      });
    }
  }
});
