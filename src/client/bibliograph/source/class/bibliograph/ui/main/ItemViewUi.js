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
    var itemView = this;
    this.setLayout(qxVbox1);
    this.getApplication().addListener(
      "changeModelId",
      function(e) {
        this.setVisibility(e.getData() ? "visible" : "hidden");
      },
      this
    );

    // stack view
    var itemViewStack = new qx.ui.container.Stack();
    this.itemViewStack = itemViewStack;
    itemViewStack.setWidgetId("app/item/stack");
    itemView.add(itemViewStack, { flex: 1 });

    // reference editor
    // @todo wiget id should be app/item/stack/editor
    var referenceEditor = new bibliograph.ui.item.ReferenceEditorUi();
    referenceEditor.setWidgetId("bibliograph/referenceEditor");
    referenceEditor.setVisibility("hidden");
    itemViewStack.add(referenceEditor);
    referenceEditor.setUserData("name", "referenceEditor");
    this.getApplication().bind("datasource", referenceEditor, "datasource");
    this.getApplication().bind("modelType", referenceEditor, "modelType");
    this.getApplication().bind("modelId", referenceEditor, "referenceId");

    // table view
    var tableView = new bibliograph.ui.item.TableViewUi();
    tableView.setVisibility("hidden");
    itemViewStack.add(tableView);
    tableView.setUserData("name", "tableView");

    // formatted view
    // @todo: test & activate
/*     var formattedView = new bibliograph.ui.item.FormattedViewUi();
    itemView.addView("formattedView", formattedView);
    var button = new qx.ui.menubar.Button(this.tr("Formatted View"));
    button.addListener("click", function() {
      itemView.setView("formattedView");
    });
    itemView.getViewByName("tableView").menuBar.add(button);
    var button = new qx.ui.menubar.Button(this.tr("Formatted View"));
    button.addListener("click", function() {
      itemView.setView("formattedView");
    });
    // this adds the button to the reference editor footer
    itemView.getViewByName("referenceEditor").menuBar.add(button);     */

    // event handlers
    qx.event.message.Bus
      .getInstance()
      .subscribe("user.loggedin", this.toggleReferenceView, this);
    qx.event.message.Bus.getInstance().subscribe(
      "logout",
      function(e) {
        this.setView(null);
      },
      this
    );
    itemViewStack.addListener("appear", this.toggleReferenceView, this);
    this.getApplication().addListener(
      "changeModelId",
      this.toggleReferenceView,
      this
    );
  }
});
