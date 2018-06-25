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

/*global qx qcl bibliograph*/

qx.Class.define("bibliograph.ui.main.ItemViewUi", {
  extend: bibliograph.ui.main.ItemView,
  construct: function() 
  {
    this.base(arguments);

    var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
    this.setLayout(qxVbox1);

    // stack view
    var itemViewStack = new qx.ui.container.Stack();
    this.itemViewStack = itemViewStack;
    itemViewStack.setWidgetId("app/item/stack");
    itemViewStack.addListener("appear", this.toggleReferenceView, this);
    this.add(itemViewStack, { flex: 1 });
  
    // reference editor
    // @todo wiget id should be app/item/stack/editor
    var referenceEditor = new bibliograph.ui.item.ReferenceEditorUi();
    referenceEditor.setWidgetId("app/item/editor");
    referenceEditor.setVisibility("hidden");
    this.addView("referenceEditor", referenceEditor);
    // setup bindings
    this.getApplication().bind("datasource", referenceEditor, "datasource");
    this.getApplication().bind("modelType", referenceEditor, "modelType");
    this.getApplication().bind("modelId", referenceEditor, "referenceId");

    // table view
    var tableView = new bibliograph.ui.item.TableViewUi();
    tableView.setVisibility("hidden");
    this.addView( "tableView", tableView );

    // formatted view
    // @todo: test & activate
    //let [page,button] = this.createFormattedView();
    //this.addView("formattedView", page);
    //
  },

  members : {
    createFormattedView : function(){
      // widget
      var formattedViewPage = new bibliograph.ui.item.FormattedViewUi();
      // buttons
      var formattedViewButton = new qx.ui.menubar.Button(this.tr("Formatted View"));
      formattedViewButton.addListener("click", function() {
        this.setView("formattedView");
      });
      this.getViewByName("tableView").menuBar.add(formattedViewButton);
      var formattedViewButton2 = new qx.ui.menubar.Button(this.tr("Formatted View"));
      formattedViewButton2.addListener("click", function() {
        this.setView("formattedView");
      });      
      this.getViewByName("referenceEditor").menuBar.add(formattedViewButton2);  
      return [formattedViewPage, formattedViewButton];
    }
  }
});
