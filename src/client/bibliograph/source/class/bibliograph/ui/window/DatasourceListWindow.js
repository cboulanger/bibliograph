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

/**
 * UI for choosing the datasource from a list
 * @asset(bibliograph/icon/button-reload.png)
 * @asset(qx/icon/${qx.icontheme}/22/apps/utilities-archiver.png)
 */
qx.Class.define("bibliograph.ui.window.DatasourceListWindow",
{
  extend : qx.ui.window.Window,
  construct : function()
  {
    this.base(arguments);
    let app = this.getApplication();
    this.setWidth(300);

    qx.event.message.Bus.getInstance().subscribe("user.loggedout", function(e) {
      this.close()
    }, this)

    var qxVbox1 = new qx.ui.layout.VBox(5, null, null);
    qxVbox1.setSpacing(5);
    this.setLayout(qxVbox1);
    var qxAtom1 = new qx.ui.basic.Atom();
    qxAtom1.setPadding(10);
    qxAtom1.setIcon("icon/22/apps/utilities-archiver.png");
    qxAtom1.setLabel(this.tr('Please select the datasource'));
    this.add(qxAtom1);
    var dsList = new qx.ui.form.List();
    dsList.setAllowStretchY(true);
    this.add(dsList, {
      flex : 1
    });
    var dsController = new qx.data.controller.List(null, dsList, "label");
    this.getApplication().getDatasourceStore().bind("model", dsController, "model");
    this.getApplication().bind("datasource", dsList, "selection", {
        converter : qx.lang.Function.bind(bibliograph.Utils.getModelValueListElement, dsList)
    });
    dsList.addListener("changeSelection", function(e)    {
      var sel = e.getData();
      if (sel.length) {
        app.setDatasource(sel[0].getModel().getValue());
      }
    }, this);

    dsList.addListener("changeSelection", function(e) {
      if (e.getData().length) {
        qx.event.Timer.once(function() {
          this.hide();
        }, this, 1000);
      }
    }, this);
    // dsList.addListener("appear", (e) => {
    //   this.center();
    //   var root = app.getRoot();
    //   root.setBlockerOpacity(0.5);
    //   root.setBlockerColor("black");
    //   root.blockContent(this.getZIndex() - 1);
    // }, this);
    // dsList.addListener("disappear", function(e) {
    //   //this.getApplicationRoot().unblockContent(); // not working with qx > 3.0
    // }, this);
    var qxHbox1 = new qx.ui.layout.HBox(5, null, null);
    var qxComposite1 = new qx.ui.container.Composite();
    qxComposite1.setLayout(qxHbox1)
    this.add(qxComposite1);
    qxHbox1.setSpacing(5);
    var qxButton1 = new qx.ui.form.Button(null, "bibliograph/icon/button-reload.png", null);
    qxButton1.setIcon("bibliograph/icon/button-reload.png");
    qxComposite1.add(qxButton1);
    qxButton1.addListener("execute", function(e) {
      qx.event.message.Bus.dispatchByName("datasources.reload");
    }, this);
    var qxButton2 = new qx.ui.form.Button(this.tr('OK'), null, null);
    qxButton2.setLabel(this.tr('OK'));
    qxComposite1.add(qxButton2);
    qxButton2.addListener("execute", function(e) {
      this.hide();
    }, this);
  }
}); 
