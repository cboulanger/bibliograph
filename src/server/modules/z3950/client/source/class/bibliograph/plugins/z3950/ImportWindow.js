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

/*global qx qcl z3950 dialog*/

/**
 * Z39.50 Plugin: Application logic
 * @property {bibliograph.ConfigManager} configManager
 */
qx.Class.define("bibliograph.plugins.z3950.ImportWindow",
{
  extend: qx.ui.window.Window,
  include: [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties : {

    datasource : {
      check : "String",
      nullable : true,
      event : "changeDatasource",
      apply : "_applyDatasource"
    }
  },

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  /**
   * Constructor
   */
  construct: function () {
    this.base(arguments);
    this.createUI();
    this.createPopup();

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
        controller.addListener("statusMessage", (e) => {
          this.showPopup(e.getData());
          qx.lang.Function.delay(enableButtons, 1000, this);
        });
      });
    }, 100);
  },


  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  members:
  {
    listView: null,
    datasourceSelectBox: null,
    searchBox: null,
    searchButton: null,
    statusTextLabel: null,

    _applyDatasource : function(value, old)
    {
      if (value) {
        this.datasourceSelectBox.getModel().forEach((item) => {
          if (item.getValue() === value) {
            this.datasourceSelectBox.getSelection().setItem(0,item);
          }
        });
        this.getApplication().getConfigManager().setKey("modules.z3950.lastDatasource", value);
      }
      this.info("Z39.50 datasource is now: " + value);
    },

    createUI: function()
    {
      let app = this.getApplication();

      // window
      let importWindow = this;
      importWindow.setWidth(700);
      importWindow.setCaption(this.tr('Import from library catalog'));
      importWindow.setShowMinimize(false);
      importWindow.setVisibility("excluded");
      importWindow.setHeight(500);

      // events
      qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.LOGOUT, ()=>importWindow.close() );
      importWindow.addListener("appear", ()=>importWindow.center() );

      // layout
      let qxVbox1 = new qx.ui.layout.VBox(5, null, null);
      qxVbox1.setSpacing(5);
      importWindow.setLayout(qxVbox1);

      // toolbar
      let qxToolBar1 = new qx.ui.toolbar.ToolBar();
      importWindow.add(qxToolBar1);

      // datasource select box
      let selectBox = new qx.ui.form.VirtualSelectBox();
      selectBox.setLabelPath("label");
      this.datasourceSelectBox = selectBox;
      selectBox.setWidth(300);
      selectBox.setMaxHeight(25);
      qxToolBar1.add(selectBox);

      // bindings
      selectBox.bind("selection[0].label", selectBox, "toolTipText");
      selectBox.bind("selection[0].value", this, "datasource");

      // store
      let store = new qcl.data.store.JsonRpcStore("z3950/table");
      let model = qx.data.marshal.Json.createModel([]);
      store.setModel(model);
      store.bind("model", selectBox, "model");
      store.addListener("loaded", ()=>{
        let lastDatasource = this.getApplication().getConfigManager().getKey("modules.z3950.lastDatasource");
        if (lastDatasource) {
          this.setDatasource(lastDatasource);
        }
      });
      this.addListener("appear", ()=>{
        qx.event.message.Bus.dispatchByName("plugins.z3950.reloadDatasources");
      });

      // (re-)load datasources
      qx.event.message.Bus.getInstance().subscribe("plugins.z3950.reloadDatasources", function (e) {
        store.load("server-list");
      }, this);

      qxToolBar1.addSpacer();

      // searchbox
      let qxHbox1 = new qx.ui.layout.HBox(null, null, null);
      qxHbox1.setSpacing(5);
      let qxComposite1 = new qx.ui.container.Composite();
      qxComposite1.setLayout(qxHbox1)
      qxComposite1.setPadding(4);
      qxToolBar1.add(qxComposite1, {flex: 1});
      let searchBox = new qx.ui.form.TextField(null);
      this.searchBox = searchBox;
      searchBox.setPadding(2);
      searchBox.setPlaceholder(this.tr('Enter search terms'));
      searchBox.setHeight(26);
      qxComposite1.add(searchBox, {flex: 1});
      searchBox.addListener("keypress", this._on_keypress, this);
      searchBox.addListener("dblclick", e => e.stopPropagation() );
      // search button
      this.searchButton = new qx.ui.form.Button(this.tr('Search'));
      this.searchButton.addListener("execute", e => this.startSearch() );
      qxComposite1.add(this.searchButton);

      // help button
      let helpButton = new qx.ui.toolbar.Button(this.tr('Help'));
      qxComposite1.add(helpButton);
      helpButton.addListener("execute", e => this.getApplication().showHelpWindow("plugin/z3950/search") );

      // listview
      let listView = new bibliograph.ui.reference.ListView();
      this.listView = listView;
      listView.setDecorator("main"); //??
      listView.setModelType("record");
      listView.setServiceName("z3950/table");
      importWindow.add(listView, {flex: 1});

      // populate the list when the data is ready
      qx.event.message.Bus.getInstance().subscribe("z3950.dataReady", e => {
        listView.setQuery(null);
        listView.setQuery(e.getData());
      });

      // footer
      let qxHbox2 = new qx.ui.layout.HBox(5, null, null);
      let qxComposite2 = new qx.ui.container.Composite();
      qxComposite2.setLayout(qxHbox2);
      importWindow.add(qxComposite2);
      qxHbox2.setSpacing(5);

      // status label
      this.statusTextLabel = new qx.ui.basic.Label(null);
      this.statusTextLabel.setTextColor("#808080");
      qxComposite2.add(this.statusTextLabel);
      this.listView.bind("store.model.statusText", this.statusTextLabel, "value");

      // spacer
      let qxSpacer2 = new qx.ui.core.Spacer(null, null);
      qxComposite2.add(qxSpacer2, {
        flex: 10
      });

      // import button
      let importButton = new qx.ui.form.Button(this.tr('Import selected records'));
      this.importButton = importButton;
      importButton.setEnabled(false);
      qxComposite2.add(importButton);
      importButton.addListener("execute", e => this.importSelected() );

      // close button
      let qxButton1 = new qx.ui.form.Button(this.tr('Close'));
      qxComposite2.add(qxButton1);
      qxButton1.addListener("execute", e => this.close() );
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
      this.importButton.setEnabled(false);
      //this.searchButton.setEnabled(false);
      
      // open the ServerProgress widget and initiate the remote search
      let p = this.getApplication().getWidgetById("plugins/z3950/searchProgress");
      p.setMessage(this.tr("Searching..."));
      p.start({ datasource, query });
    },
    
    
    /**
     * Imports the selected references
     */
    importSelected: function () {
      let app = this.getApplication();
      
      // ids to import
      let ids = this.listView.getSelectedIds();
      if (!ids.length) {
        dialog.Dialog.alert(this.tr("You have to select one or more reference to import."));
        return false;
      }
      
      // target folder
      let targetFolderId = app.getFolderId();
      if (!targetFolderId) {
        dialog.Dialog.alert(this.tr("Please select a folder first."));
        return false;
      }
      let treeView = app.getWidgetById("bibliograph/mainFolderTree");
      let nodeId = treeView.getController().getClientNodeId(targetFolderId);
      let node = treeView.getTree().getDataModel().getData()[nodeId];
      if (!node) {
        dialog.Dialog.alert(this.tr("Cannot determine selected folder. Please reload the folders."));
        return false;
      }
      if (node.data.type !== "folder") {
        dialog.Dialog.alert(this.tr("Invalid target folder. You can only import into normal folders."));
        return false;
      }
      
      // send to server
      let sourceDatasource = this.datasourceSelectBox.getSelection().toArray()[0].getValue();
      let targetDatasource = app.getDatasource();
      this.showPopup(this.tr("Importing references..."));
      this.getApplication()
        .getRpcClient("z3950/table")
        .send("import", [sourceDatasource, ids, targetDatasource, targetFolderId])
        .then(()=>{
          this.importButton.setEnabled(true);
          this.hidePopup();
        });
    },


    /**
     * Called when the user presses a key in the search box
     * @param e {qx.event.type.Data}
     */
    _on_keypress: function (e) {
      if (e.getKeyIdentifier() == "Enter") {
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
      
      function filter(c) {
        switch (c) {
          case 'ä':
            return 'ae';
          
          case 'å':
            return 'aa';
          
          case 'á':
          case 'à':
          case 'ã':
          case 'â':
            return 'a';
          
          case 'ç':
          case 'č':
            return 'c';
          
          case 'é':
          case 'ê':
          case 'è':
            return 'e';
          
          case 'ï':
          case 'í':
            return 'i';
          
          case 'ö':
            return 'oe';
          
          case 'ó':
          case 'õ':
          case 'ô':
            return 'o';
          
          case 'ś':
          case 'š':
            return 's';
          
          case 'ü':
            return 'ue';
          
          case 'ú':
            return 'u';
          
          case 'ß':
            return 'ss';
          
          case 'ё':
            return 'е';
          
          default:
            return c;
        }
      }
      
      let normalized = '', i, l;
      s = s.toLowerCase();
      for (i = 0, l = s.length; i < l; i = i + 1) {
        normalized = normalized + filter(s.charAt(i));
      }
      return normalized;
    }
  }
});
