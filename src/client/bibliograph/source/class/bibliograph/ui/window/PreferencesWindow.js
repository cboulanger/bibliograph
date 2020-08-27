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


/**
 * The window containing the preferences
 */
qx.Class.define("bibliograph.ui.window.PreferencesWindow", {
  extend: qx.ui.window.Window,
  construct: function() {
    this.base(arguments);

    let app = qx.core.Init.getApplication();
    let permMgr = app.getAccessManager().getPermissionManager();
    let confMgr = app.getConfigManager();

    // Window
    this.setCaption(this.tr("Preferences"));
    this.setHeight(400);
    this.setWidth(600);
    this.addListener("appear", this.center, this);
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, this.close, this);
    this.setLayout(new qx.ui.layout.VBox(5));

    // Tabview
    let tabView = new qx.ui.tabview.TabView();
    this.tabView = tabView;
    this.add(tabView, { flex: 1 });

    // work around strange bug that displays first and second tab simultaneously
    tabView.addListener("appear", function() {
      let selectables = tabView.getSelectables();
      tabView.setSelection([selectables[1]]);
      tabView.setSelection([selectables[0]]);
    });

    // general settings tab
    let qxPage1 = new qx.ui.tabview.Page(this.tr("General"));
    tabView.add(qxPage1);
    let qxGrid1 = new qx.ui.layout.Grid();
    qxGrid1.setSpacing(5);
    qxPage1.setLayout(qxGrid1);
    qxGrid1.setColumnWidth(0, 200);
    qxGrid1.setColumnFlex(1, 2);

    // title
    let qxLabel1 = new qx.ui.basic.Label(this.tr("Application title"));
    qxPage1.add(qxLabel1, {
      row: 0,
      column: 0
    });
    let qxTextarea1 = new qx.ui.form.TextArea(null);
    qxPage1.add(qxTextarea1, {
      row: 0,
      column: 1
    });
    confMgr.addListener("ready", e => confMgr.bindKey("application.title", qxTextarea1, "value", true));

    // logo
    let qxLabel2 = new qx.ui.basic.Label(this.tr("Application logo"));
    qxPage1.add(qxLabel2, { row: 1, column: 0 });
    let qxTextField1 = new qx.ui.form.TextField(null);
    qxPage1.add(qxTextField1, { row: 1, column: 1 });
    confMgr.addListener("ready", e => confMgr.bindKey("application.logo", qxTextField1, "value", true));
    
    // Tab to configure which fields to omit
    let qxPage2 = new qx.ui.tabview.Page(this.tr("Fields"));
    tabView.add(qxPage2);
    permMgr
      .create("bibliograph.fields.manage")
      .bind("state", qxPage2, "visibility", {
        converter: function(v) {
          return v ? "visible" : "excluded";
        }
      });
    let qxGrid2 = new qx.ui.layout.Grid();
    qxGrid2.setSpacing(5);
    qxPage2.setLayout(qxGrid2);
    qxGrid2.setColumnWidth(0, 200);
    qxGrid2.setColumnFlex(1, 2);
    qxGrid2.setRowFlex(1, 1);
    let qxLabel3b = new qx.ui.basic.Label(this.tr("Database"));
    qxPage2.add(qxLabel3b, { row: 0, column: 0 });
    let datasourceSelectBox = new qx.ui.form.SelectBox();
    qxPage2.add(datasourceSelectBox, { row: 0, column: 1 });
    let dsController = new qx.data.controller.List(null, datasourceSelectBox, "label");
    app.getDatasourceStore().bind("model", dsController, "model");
    datasourceSelectBox.addListener(
      "changeSelection",
      function(e) {
        let selection = e.getData()[0];
        if (!selection) {
         return;
        }
        let model = selection.getModel();
        if (typeof model.getValue !== "function") {
         return;
        }
        let datasource = model.getValue();
        let key = (this.__datasourceExcludeFieldsKey =
          "datasource." + datasource + ".fields.exclude");
        let configManager = this.getApplication().getConfigManager();
        if (configManager.keyExists(key)) {
          this.excludeFieldsTextArea.setReadOnly(false);
          this.excludeFieldsTextArea.setEnabled(true);
          this.excludeFieldsTextArea.setValue(
            configManager.getKey(key).join("\n")
          );
        } else {
          this.excludeFieldsTextArea.setEnabled(false);
          this.excludeFieldsTextArea.setReadOnly(true);
          this.excludeFieldsTextArea.setValue("");
        }
      },
      this
    );
    let qxLabel4 = new qx.ui.basic.Label(this.tr("Exclude fields"));
    qxLabel4.setValue(this.tr("Exclude fields"));
    qxPage2.add(qxLabel4, { row: 1, column: 0 });
    
    // excluded fields textarea
    let excludeFieldsTextArea = new qx.ui.form.TextArea();
    this.excludeFieldsTextArea = excludeFieldsTextArea;
    excludeFieldsTextArea.setReadOnly(true);
    excludeFieldsTextArea.setPlaceholder(
      this.tr("Enter the names of fields to exclude.")
    );
    qxPage2.add(excludeFieldsTextArea, { row: 1, column: 1 });
    excludeFieldsTextArea.addListener("changeValue", e => {
      let key = this.__datasourceExcludeFieldsKey;
      let value = e.getData().split("\n");
      if (
        confMgr.keyExists(key) &&
        confMgr.getKey(key).join("") !== value.join("") &&
        this.isVisible()
      ) {
        confMgr.setKey(key, value);
      }
    });
    //
    // // excluded fields checkbox list
    // let list = new qx.ui.list.List();
    // qxPage2.add(list, { row: 2, column: 1 });
    // // create the delegate to change the bindings
    // let delegate = {
    //   configureItem: function (item) {
    //     item.setPadding(3);
    //   },
    //   createItem: function () {
    //     return new qx.ui.form.CheckBox();
    //   },
    //   bindItem: function (controller, item, id) {
    //     controller.bindProperty("label", "label", null, item, id);
    //     controller.bindProperty("active", "value", { converter: v => !! v }, item, id);
    //     controller.bindPropertyReverse("active", "value", { converter: v => !! v }, item, id);
    //   }
    // };
    // list.setDelegate(delegate);
    // datasourceSelectBox.addListener( "changeSelection", e => {
    //   let key = this.__datasourceExcludeFieldsKey;
    //   if ( confMgr.keyExists(key) && this.isVisible() ) {
    //     //...
    //   }
    // });

    // access tab
    let qxPage3 = new qx.ui.tabview.Page(this.tr("Access"));
    tabView.add(qxPage3);
    permMgr.create("access.manage").bind("state", qxPage3, "enabled", {});
    let qxGrid3 = new qx.ui.layout.Grid();
    qxGrid3.setSpacing(5);
    qxPage3.setLayout(qxGrid3);
    qxGrid3.setColumnWidth(0, 200);
    qxGrid3.setColumnFlex(1, 2);

    // Authenication mode
    let authModeLabel = new qx.ui.basic.Label(
      this.tr("Authentication method")
    );
    qxPage3.add(authModeLabel, { row: 0, column: 0 });
    let authModeSelBox = new qx.ui.form.SelectBox();
    qxPage3.add(authModeSelBox, { row: 0, column: 1 });
    let item1 = new qx.ui.form.ListItem(
      this.tr("Client sends plain text password"),
      null,
      "plaintext"
    );
    authModeSelBox.add(item1);
    let item2 = new qx.ui.form.ListItem(
      this.tr("Client sends hashed password"),
      null,
      "hashed"
    );
    authModeSelBox.add(item2);
    authModeSelBox.addListener(
      "appear",
      function(e) {
        let mode = this.getApplication()
          .getConfigManager()
          .getKey("authentication.method");
        authModeSelBox.getSelectables().forEach(function(elem) {
          if (elem.getModel("value") === mode) {
           authModeSelBox.setSelection([elem]);
          }
        }, this);
      },
      this
    );
    authModeSelBox.addListener(
      "changeSelection",
      function(e) {
        if (e.getData().length) {
          let mode = e.getData()[0].getModel();
          let location = window.location;
          if (mode === "plaintext" && location.protocol !== "https:") {
            let msg = this.tr("Plaintext passwords without a secure connection (HTTPS) are not allowed.");
            this.getApplication().error(msg);
            return;
          }
          this.getApplication()
            .getConfigManager()
            .setKeyAsync("authentication.method", mode);
        }
      },
      this
    );

    // Access mode
    // let qxLabel5 = new qx.ui.basic.Label(this.tr("Access Mode"));
    // qxPage3.add(qxLabel5, { row: 1, column: 0 });
    // let modelSelectBox = new qx.ui.form.SelectBox();
    // qxPage3.add(modelSelectBox, { row: 1, column: 1 });
    // let qxListItem1 = new qx.ui.form.ListItem(this.tr("Normal"), null, null);
    // modelSelectBox.add(qxListItem1);
    // qxListItem1.setUserData("value", "normal");
    // let qxListItem2 = new qx.ui.form.ListItem(
    //   this.tr("Read-Only"),
    //   null,
    //   null
    // );
    // modelSelectBox.add(qxListItem2);
    // qxListItem2.setUserData("value", "readonly");
    // modelSelectBox.addListener(
    //   "appear",
    //   function(e) {
    //     let mode = this.getApplication()
    //       .getConfigManager()
    //       .getKey("bibliograph.access.mode");
    //     modelSelectBox.getSelectables().forEach(function(elem) {
    //       if (elem.getUserData("value") == mode)
    //         modelSelectBox.setSelection([elem]);
    //     }, this);
    //   },
    //   this
    // );
    // modelSelectBox.addListener(
    //   "changeSelection",
    //   function(e) {
    //     if (e.getData().length)
    //       this.getApplication()
    //         .getConfigManager()
    //         .setKey(
    //           "bibliograph.access.mode",
    //           e.getData()[0].getUserData("value")
    //         );
    //   },
    //   this
    // );
    // let qxLabel6 = new qx.ui.basic.Label(this.tr("Custom message"));
    // qxPage3.add(qxLabel6, { row: 2, column: 0 });
    // let qxTextarea2 = new qx.ui.form.TextArea(null);
    // qxTextarea2.setPlaceholder(
    //   this.tr("Shown to users in read-only and no-access mode.")
    // );
    // qxPage3.add(qxTextarea2, { row: 2, column: 1 });
    // confMgr.addListener("ready", function() {
    //   confMgr.bindKey(
    //     "bibliograph.access.no-access-message",
    //     qxTextarea2,
    //     "value",
    //     true
    //   );
    // });
  }
});
