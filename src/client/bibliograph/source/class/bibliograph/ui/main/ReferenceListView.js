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
qx.Class.define("bibliograph.ui.main.ReferenceListView",
{
  extend : qx.ui.container.Composite,
  construct : function()
  {
    this.base(arguments);
    this.setLayout(new qx.ui.layout.HBox());
    const app = this.getApplication();
    const listView = new bibliograph.ui.reference.ListViewUi();
    this.mainListView = listView;
    listView.setWidgetId("bibliograph/mainListView");
    listView.setAllowStretchY(true);
    this.add(listView, {flex : 1});
    
    app.bind("datasource", listView, "datasource");
    app.bind("query", listView, "query");
    app.bind("folderId", listView, "folderId");
    app.bind("modelType", listView, "modelType");
    listView.bind("modelId", app, "modelId");
    app.bind("modelId", listView, "modelId");
    listView.bind("selectedIds", app, "selectedIds");
    //app.bind("selectedIds", listView, "selectedIds");
  }
});
