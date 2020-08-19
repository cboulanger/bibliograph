/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

qx.Class.define("bibliograph.plugins.webservices.View",
{
  extend: qx.ui.container.Composite,
  include: [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties : {
  
    /**
     * The containing window object, either a qx.ui.window.Window or a native
     * Window object
     */
    window:{
      check: "Object"
    },
  
    /**
     * The datasource to search
     */
    datasource : {
      check: "String",
      nullable: true,
      event: "changeDatasource",
      apply: "_applyDatasource"
    },
  
    /**
     * Whether to auto-import the first/best search result. This will also
     * auto-submit recognizable identifiers such as ISBNs or DOIs
     */
    autoimport : {
      check: "Boolean",
      init: false,
      event: "changeAutomimport"
    },
  
    /**
     * The search text
     */
    search: {
      check: "String",
      nullable: true,
      event: "changeSearch"
    }
  },

  /**
   * Constructor
   */
  construct: function () {
    this.base(arguments);
    this.setLayout(new qx.ui.layout.VBox(5));
    this.createPopup();
    
    // create toolbar with select box and search bar
    this.add(this.getQxObject("toolbar"));
    this.add(this.getQxObject("autoimport"));
    
    // select box with list of datasources
    this.__selectBox.bind("selection[0].label", this.__selectBox, "toolTipText");
    this.__selectBox.bind("selection[0].value", this, "datasource");
    // store for selectbox
    let store = new qcl.data.store.JsonRpcStore("webservices.table");
    store.setModel(qx.data.marshal.Json.createModel([]));
    store.bind("model", this.__selectBox, "model");
    store.addListener("loaded", () => {
      let lastDatasource = this.getApplication()
        .getConfigManager()
        .getKey("modules.webservices.lastDatasource");
      if (lastDatasource) {
        this.setDatasource(lastDatasource);
      }
    });
    this.addListener("appear", () => store.load("server-list"));
    qx.event.message.Bus.getInstance().subscribe("plugins.webservices.reloadDatasources", () => store.load("server-list"));
    
    // help button
    this.__helpButton.addListener("execute", () =>
      this.getApplication()
        .getCommands()
        .showHelpWindow("plugin/webservices/search"));
    
    // searchbox
    this.__searchBox.addListener("input", e => this.setSearch(e.getData()));
    this.__searchBox.addListener("changeValue", e => this.setSearch(e.getData()));
    this.__searchBox.addListener("keypress", this.__onKeypress, this);
    this.bind("search", this.__searchBox, "value");
    
    // create and configure listview
    let listview = this.getQxObject("listview");
    this.add(listview, {flex: 1});
    listview.set({
      modelType: "record",
      serviceName: "webservices.table"
    });
    // populate the list when the data is ready
    qx.event.message.Bus.getInstance().subscribe("webservices.dataReady", e => {
      listview.setQuery(null);
      listview.setQuery(e.getData());
    });
    
    // when table is ready
    qx.lang.Function.delay(() => {
      this.__listView.addListenerOnce("tableReady", () => {
        let controller = this.__listView.getController();
        let enableButtons = () => {
          this.__searchBar.setEnabled(true);
          this.__importButton.setEnabled(true);
          this.__listView.setEnabled(true);
          this.hidePopup();
          if (this.getAutoimport() && this.getSearch()) {
           this.__listView
             .getTable()
             .getSelectionManager()
             .getSelectionModel()
             .setSelectionInterval(0, 0);
           this.importSelected();
           this.setSearch(null);
           this.__searchBox.focus();
          }
        };
        controller.addListener("blockLoaded", enableButtons);
        controller.addListener("statusMessage", e => {
          this.showPopup(e.getData());
          qx.lang.Function.delay(enableButtons, 1000, this);
        });
      });
    }, 100);
  
    // create __footer with status and import/close buttons
    this.add(this.getQxObject("footer"));
    this.__importButton.addListener("execute", () => this.importSelected());
    this.__closeButton.addListener("execute", () => this.getWindow().close());
    this.__listView.bind("store.model.statusText", this.__status, "value");
    
    // progress bar
    this.__serverProgress = qx.core.Id.getQxObject("plugins-webservices-progress");
    // re-enable searchbar after a progress request
    this.__serverProgress.addListener("error", () => this.__searchBar.setEnabled(true));
    this.__serverProgress.addListener("message", () => this.__searchBar.setEnabled(true));
    this.__serverProgress.addListener("done", () => {
      this.__searchBar.setEnabled(true);
      this.__searchBox.setValue("");
    });
  },
  
  members:
  {
    _createQxObjectImpl(id) {
      let control;
      switch (id) {
        case "toolbar":
          control = new qx.ui.toolbar.ToolBar();
          control.add(this.getQxObject("selectbox"));
          control.addSpacer();
          control.add(this.getQxObject("search-bar"), {flex: 1});
          this.___toolbar = control;
          break;
        case "selectbox":
          control = new qx.ui.form.VirtualSelectBox();
          control.set({
            labelPath: "label",
            width: 300,
            maxHeight: 30,
            marginTop: 8,
            marginLeft: 4
          });
          this.__selectBox = control;
          break;
        case "search-bar":
          control = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
          control.setPadding(4);
          control.add(this.getQxObject("search-box"), {flex: 1});
          control.add(this.getQxObject("search-clear-button"));
          control.add(this.getQxObject("search-button"));
          control.add(this.getQxObject("help-button"));
          this.__searchBar = control;
          break;
        case "search-box":
          control = new qx.ui.form.TextField();
          control.set({
            padding: 2,
            margin: 4,
            height: 30,
            placeholder: this.tr("Enter search terms")
          });
          control.addListener("dblclick", e => e.stopPropagation());
          this.__searchBox = control;
          break;
        case "search-button":
          control = new qx.ui.form.Button(this.tr("Search"));
          control.addListener("execute", () => this.startSearch());
          this.__searchButton = control;
          break;
        case "search-clear-button":
          control = new qx.ui.toolbar.Button();
          control.setIcon("bibliograph/icon/16/cancel.png");
          control.set({
            margin: 4,
            height: 30
          });
          control.addListener("execute", () => {
            this.getQxObject("search-box").setValue("");
            this.getQxObject("search-box").focus();
          });
          this.__searchClearButton = control;
          break;
        case "autoimport":
          control = new qx.ui.form.CheckBox(this.tr("Auto-import best result"));
          control.bind("value", this, "autoimport");
          this.bind("autoimport", control, "value");
          this.__autoimport = control;
          break;
        case "help-button":
          control = new qx.ui.toolbar.Button(this.tr("Help"));
          this.__helpButton = control;
          break;
        case "listview":
          control = new qcl.ui.table.TableView();
          control.setDecorator("main"); //??
          control.headerBar.setVisibility("excluded");
          control.menuBar.setVisibility("excluded");
          this.__listView = control;
          break;
        case "footer":
          control = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
          control.add(this.getQxObject("status-label"));
          control.add(new qx.ui.core.Spacer(), { flex: 10 });
          control.add(this.getQxObject("import-button"));
          control.add(this.getQxObject("close-button"));
          this.__footer = control;
          break;
        case "status-label":
          control = new qx.ui.basic.Label();
          control.setTextColor("#808080");
          this.__status = control;
          break;
        case "import-button":
          control = new qx.ui.form.Button(this.tr("Import selected records"));
          control.setEnabled(false);
          this.__importButton = control;
          break;
        case "close-button":
          control = new qx.ui.form.Button(this.tr("Close"));
          control.addListener("execute", () => this.getWindow().close());
          this.__closeButton = control;
          break;
      }
      return control || this.base(arguments, id);
    },

    _applyDatasource : function(value, old) {
      if (value) {
        this.__selectBox.getModel().forEach(item => {
          if (item.getValue() === value) {
            this.__selectBox.getSelection().setItem(0, item);
          }
        });
        this.getApplication().getConfigManager().setKey("modules.webservices.lastDatasource", value);
      }
      this.info("Webservices datasource is now: " + value);
    },

    /**
     * Starts the search
     */
    startSearch: function () {
      let datasource = this.__selectBox.getSelection().getItem(0).getValue();
      let query = this.__searchBox.getValue();

      // update the UI
      let lv = this.__listView;
      lv.setDatasource(datasource);
      lv.clearTable();
      lv.setEnabled(false);
      this.__searchBar.setEnabled(false);
      // open the ServerProgress widget and initiate the remote search
      this.__serverProgress
        .set({message: this.tr("Searching...")})
        .start({ datasource, query });
    },
    
    
    /**
     * Imports the selected references
     */
    importSelected: async function () {
      let app = this.getApplication();
      
      // ids to import
      let ids = this.__listView.getSelectedIds();
      if (!ids.length) {
        await this.getApplication().alert(this.tr("You have to select one or more reference to import."));
        return;
      }
      
      // target folder
      let targetFolderId = app.getFolderId();
      if (!targetFolderId) {
        await this.getApplication().alert(this.tr("Please select a folder first."));
        return;
      }
      let treeView = app.getWidgetById(bibliograph.Application.ids.app.treeview);
      let nodeId = treeView.getController().getClientNodeId(targetFolderId);
      let node = treeView.getTree().getDataModel().getData()[nodeId];
      if (!node) {
        await this.getApplication().alert(this.tr("Cannot determine selected folder. Please reload the folders."));
        return;
      }
      if (node.data.type !== "folder") {
        await this.getApplication().alert(this.tr("Invalid target folder. You can only import into normal folders."));
        return;
      }
      
      // send to server
      let sourceDatasource = this.__selectBox.getSelection().toArray()[0].getValue();
      let targetDatasource = app.getDatasource();
      this.__importButton.setEnabled(false);
      this.showPopup(this.tr("Importing references..."));
      try {
        await this.getApplication()
          .getRpcClient("webservices.table")
          .request("import", [sourceDatasource, ids, targetDatasource, targetFolderId]);
      } finally {
        this.__importButton.setEnabled(true);
        this.hidePopup();
        this.__searchBox.setValue("");
        this.__searchBox.focus();
      }
    },


    /**
     * Called when the user presses a key in the search box
     * @param e {qx.event.type.Data}
     */
    __onKeypress: function (e) {
      if (e.getKeyIdentifier() === "Enter") {
        this.startSearch();
      }
      // TODO: why is this disabled?
      // if (this.getAutoimport()) {
      //   let searchText = this.getSearch();
      //   // auto-submit ISBNs
      //   if (searchText && searchText.length > 12 && searchText.replace(/[^0-9xX]/g, "").length === 13 && searchText.substr(0, 3) === "978") {
      //     this.startSearch();
      //   }
      // }
    }
  }
});
