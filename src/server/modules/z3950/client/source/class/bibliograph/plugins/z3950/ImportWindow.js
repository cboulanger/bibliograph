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

/**
 * Z39.50 Plugin: Application logic
 * @property {bibliograph.ConfigManager} configManager
 */
qx.Class.define("bibliograph.plugins.z3950.ImportWindow", {
  extend: qx.ui.window.Window,
  include: [qcl.ui.MLoadingPopup],
  
  properties : {
    datasource : {
      check : "String",
      nullable : true,
      event : "changeDatasource",
      apply : "_applyDatasource"
    }
  },

  /**
   * Constructor
   */
  construct: function () {
    this.base(arguments);
    this.set({
      width: 700,
      height: 500,
      layout: new qx.ui.layout.VBox(5),
      caption: this.tr("Import from library catalog"),
      showMinimize: false,
      visibility: "excluded"
    });
    this.addListener("appear", () => {
      qx.event.message.Bus.dispatchByName("plugins.z3950.reloadDatasources");
    });
    this.createPopup();
  
    // create toolbar with selectbox and search bar
    this.add(this.getQxObject("toolbar"));
    this.__helpButton.addListener("execute", () => this.getApplication().showHelpWindow("plugin/z3950/search"));
    // selectbox with list of datasources
    this.__selectBox.bind("selection[0].label", this.__selectBox, "toolTipText");
    this.__selectBox.bind("selection[0].value", this, "datasource");
    
    // store for selectbox
    let store = new qcl.data.store.JsonRpcStore("z3950.table");
    store.setModel(qx.data.marshal.Json.createModel([]));
    store.bind("model", this.getQxObject("selectbox"), "model");
    store.addListener("loaded", () => {
      let lastDatasource = this.getApplication().getConfigManager().getKey("modules.z3950.lastDatasource");
      if (lastDatasource) {
        this.setDatasource(lastDatasource);
      }
    });
    
    // (re-)load datasources on message
    qx.event.message.Bus.getInstance().subscribe("plugins.z3950.reloadDatasources",  () => store.load("server-list"));
    
    // searchbox
    this.__searchBox.addListener("keypress", this._on_keypress, this);
    
    
    // create and configure listview
    let listview = this.getQxObject("listview");
    this.add(listview, {flex: 1});
    listview.set({
      modelType: "record",
      serviceName: "z3950.table"
    });
    // populate the list when the data is ready
    qx.event.message.Bus.getInstance().subscribe("z3950.dataReady", e => {
      listview.setQuery(null);
      listview.setQuery(e.getData());
    });
    // when table is ready, configure buttons
    qx.lang.Function.delay(() => {
      this.__listView.addListenerOnce("tableReady", () => {
        let controller = this.__listView.getController();
        let enableButtons = () => {
          this.__importButton.setEnabled(true);
          this.__searchButton.setEnabled(true);
          listview.setEnabled(true);
          this.hidePopup();
        };
        controller.addListener("blockLoaded", enableButtons);
        controller.addListener("statusMessage", e => {
          this.showPopup(e.getData());
          qx.lang.Function.delay(enableButtons, 1000, this);
        });
      });
    }, 100);

    // create footer with status and import/close buttons
    this.add(this.getQxObject("footer"));
    this.__importButton.addListener("execute", () => this.importSelected());
    this.__closeButton.addListener("execute", () => this.close());
    this.__listView.bind("store.model.statusText", this.__status, "value");
    
    // close on logout
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, () => this.close());
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
          this.__toolbar = control;
          break;
        case "selectbox":
          control = new qx.ui.form.VirtualSelectBox();
          control.set({
            labelPath: "label",
            width: 300,
            maxHeight: 25
          });
          this.__selectBox = control;
          break;
        case "search-bar":
          control = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
          control.setPadding(4);
          control.add(this.getQxObject("search-box"), {flex: 1});
          control.add(this.getQxObject("search-button"));
          control.add(this.getQxObject("help-button"));
          this.__searchBar = control;
          break;
        case "search-box":
          control = new qx.ui.form.TextField();
          control.set({
            padding: 2,
            height: 26,
            placeholder: this.tr("Enter search terms"),
          });
          control.addListener("dblclick", e => e.stopPropagation());
          this.__searchBox = control;
          break;
        case "search-button":
          control = new qx.ui.form.Button(this.tr("Search"));
          control.addListener("execute", () => this.startSearch());
          this.__searchButton = control;
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
          this.__closeButton = control;
          break;
      }
      // if (control) {
      //   this.addOwnedQxObject(control, id);
      // }
      return control || this.base(arguments, id);
    },
  
    _applyDatasource : function(value, old) {
      if (value) {
        let sb = this.getQxObject("selectbox");
        sb.getModel().forEach(item => {
          if (item.getValue() === value) {
            sb.getSelection().setItem(0, item);
          }
        });
        this.getApplication().getConfigManager().setKey("modules.z3950.lastDatasource", value);
      }
      this.info("Z39.50 datasource is now: " + value);
    },

    /**
     * Starts the search
     */
    startSearch: function () {
      let datasource = this.__selectBox.getSelection().getItem(0).getValue();
      let query = this.normalizeForSearch(this.__searchBox.getValue());

      // update the UI
      this.__listView.setDatasource(datasource);
      this.__listView.clearTable();
      //this.listView.setEnabled(false);
      //this.importButton.setEnabled(false);
      //this.searchButton.setEnabled(false);
      
      // open the ServerProgress widget and initiate the remote search
      let p = qx.core.Id.getQxObject("plugins-z3950-progress");
      p.setMessage(this.tr("Searching..."));
      p.start({ datasource, query });
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
      let sourceDatasource = this.getQxObject("selectbox").getSelection().toArray()[0].getValue();
      let targetDatasource = app.getDatasource();
      this.showPopup(this.tr("Importing references..."));
      let client = this.getApplication().getRpcClient("z3950.table");
      await client.request("import", [sourceDatasource, ids, targetDatasource, targetFolderId]);
      this.__importButton.setEnabled(true);
      this.hidePopup();
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
    
    markForTranslation: function () {
      this.tr("Import from library catalog");
    },
    
    /**
     * from https://github.com/ikr/normalize-for-search/blob/master/src/normalize.js
     * MIT licence
     * @param s {String}
     * @return {String}
     */
    normalizeForSearch: function (s) {
      // ES6: @todo
      //let combining = /[\u0300-\u036F]/g;
      // return s.normalize('NFKD').replace(combining, ''));
      
      /**
       * @param c
       */
      function filter(c) {
        switch (c) {
          case "ä":
            return "ae";
          
          case "å":
            return "aa";
          
          case "á":
          case "à":
          case "ã":
          case "â":
            return "a";
          
          case "ç":
          case "č":
            return "c";
          
          case "é":
          case "ê":
          case "è":
            return "e";
          
          case "ï":
          case "í":
            return "i";
          
          case "ö":
            return "oe";
          
          case "ó":
          case "õ":
          case "ô":
            return "o";
          
          case "ś":
          case "š":
            return "s";
          
          case "ü":
            return "ue";
          
          case "ú":
            return "u";
          
          case "ß":
            return "ss";
          
          case "ё":
            return "е";
          
          default:
            return c;
        }
      }
      
      let normalized = "";
      let i;
      let l;
      s = s.toLowerCase();
      for (i = 0, l = s.length; i < l; i += 1) {
        normalized += filter(s.charAt(i));
      }
      return normalized;
    }
  }
});
