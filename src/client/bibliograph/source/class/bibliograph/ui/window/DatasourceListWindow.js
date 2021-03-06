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
 * @asset(qx/icon/Tango/22/apps/utilities-archiver.png)
 */
qx.Class.define("bibliograph.ui.window.DatasourceListWindow",
{
  extend : qx.ui.window.Window,
  type : "singleton",
  construct : function() {
    this.base(arguments);
    this.setWidth(300);
    this.setVisibility("excluded");
    this.addListener("appear", e => this.center());

    let app = this.getApplication();
    //qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, () => this.close());

    let vbox1 = new qx.ui.layout.VBox(5);
    vbox1.setSpacing(5);
    this.setLayout(vbox1);
    let atom1 = new qx.ui.basic.Atom();
    atom1.setPadding(10);
    atom1.setIcon("icon/22/apps/utilities-archiver.png");
    atom1.setLabel(this.tr("Please select the datasource"));
    this.add(atom1);

    // list
    let dsList = new qx.ui.form.List();
    dsList.setAllowStretchY(true);
    this.add(dsList, { flex : 1 });
    dsList.setQxObjectId("list");
    this.addOwnedQxObject(dsList);

    // controller
    let dsController = new qx.data.controller.List(null, dsList, "label");
    bibliograph.store.Datasources.getInstance().bind("model", dsController, "model");

    // make list items addressable with qooxdoo ids
    // does this really make sense?
    dsController.addListener("changeModel", () => {
      qx.event.Timer.once(() => {
        dsList.getOwnedQxObjects().map(o => dsList.removeOwnedQxObject(o));
        dsList.getChildren().forEach(item => {
          item.setQxObjectId(item.getModel().getValue());
          dsList.addOwnedQxObject(item);
        });
      }, this, 50);
    });

    this.getApplication().bind("datasource", dsList, "selection", {
      converter : v => bibliograph.Utils.getListElementWithValue(dsList, v)
    });

    // event listeners
    dsList.addListener("changeSelection", function(e) {
      let sel = e.getData();
      if (sel.length) {
        app.setDatasource(sel[0].getModel().getValue());
        qx.event.Timer.once(function() {
          this.hide();
        }, this, 1000);
      }
    }, this);

    // buttons
    let hbox1 = new qx.ui.layout.HBox(5, null, null);
    let composite1 = new qx.ui.container.Composite();
    composite1.setLayout(hbox1);
    this.add(composite1);
    hbox1.setSpacing(5);
    let button1 = new qx.ui.form.Button();
    button1.setIcon("bibliograph/icon/button-reload.png");
    composite1.add(button1);
    button1.addListener("execute", function(e) {
      qx.event.message.Bus.dispatchByName("datasources.reload");
    }, this);
    let button2 = new qx.ui.form.Button(this.tr("OK"), null, null);
    button2.setLabel(this.tr("OK"));
    composite1.add(button2);
    button2.addListener("execute", function(e) {
      this.hide();
    }, this);
  }
});
