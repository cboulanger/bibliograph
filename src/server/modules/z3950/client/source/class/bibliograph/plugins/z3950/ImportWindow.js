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

    this.setWidth(700);
    this.setCaption(this.tr("Import from library catalog"));
    this.setShowMinimize(false);
    this.setVisibility("excluded");
    this.setHeight(500);
    this.addListener("appear", () => this.center());

    this.createUi();
    this.createPopup();
    qx.event.message.Bus
    .getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, () => this.close());

    qx.lang.Function.delay(() => {
      this.listView.addListenerOnce("tableReady", () => {
        let controller = this.listView.getController();
        let enableButtons = () => {
          this.importButton.setEnabled(true);
          this.searchButton.setEnabled(true);
          this.listView.setEnabled(true);
          this.hidePopup();
        };
        controller.addListener("blockLoaded", enableButtons);
        controller.addListener("statusMessage", e => {
          this.showPopup(e.getData());
          qx.lang.Function.delay(enableButtons, 1000, this);
        });
      });
    }, 100);
  },
  
  members:
  {
    listView: null,
    datasourceSelectBox: null,
    searchBox: null,
    searchButton: null,
    statusTextLabel: null,

    _applyDatasource : function(value, old) {
      if (value) {
        this.datasourceSelectBox.getModel().forEach(item => {
          if (item.getValue() === value) {
            this.datasourceSelectBox.getSelection().setItem(0, item);
          }
        });
        this.getApplication().getConfigManager().setKey("modules.z3950.lastDatasource", value);
      }
      this.info("Z39.50 datasource is now: " + value);
    },

    /**
     * UI
     */
    createUi: function() {
      this.setLayout(new qx.ui.layout.VBox(5));

      // toolbar
      let toolBar1 = new qx.ui.toolbar.ToolBar();
      this.add(toolBar1);

      // datasource select box
      let selectBox = new qx.ui.form.VirtualSelectBox();
      selectBox.setLabelPath("label");
      this.datasourceSelectBox = selectBox;
      selectBox.setWidth(300);
      selectBox.setMaxHeight(25);
      toolBar1.add(selectBox);

      // bindings
      selectBox.bind("selection[0].label", selectBox, "toolTipText");
      selectBox.bind("selection[0].value", this, "datasource");

      // store
      let store = new qcl.data.store.JsonRpcStore("z3950/table");
      let model = qx.data.marshal.Json.createModel([]);
      store.setModel(model);
      store.bind("model", selectBox, "model");
      store.addListener("loaded", () => {
        let lastDatasource = this.getApplication().getConfigManager().getKey("modules.z3950.lastDatasource");
        if (lastDatasource) {
          this.setDatasource(lastDatasource);
        }
      });
      this.addListener("appear", () => {
        qx.event.message.Bus.dispatchByName("plugins.z3950.reloadDatasources");
      });

      // (re-)load datasources
      qx.event.message.Bus.getInstance().subscribe("plugins.z3950.reloadDatasources", function (e) {
        store.load("server-list");
      }, this);

      toolBar1.addSpacer();

      // searchbox
      let hbox1 = new qx.ui.layout.HBox(null, null, null);
      hbox1.setSpacing(5);
      let composite1 = new qx.ui.container.Composite();
      composite1.setLayout(hbox1);
      composite1.setPadding(4);
      toolBar1.add(composite1, {flex: 1});
      let searchBox = new qx.ui.form.TextField(null);
      this.searchBox = searchBox;
      searchBox.setPadding(2);
      searchBox.setPlaceholder(this.tr("Enter search terms"));
      searchBox.setHeight(26);
      composite1.add(searchBox, {flex: 1});
      searchBox.addListener("keypress", this._on_keypress, this);
      searchBox.addListener("dblclick", e => e.stopPropagation());
      // search button
      this.searchButton = new qx.ui.form.Button(this.tr("Search"));
      this.searchButton.addListener("execute", e => this.startSearch());
      composite1.add(this.searchButton);

      // help button
      let helpButton = new qx.ui.toolbar.Button(this.tr("Help"));
      composite1.add(helpButton);
      helpButton.addListener("execute", e => this.getApplication().showHelpWindow("plugin/z3950/search"));

      // table view
      let tableview = new qcl.ui.table.TableView();
      this.listView = tableview;
      tableview.setDecorator("main"); //??
      tableview.setModelType("record");
      tableview.setServiceName("z3950/table");
      tableview.headerBar.setVisibility("excluded");
      tableview.menuBar.setVisibility("excluded");
      this.add(tableview, {flex: 1});

      // populate the list when the data is ready
      qx.event.message.Bus.getInstance().subscribe("z3950.dataReady", e => {
        tableview.setQuery(null);
        tableview.setQuery(e.getData());
      });

      // footer
      let hbox2 = new qx.ui.layout.HBox(5, null, null);
      let composite2 = new qx.ui.container.Composite();
      composite2.setLayout(hbox2);
      this.add(composite2);
      hbox2.setSpacing(5);

      // status label
      this.statusTextLabel = new qx.ui.basic.Label(null);
      this.statusTextLabel.setTextColor("#808080");
      composite2.add(this.statusTextLabel);
      this.listView.bind("store.model.statusText", this.statusTextLabel, "value");

      // spacer
      let spacer2 = new qx.ui.core.Spacer(null, null);
      composite2.add(spacer2, {
        flex: 10
      });

      // import button
      let importButton = new qx.ui.form.Button(this.tr("Import selected records"));
      this.importButton = importButton;
      importButton.setEnabled(false);
      composite2.add(importButton);
      importButton.addListener("execute", e => this.importSelected());

      // close button
      let button1 = new qx.ui.form.Button(this.tr("Close"));
      composite2.add(button1);
      button1.addListener("execute", e => this.close());
    },
    

    /**
     * Starts the search
     */
    startSearch: function () {
      let datasource = this.datasourceSelectBox.getSelection().getItem(0).getValue();
      let query = this.normalizeForSearch(this.searchBox.getValue());

      // update the UI
      let lv = this.listView;
      lv.setDatasource(datasource);
      lv.clearTable();
      //lv.setEnabled(false);
      //this.importButton.setEnabled(false);
      //this.searchButton.setEnabled(false);
      
      // open the ServerProgress widget and initiate the remote search
      let p = this.getApplication().getWidgetById("plugins/z3950/searchProgress");
      p.setMessage(this.tr("Searching..."));
      p.start({ datasource, query });
    },
    
    
    /**
     * Imports the selected references
     */
    importSelected: async function () {
      let app = this.getApplication();
      
      // ids to import
      let ids = this.listView.getSelectedIds();
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
      let sourceDatasource = this.datasourceSelectBox.getSelection().toArray()[0].getValue();
      let targetDatasource = app.getDatasource();
      this.showPopup(this.tr("Importing references..."));
      let client = this.getApplication().getRpcClient("z3950/table");
      await client.request("import", [sourceDatasource, ids, targetDatasource, targetFolderId]);
      this.importButton.setEnabled(true);
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
