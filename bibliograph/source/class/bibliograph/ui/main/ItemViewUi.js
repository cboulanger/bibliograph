/**
    Generated by QxTransformer v.0.4.
    Author Christian Boulanger
**/

/*------------------------------------------------------------------------------

------------------------------------------------------------------------------*/
qx.Class.define("bibliograph.ui.main.ItemViewUi",
{
  extend : bibliograph.ui.main.ItemView,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var itemView = this;
      this.setLayout(qxVbox1)
      this.getApplication().addListener("changeModelId", function(e) {
        this.setVisibility(e.getData() ? "visible" : "hidden");
      }, this);
      var itemViewStack = new qx.ui.container.Stack();
      this.itemViewStack = itemViewStack;
      itemView.add(itemViewStack, {
        flex : 1
      });
      var referenceEditor = new bibliograph.ui.item.ReferenceEditorUi();
      referenceEditor.setVisibility("hidden");
      itemViewStack.add(referenceEditor);
      referenceEditor.setUserData("name", "referenceEditor");
      this.getApplication().bind("datasource", referenceEditor, "datasource", {

      });
      this.getApplication().bind("modelType", referenceEditor, "modelType", {

      });
      this.getApplication().bind("modelId", referenceEditor, "referenceId", {

      });
      var tableView = new bibliograph.ui.item.TableViewUi();
      tableView.setVisibility("hidden");
      itemViewStack.add(tableView);
      tableView.setUserData("name", "tableView");
      qx.event.message.Bus.getInstance().subscribe("authenticated", this.toggleReferenceView, this)
      qx.event.message.Bus.getInstance().subscribe("logout", function(e) {
        this.setView(null);
      }, this)
      itemViewStack.addListener("appear", this.toggleReferenceView, this);
      this.getApplication().addListener("changeModelId", this.toggleReferenceView, this);
    }
  }
});
