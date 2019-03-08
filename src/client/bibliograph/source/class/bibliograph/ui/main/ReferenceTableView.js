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
  extend : bibliograph.ui.main.TableView,
  construct : function() {
    this.base(arguments);
    this.setAllowStretchY(true);
    this.setServiceName("reference");
    let app = this.getApplication();
    app.bind("datasource", this, "datasource");
    app.bind("query", this, "query");
    app.bind("folderId", this, "folderId");
    app.bind("modelType", this, "modelType");
    this.bind("modelId", app, "modelId");
    app.bind("modelId", this, "modelId");
    this.bind("selectedIds", app, "selectedIds");
    //app.bind("selectedIds", listView, "selectedIds");
    app.setModelType("reference");
  }
});
