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
 
/*global bibliograph qx qcl*/

/**
 * The main list of references
 */
qx.Class.define("bibliograph.ui.main.ReferenceTableView",
{
  extend : qx.ui.container.Composite,
  construct : function()
  {
    this.base(arguments);
    this.setLayout(new qx.ui.layout.HBox());
    const app = this.getApplication();
    const tableview = new bibliograph.ui.main.TableView();

    this.mainListView = tableview;
    tableview.setWidgetId("app/tableview");
    tableview.setAllowStretchY(true);
    tableview.setServiceName("reference");

    this.add(tableview, {flex : 1});
    
    app.bind("datasource", tableview, "datasource");
    app.bind("query", tableview, "query");
    app.bind("folderId", tableview, "folderId");
    app.bind("modelType", tableview, "modelType");
    tableview.bind("modelId", app, "modelId");
    app.bind("modelId", tableview, "modelId");
    tableview.bind("selectedIds", app, "selectedIds");
    //app.bind("selectedIds", listView, "selectedIds");
    app.setModelType("reference");
  }
});
