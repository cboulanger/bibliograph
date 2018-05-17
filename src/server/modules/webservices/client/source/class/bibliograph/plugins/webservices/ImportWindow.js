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

/*global qx qcl dialog*/

qx.Class.define("bibliograph.plugins.webservices.ImportWindow",
{
  extend: qx.ui.window.Window,
  include: [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties : {
  
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
    this.set({
      width:700,
      height:500,
      caption:this.tr('Import from webservices'),
      showMinimize:false,
      visibility:"excluded"
    });
    this.addListener("appear", ()=>this.center() );
    this.createUi();
    this.setupScanner();
    this.createPopup();
    qx.event.message.Bus
      .getInstance()
        .subscribe(bibliograph.AccessManager.messages.LOGOUT, ()=>this.close() );

    qx.lang.Function.delay(() => {
      this.listView.addListenerOnce("tableReady", () => {
        let controller = this.listView.getController();
        let enableButtons = () => {
          this.importButton.setEnabled(true);
          this.searchButton.setEnabled(true);
          this.listView.setEnabled(true);
          this.hidePopup();
          if( this.getAutoimport() && this.getSearch() ){
           this.listView
             .getTable()
             .getSelectionManager()
             .getSelectionModel()
             .setSelectionInterval(0,0);
           this.importSelected();
           this.setSearch(null);
           this.searchBox.focus();
          }
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
    scannerButton : null,
    statusTextLabel: null,

    _applyDatasource : function(value, old)
    {
      if (value) {
        this.datasourceSelectBox.getModel().forEach((item) => {
          if (item.getValue() === value) {
            this.datasourceSelectBox.getSelection().setItem(0,item);
          }
        });
        this.getApplication().getConfigManager().setKey("modules.webservices.lastDatasource", value);
      }
      this.info("Webservices datasource is now: " + value);
    },

    /**
     * UI
     */
    createUi: function()
    {
      let app = this.getApplication();
      this.setLayout(new qx.ui.layout.VBox(5));

      // toolbar
      let toolBar1 = new qx.ui.toolbar.ToolBar();
      toolBar1.set({
        spacing : 5,
        
      })
      this.add(toolBar1);

      // datasource select box
      let selectBox = new qx.ui.form.VirtualSelectBox();
      selectBox.setLabelPath("label");
      this.datasourceSelectBox = selectBox;
      //selectBox.setWidth(300);
      //selectBox.setMaxHeight(25);
      toolBar1.add(selectBox, {flex:1});
      selectBox.bind("selection[0].label", selectBox, "toolTipText");
      selectBox.bind("selection[0].value", this, "datasource");
      let store = new qcl.data.store.JsonRpcStore("webservices/table");
      let model = qx.data.marshal.Json.createModel([]);
      store.setModel(model);
      store.bind("model", selectBox, "model");
      store.addListener("loaded", ()=>{
        let lastDatasource = this.getApplication()
          .getConfigManager()
          .getKey("modules.webservices.lastDatasource");
        if (lastDatasource) {
          this.setDatasource(lastDatasource);
        }
      });
      this.addListener("appear", ()=>{
        qx.event.message.Bus.dispatchByName("plugins.webservices.reloadDatasources");
      });
      qx.event.message.Bus.getInstance().subscribe("plugins.webservices.reloadDatasources", function (e) {
        store.load("server-list");
      }, this);

      // auto-import
      let autoimport = new qx.ui.form.CheckBox( this.tr("Auto-import best result") );
      autoimport.bind("value",this,"autoimport");
      this.bind("autoimport",autoimport,"value");
      toolBar1.add(autoimport);
      
      // search widgets container
      let composite1 = new qx.ui.container.Composite();
      composite1.setLayout(new qx.ui.layout.HBox(5));
      composite1.setPadding(4);
      toolBar1.add(composite1, {flex: 1});
      
      // searchbox
      let searchBox = new qx.ui.form.TextField();
      this.searchBox = searchBox;
      searchBox.setPadding(2);
      searchBox.setPlaceholder(this.tr('Enter search terms'));
      searchBox.setHeight(26);
      composite1.add(searchBox, {flex: 1});
      searchBox.addListener("input", e => this.setSearch(e.getData()));
      searchBox.addListener("changeValue", e => this.setSearch(e.getData()));
      this.bind("search", searchBox, "value");
      searchBox.addListener("keypress", this._on_keypress, this);
      searchBox.addListener("dblclick", e => e.stopPropagation() );
      
      // search button
      this.searchButton = new qx.ui.form.Button(this.tr('Search'));
      this.searchButton.addListener("execute", e => this.startSearch() );
      composite1.add(this.searchButton);
      
      // scanner button
      this.scannerButton = new qx.ui.form.Button("|||");
      composite1.add(this.scannerButton);
      
      // help button
      let helpButton = new qx.ui.toolbar.Button(this.tr('Help'));
      composite1.add(helpButton);
      helpButton.addListener("execute", e => this.getApplication().showHelpWindow("plugin/webservices/search") );

      // table view
      let tableview = new qcl.ui.table.TableView();
      this.listView = tableview;
      tableview.setDecorator("main"); //??
      tableview.setModelType("record");
      tableview.setServiceName("webservices/table");
      tableview.headerBar.setVisibility("excluded");
      tableview.menuBar.setVisibility("excluded");
      this.add(tableview, {flex: 1});

      // populate the list when the data is ready
      qx.event.message.Bus.getInstance().subscribe("webservices.dataReady", e => {
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
      let importButton = new qx.ui.form.Button(this.tr('Import selected records'));
      this.importButton = importButton;
      importButton.setEnabled(false);
      composite2.add(importButton);
      importButton.addListener("execute", e => this.importSelected() );

      // close button
      let button1 = new qx.ui.form.Button(this.tr('Close'));
      composite2.add(button1);
      button1.addListener("execute", e => this.close() );
    },
  
    /**
     * Sets up the built-in barcode scanner
     */
    setupScanner : function()
    {
      let scanner = new bibliograph.plugins.webservices.Scanner(this.scannerButton);
      scanner.addListener('changeResult', e => {
        let result = e.getData();
        if( result && result.length === 13 ){
          this.searchBox.setValue(result);
          this.startSearch();
        }
      });
    },
    

    /**
     * Starts the search
     */
    startSearch: function () {
      let datasource = this.datasourceSelectBox.getSelection().getItem(0).getValue();
      let query = this.searchBox.getValue();

      // update the UI
      let lv = this.listView;
      lv.setDatasource(datasource);
      lv.clearTable();
      //lv.setEnabled(false);
      //this.importButton.setEnabled(false);
      //this.searchButton.setEnabled(false);
      
      // open the ServerProgress widget and initiate the remote search
      let p = this.getApplication().getWidgetById("plugins/webservices/searchProgress");
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
      let treeView = app.getWidgetById(bibliograph.Application.ids.app.treeview);
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
        .getRpcClient("webservices/table")
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
      if (e.getKeyIdentifier() === "Enter") {
        this.startSearch();
      }
      if( false && this.getAutoimport() ){
        let searchText = this.getSearch();
        // auto-submit ISBNs
        if( searchText && searchText.length > 12 && searchText.replace(/[^0-9xX]/g,'').length === 13 && searchText.substr(0,3) === "978" ){
          this.startSearch();
        }
      }
    }
   
  }
});
