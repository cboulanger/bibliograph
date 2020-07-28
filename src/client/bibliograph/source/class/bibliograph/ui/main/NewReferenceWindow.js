qx.Class.define("bibliograph.ui.main.NewReferenceWindow", {
  extend: qx.ui.window.Window,
  
  events: {
    "referenceTypeSelected" : "qx.event.type.Data"
  },
  
  construct: function() {
    this.base(arguments);
    let app = qx.core.Init.getApplication();
    this.set({
      caption: this.tr("Create new reference type"),
      layout: new qx.ui.layout.VBox(5),
      height: 300, width: 200,
      showMinimize: false,
      showMaximize: false,
      modal: true
    });
  
    // close on logout
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.LOGOUT, this.close, this);
  
    // blocker
    this.addListener("appear", () => {
      this.center();
      app.getBlocker().blockContent(this.getZIndex() - 1);
    });
    this.addListener("disappear", () => app.getBlocker().unblock());
  
    // List widget, will be populated later
    let list = new qx.ui.list.List();
    list.set({
      iconPath: "icon",
      labelPath: "label"
    });
    list.addListener("dblclick", this.__onReferenceTypeSelected, this);
    this.add(list, {flex: 1});
    this.addOwnedQxObject(list, "list");
  
    // OK button
    let okButton = new qx.ui.form.Button(this.tr("Create"));
    okButton.addListener("execute", this.__onReferenceTypeSelected, this);
    this.add(okButton);
    this.addOwnedQxObject(okButton, "ok");
  
    // Cancel button
    let cancelButton = new qx.ui.form.Button(this.tr("Cancel"));
    cancelButton.addListener("execute", () => this.close());
    this.add(cancelButton);
    this.addOwnedQxObject(cancelButton, "cancel");

    // add to window
    app.getRoot().add(this);
  },
  members: {
    __onReferenceTypeSelected() {
      let sel = this.getQxObject("list").getSelection();
      if (sel.getLength()) {
        let type = sel.getItem(0).getValue();
        this.fireDataEvent("referenceTypeSelected", type);
        qx.lang.Function.delay(() => this.close(), 100);
      }
    }
  }
});
