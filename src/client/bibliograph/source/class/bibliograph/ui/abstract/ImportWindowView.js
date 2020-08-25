/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2020 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

qx.Class.define("bibliograph.ui.abstract.ImportWindowView",
{
  extend: qx.ui.container.Composite,
  include: [qcl.ui.MLoadingPopup],
  properties : {
  
    /**
     * The name of the module using this template
     */
    moduleName: {
      check: "String"
    },
  
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
     * The search text
     */
    search: {
      check: "String",
      nullable: true,
      event: "changeSearch",
      apply: "_applySearch"
    }
  },
  
  members:
  {
  
    _applyDatasource : function(value, old) {
      if (value) {
        this._selectBox.getModel().forEach(item => {
          if (item.getValue() === value) {
            this._selectBox.getSelection().setItem(0, item);
          }
        });
        this.getApplication().getConfigManager().setKey(`modules.${this.getModuleName()}.lastDatasource`, value);
      }
    },
  
    _applySearch(value) {
      if (!value && this._listView) {
        this._listView.clearTable();
      }
    },
    
    _createQxObjectImpl(id) {
      let control;
      switch (id) {
        case "toolbar":
          control = new qx.ui.toolbar.ToolBar();
          control.add(this.getQxObject("selectbox"));
          control.add(this.getQxObject("search-bar"), {flex: 1});
          this._toolbar = control;
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
          this._selectBox = control;
          this._configureSelectBox();
          break;
        case "search-bar":
          control = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
          control.setPadding(4);
          control.add(this.getQxObject("search-box"), {flex: 1});
          control.add(this.getQxObject("search-clear-button"));
          control.add(this.getQxObject("search-button"));
          control.add(this.getQxObject("help-button"));
          this._searchBar = control;
          break;
        case "search-box":
          control = new qx.ui.form.TextField();
          control.set({
            padding: 2,
            margin: 4,
            height: 30,
            placeholder: this.tr("Enter search terms")
          });
          control.bind("value", this, "search");
          this.bind("search", control, "value");
          control.addListener("dblclick", e => e.stopPropagation());
          control.addListener("keypress", this._on_keypress, this);
          control.addListener("input", this._on_input, this);
          this._searchBox = control;
          break;
        case "search-button":
          control = new qx.ui.form.Button(this.tr("Search"));
          control.addListener("execute", () => this.startSearch());
          this._searchButton = control;
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
          this._searchClearButton = control;
          break;
        case "autoimport":
          control = new qx.ui.form.CheckBox(this.tr("Auto-import best result"));
          control.bind("value", this, "autoimport");
          this.bind("autoimport", control, "value");
          this._autoimport = control;
          break;
        case "help-button":
          control = new qx.ui.toolbar.Button(this.tr("Help"));
          control.addListener("execute", () =>
            this.getApplication()
              .getCommands()
              .showHelpWindow(`plugin/${this.getModuleName()}/search`));
          this._helpButton = control;
          break;
        case "listview":
          control = new qcl.ui.table.TableView();
          control.setDecorator("main"); //??
          control.set({
            modelType: "record",
            serviceName: `${this.getModuleName()}.table`
          });
          control.headerBar.setVisibility("excluded");
          control.menuBar.setVisibility("excluded");
          control.addListener("tableReady", this._on_tableReady, this);
          this._listView = control;
          break;
        case "footer":
          control = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
          control.add(this.getQxObject("status-label"));
          control.add(new qx.ui.core.Spacer(), { flex: 10 });
          control.add(this.getQxObject("import-button"));
          control.add(this.getQxObject("close-button"));
          this._footer = control;
          break;
        case "status-label":
          control = new qx.ui.basic.Label();
          control.setTextColor("#808080");
          this.getQxObject("listview").bind("store.model.statusText", control, "value");
          this._status = control;
          break;
        case "import-button":
          control = new qx.ui.form.Button(this.tr("Import selected records"));
          control.setEnabled(false);
          control.addListener("execute", () => this.importSelected());
          this._importButton = control;
          break;
        case "close-button":
          control = new qx.ui.form.Button(this.tr("Close"));
          control.addListener("execute", () => this.getWindow().close());
          this._closeButton = control;
          break;
      }
      return control || this.base(arguments, id);
    },
    
    _configureSelectBox() {
      this._selectBox.bind("selection[0].label", this._selectBox, "toolTipText");
      this._selectBox.bind("selection[0].value", this, "datasource");
      // store for selectbox
      let store = new qcl.data.store.JsonRpcStore(`${this.getModuleName()}.table`);
      store.setModel(qx.data.marshal.Json.createModel([]));
      store.bind("model", this._selectBox, "model");
      store.addListener("loaded", () => {
        let lastDatasource = this.getApplication()
          .getConfigManager()
          .getKey(`modules.${this.getModuleName()}.lastDatasource`);
        if (lastDatasource) {
          this.setDatasource(lastDatasource);
        }
      });
      store.load("server-list");
      qx.event.message.Bus.getInstance().subscribe(`plugins.${this.getModuleName()}.reloadDatasources`, () => store.load("server-list"));
    },
    
    /**
     * Called once when a table is ready in the listview to load data
     */
    _on_tableReady() {
      let selectionModel = this._listView.getTable().getSelectionModel();
      selectionModel.addListener("changeSelection", () => this._importButton.setEnabled(!selectionModel.isSelectionEmpty()));
      let controller = this._listView.getController();
      let enableButtons = () => {
        this._searchBar.setEnabled(true);
        this.hidePopup();
      };
      controller.addListener("blockLoaded", enableButtons);
    },
  
    /**
     * Called when the user presses a key in the search box
     * @param e {qx.event.type.Data}
     */
    _on_keypress: function (e) {
      if (e.getKeyIdentifier() === "Enter") {
        this.startSearch();
      }
    },
  
    /**
     * Called when the "input" event is fired on the searchbox
     * @param {qx.event.type.Data} e
     * Empty stub to be overridden
     * @private
     */
    _on_input(e) {},
    
    _setupProgressWidget() {
      this._serverProgress = qx.core.Id.getQxObject(`plugin-${this.getModuleName()}-progress`);
      // after an error
      this._serverProgress.addListener("error", () => {
        this._searchBar.setEnabled(true);
        this._importButton.setEnabled(false);
      });
      // after displaying a message
      this._serverProgress.addListener("message", () => {
        this._searchBar.setEnabled(true);
      });
      // after success
      this._serverProgress.addListener("done", () => {
        this._searchBar.setEnabled(true);
        this._listView.setEnabled(true);
        this._listView.setQuery(null);
        this._listView.setQuery(this.getSearch());
      });
    },

    /**
     * Starts the search
     * @param {String?} query The query string. If undefined, get it from the
     * search box.
     */
    startSearch(query) {
      query = query || this._searchBox.getValue();
      let datasource = this._selectBox.getSelection().getItem(0).getValue();
      // update the UI
      this._listView.setDatasource(datasource);
      this._listView.clearTable();
      this._listView.setEnabled(false);
      this._searchBar.setEnabled(false);
      this._importButton.setEnabled(false);
      // open the ServerProgress widget and initiate the remote search
      this._serverProgress
        .set({message: this.tr("Searching...")})
        .start({ datasource, query });
    },
  
    /**
     * If the table has any rows, select the first one and return true,
     * Otherwise, return false
     * @private
     * @return {Boolean}
     */
    _selectFirstRow() {
      let table = this._listView.getTable();
      if (table.getDataModel().getRowCount() > 0) {
        table.getSelectionManager().getSelectionModel().setSelectionInterval(0, 0);
        return true;
      }
      return false;
    },
    
    /**
     * Imports the selected references
     */
    importSelected: async function () {
      let app = this.getApplication();
      // ids to import
      if (this._listView.getTable().getTableModel().getRowCount() === 1) {
        this._selectFirstRow();
      }
      let ids = this._listView.getSelectedIds();
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
      let treeView = qx.core.Id.getQxObject("folder-tree-panel/tree-view");
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
      let sourceDatasource = this._selectBox.getSelection().toArray()[0].getValue();
      let targetDatasource = app.getDatasource();
      this._importButton.setEnabled(false);
      this.showPopup(this.tr("Importing references..."));
      try {
        await this.getApplication()
          .getRpcClient(`${this.getModuleName()}.table`)
          .request("import", [sourceDatasource, ids, targetDatasource, targetFolderId]);
      } finally {
        this._importButton.setEnabled(true);
        this.hidePopup();
        this._searchBox.setValue("");
        this._searchBox.focus();
      }
    }
  }
});
