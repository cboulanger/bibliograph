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
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
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
      this.getApplication().bind("datasourceStore.model", dsController, "model", {

      });
      dsList.bind("selection", this.getApplication(), "datasource", {
        converter : this.getApplication().getSelectionValue
      });
      this.getApplication().bind("datasource", dsList, "selection", {
        converter : qx.lang.Function.bind(this.getApplication().getModelValueListElement, dsList)
      });
    }
  }
});
