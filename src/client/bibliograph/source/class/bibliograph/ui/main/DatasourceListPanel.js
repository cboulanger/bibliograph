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

/*global qx qcl*/

qx.Class.define("bibliograph.ui.main.DatasourceListPanel",
{
  extend : qx.ui.container.Composite,
  construct : function()
  {
    this.base(arguments);
    let app = this.getApplication();

    var qxHbox1 = new qx.ui.layout.HBox(null, null, null);
    var qxComposite1 = this;
    this.setLayout(qxHbox1)
    var datasourcePanel = new collapsablepanel.Panel();
    datasourcePanel.setCaption("Datasource");
    datasourcePanel.setAppearance("collapsable-panel-classic");
    datasourcePanel.setValue(false);
    qxComposite1.add(datasourcePanel, {
      flex : 1
    });
    var dsList = new qx.ui.form.List();
    dsList.setAllowStretchY(true);
    datasourcePanel.add(dsList);
    var dsController = new qx.data.controller.List(null, dsList, "label");
    app.getDatasourceStore().bind("model", dsController, "model");
    dsList.bind("selection", this.getApplication(), "datasource", {
      converter : bibliograph.Utils.getSelectionValue
    });
    this.getApplication().bind("datasource", dsList, "selection", {
      converter : (v) => bibliograph.Utils.getListElementWithValue(dsList,v)
    });
  }
});
