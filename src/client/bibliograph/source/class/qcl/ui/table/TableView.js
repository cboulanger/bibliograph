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
/*global qx qcl dialog bibliograph virtualdata*/

/**
 * Base class for Table widgets
 * @require(qx.ui.table.cellrenderer.String)
 * @require(qx.ui.table.celleditor.TextField)
 */
qx.Class.define("qcl.ui.table.TableView",
{
  extend: qx.ui.container.Composite,

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties:
  {
    /**
     * the datasource of the current tables
     */
    datasource:
    {
      check: "String",
      nullable: true,
      event: "changeDatasource",
      apply: "_applyDatasource"
    },
    
    /**
     * the id of the folder that might affect loaded tables
     */
    folderId:
    {
      check: "Integer",
      nullable: true,
      event: "changeFolderId",
      apply: "_applyFolderId"
    },
    
    /**
     * the current query executed in all the tables
     */
    query:
    {
      check: "String",
      nullable: true,
      event: "changeQuery",
      apply: "_applyQuery"
    },
    
    /**
     * The type of the currently selected model record, if any
     */
    modelType:
    {
      check: "String",
      nullable: true,
      event: "changeModelType"
    },
    
    /**
     * The id of the currently selected model record, if any
     */
    modelId:
    {
      check: "Integer",
      nullable: true,
      event: "changeModelId",
      apply: "_applyModelId"
    },
    
    /**
     * The ids of the currently selected rows
     */
    selectedIds:
    {
      check: "Array",
      nullable: true,
      event: "changeSelectedIds",
      apply: "_applySelectedIds"
    },
    
    /**
     * The data of the currently selected rows
     */
    selectedRowData:
    {
      check: "Array",
      nullable: true,
      event: "changeSelectedRowData"
    },
    
    /**
     * The table widget
     */
    table:
    {
      check: "qx.ui.table.Table",
      nullable: true,
      event: "changeTable"
    },


    /**
     * An array of column ids
     */
    columnIds:
    {
      check: "Array",
      nullable: true
    },
    
    /*
     * The cotainer for the table widget
     */
    tableContainer:
    {
      check: "qx.ui.core.Widget",
      nullable: true
    },
    
    /**
     * The contrller of the table
     */
    controller:
    {
      check: "qx.core.Object",
      nullable: true,
      event: "changeController"
    },
    
    /**
     * The table data marshaler
     */
    marshaler:
    {
      check: "qx.core.Object",
      nullable: true,
      event: "changeMarshaler"
    },
    
    /**
     * The table data store
     */
    store:
    {
      check: "qx.core.Object",
      nullable: true,
      event: "changeStore"
    },
    
    /**
     * The name of the service that suppies the table data
     */
    serviceName:
    {
      check: "String",
      nullable: true
    },
    
    /**
     * The data object containing the query data
     */
    queryData:
    {
      check: "Object",
      nullable: true
    },
  
  
    /**
     * Enable/disable drag and drop
     */
    enableDragDrop:
    {
      check: "Boolean",
      apply: "_applyEnableDragDrop",
      event: "changeEnableDragDrop",
      init: false
    },
  
    /**
     * Whether the drag session should output verbose debug messages.
     * Useful for development
     */
    debugDragSession :
    {
      check: "Boolean",
      init: false,
      event: "changeDebugDragSession"
    },
  
    /**
     * Array of drop target types (String[])
     */
    allowDropTargetTypes:
    {
      check: "Array"
    },
  
    /**
     * Drag actions
     */
    dragActions:
    {
      check: "Array"
    }
  },
  
  /*
  *****************************************************************************
      EVENTS
  *****************************************************************************
   */
  events: {
    "tableReady": "qx.event.type.Data"
  },
  
  /*
  *****************************************************************************
      CONSTRUCTOR
  *****************************************************************************
   */
  construct: function () {
    this.base(arguments);
    this.set({
      selectedRowData : [],
      selectedIds: [],
      columnIds: [],
      allowDropTargetTypes: [],
      dragActions : ['move','copy']
    });
    this.__tableModelTypes = {};
    this.createUi();
  },

  /*
  *****************************************************************************
      STATICS
  *****************************************************************************
   */
  statics: {
    types : {
       ROWDATA : "qcl/table-row-data"
    }
  },
  
  /*
  *****************************************************************************
      MEMBERS
  *****************************************************************************
   */
  members:
  {

    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */
    __tableReady: false,
    __tableModelTypes: null,
    __loadingTableStructure: null,
    __selectedIds: null,
    __ignoreChangeSelection: false,
    
    /*
    ---------------------------------------------------------------------------
       CHILD WIDGETS
    ---------------------------------------------------------------------------
    */
    
    headerBar: null,
    menuBar: null,
    referenceViewLabel: null,
    _statusLabel: null,
    
    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */
    
    /**
     * Applies the datasource
     * @param value {String}
     * @param old {String}
     */
    _applyDatasource: function (value, old) {
      // hide old table
      if (old && this.getTable()) {
        // clear label
        if (this.referenceViewLabel) {
          this.referenceViewLabel.setValue("");
        }
        
        // clear table and mark as not ready so that the next loading request checks for table changes
        this.clearTable();
        this.getTable().setVisibility("hidden");
        this.__tableReady = false;
      }
    },
    
    /**
     * Applies the id of the folder that is displayed in the table
     * @param folderId {Integer}
     * @param old {Integer}
     */
    _applyFolderId: function (folderId, old) {
      
      if (!folderId) {
        this.clearTable();
        return;
      }
      
      /*
       * clear query
       */
      this.setQuery(null);
      
      /*
       * use a small timeout to avoid rapid reloads
       */
      qx.util.TimerManager.getInstance().start( () => {
        
        // if the folder id has already changed, do not load
        if (folderId != this.getFolderId()) {
          return;
        }
        
        // show breadcrumb
        let mainFolderTree = this.getApplication().getWidgetById("app/treeview");
        let selectedNode = mainFolderTree.getSelectedNode();
        
        if (selectedNode && selectedNode.data.id == folderId) {
          // we can get the folder hierarchy for the breadcrumb
          let hierarchy = mainFolderTree.getTree().getHierarchy(selectedNode);
          hierarchy.unshift(this.getApplication().getDatasourceLabel());
          let breadcrumb = hierarchy.join(" > ");
          
          // is it a query?
          let query = selectedNode.data.query;
          if (query) {
            // append query to breadcrumb
            //breadcrumb += " (" + query + ")";
            this.setQuery(query);
            this.setFolderId(null);
          }
          
          if (this.referenceViewLabel) {
            this.referenceViewLabel.setValue(breadcrumb);
          }
        }
        
        // load folder
        this.load();
      }, null, this, null, 500);
    },
    
    /**
     * Reloads the folder when the search query has changed
     */
    _applyQuery: function (query, old) {
      if (!query) return;
      
      // clear folder Id
      this.setFolderId(null);
      
      // show breadcrumb and load
      let breadcrumb = this.tr("Query") + ": " + query;
      if (this.referenceViewLabel) {
        this.referenceViewLabel.setValue(breadcrumb);
      }
      this.load();
    },
    
    /**
     * Reacts to a change in the "selectedIds" state of the applicationby selecting
     * the values. does nothing at the moment, since async selection with the remote
     * table model is very tricky.
     */
    _applySelectedIds: function (value, old) {
      
      //
    },
    
    /**
     * Reacts to a change in the "modelId" state of the application by selecting the row
     * that corresponds to the id.
     */
    _applyModelId: function (value, old, counter) {
      if (counter === "modelId") {
        counter = 0;
      }
      
      //console.log("Model id changed to " + value);
      if (value && value === this.getModelId()) {
        //console.log("Trying to select id " + value + ", attempt " + counter);
        if ((!this.isTableReady() || this._selectIds([value]) === false) && counter < 10) {
          qx.lang.Function.delay(this._applyModelId, 1000, this, value, old, ++counter);
        }
      }
    },
  
    /**
     * Enables or disables drag and drop by adding or removing event listeners
     * from the *current* table
     * @param value {Boolean}
     * @param old {Boolean}
     * @private
     */
    _applyEnableDragDrop: function (value, old) {
      
      let table = this.getTable();
      
      // if we don't have a table yet, wait until we have one
      if( !table ){
        if( value ){
          this.dragDebug("Deferring drag & drop initialization");
          this.addListenerOnce("changeTable", ()=> this._applyEnableDragDrop(value, old) );
        }
        return;
      }
      
      if (old && !value) {
        table.setDraggable(false);
        table.setDroppable(false);
        table.removeListener("dragstart",    this._onDragStart,   this);
        table.removeListener("drag",         this._onDragHandler, this);
        table.removeListener("dragover",     this._onDragOver,    this);
        table.removeListener("dragend",      this._onDragEnd,     this);
        table.removeListener("dragleave",    this._onDragEnd,     this);
        table.removeListener("dragchange",   this._onDragChange,  this);
        table.removeListener("drop",         this._onDrop,        this);
        table.removeListener("droprequest",  this._onDropRequest, this);
        table.info("Drag & Drop disabled.");
      }
    
      if (value && !old) {
        table.addListener("dragstart",   this._onDragStart,   this);
        table.addListener("dragover",    this._onDragOver,    this); // dragover handler must be called *before* drag handler
        table.addListener("drag",        this._onDragHandler, this);
        table.addListener("dragleave",   this._onDragEnd,     this);
        table.addListener("dragend",     this._onDragEnd,     this);
        table.addListener("dragchange",  this._onDragChange,  this);
        table.addListener("drop",        this._onDrop,        this);
        table.addListener("droprequest", this._onDropRequest, this);
        table.setDraggable(true);
        table.setDroppable(true);
        table.info("Drag & Drop enabled.");
      }
    },

    /*
    ---------------------------------------------------------------------------
      CREATE UI
    ---------------------------------------------------------------------------
    */

    /**
     * @todo rewrite using child controls
     */
    createUi: function(){
      this.setLayout(new qx.ui.layout.VBox());

      // Top menu bar
      let headerBar = new qx.ui.menubar.MenuBar();
      this.headerBar = headerBar;
      headerBar.setHeight(22);
      this.add(headerBar);
      let referenceViewLabel = new qx.ui.basic.Label(null);
      this.referenceViewLabel = referenceViewLabel;
      referenceViewLabel.setPadding(3);
      referenceViewLabel.setRich(true);
      headerBar.add(referenceViewLabel);

      // Table container, table will be inserted here
      let tableContainer = new qx.ui.container.Stack();
      this.add(tableContainer, {flex: 1});
      this.setTableContainer(tableContainer);

      // Footer Menu bar
      let menuBar = new qx.ui.menubar.MenuBar();
      this.menuBar = menuBar;
      menuBar.setHeight(18);
      this.add(menuBar);

      // Status label
      let statusLabel = new qx.ui.basic.Label();
      this._statusLabel = statusLabel;
      statusLabel.setTextColor("#808080");
      statusLabel.setMargin(5);
      menuBar.add(statusLabel);
      this.bind("store.model.statusText", statusLabel, "value");
      statusLabel.addListener("changeValue", function (e) {
        qx.util.TimerManager.getInstance().start(function (value) {
          if (statusLabel.getValue() === value) statusLabel.setValue("");
        }, null, this, e.getData(), 5000);
      }, this);
    },
    
    /*
    ---------------------------------------------------------------------------
      SETUP TABLE
    ---------------------------------------------------------------------------
    */
    
    /**
     * Loads table layout from the server
     */
    _loadTableLayout: async function () {
      this.__loadingTableStructure = true;
      this.showMessage(this.tr("Loading table layout ..."));
      
      //console.log([this.getServiceName(), this.getDatasource(), this.getModelType() ]);
      let client = this.getApplication().getRpcClient(this.getServiceName());
      let data = await client.send("table-layout", [this.getDatasource(), this.getModelType()]);
      if (data === null) {
        this.warn("Loading table layout failed.");
        this.__loadingTableStructure = false;
        return;
      }
      this.showMessage("");
      
      // create the table
      this._createTableLayout(data);
      
      // notify listeners that the table is ready
      this.__tableReady = true;
      this.__loadingTableStructure = false;
      this.fireDataEvent("tableReady",data);
      this.getTable().setVisibility("visible");
    },
    
    /**
     * Creates the table layout from data sent by the server
     * @param data {Map} layout data from server
     */
    _createTableLayout: function (data) {
      /*
       * @todo: Dispose old table and connected objects if they
       * exist. this will result in a fatal error if done as below
       */
      let table = this.getTable();
      if (table) {
        //        this.getController().dispose();
        //        this.getStore().dispose();
        //        this.getMarshaler().dispose();
        //        this.getTableContainer().remove( table );
        //        table.dispose();
      }
      
      // create table
      table = this._createTable(data.columnLayout);
      table.getSelectionModel().addListener("changeSelection", this._on_table_changeSelection, this);
      
      // save columns
      let columnIds = [];
      for (let columnId in data.columnLayout) {
        columnIds.push(columnId)
      }
      this.setColumnIds(columnIds);
      
      // query data
      this.setQueryData(data.queryData);
      
      // save a reference to the table widget
      this.setTable(table);
      
      // add to the container
      this.getTableContainer().add(table);
      
      // marshaler
      let marshaler = new qcl.data.marshal.Table();
      this.setMarshaler(marshaler);
      
      // create store
      let store = new qcl.data.store.JsonRpcStore(this.getServiceName(), marshaler);
      this.setStore(store);
      
      // the controller propagates data changes between table and store. note
      // that you don't have to setup the bindings manually
      let controller = new qcl.data.controller.Table(table, store);
      this.setController(controller);
      
      // show status messages
      controller.addListener("statusMessage", function (e) {
        this.showMessage(e.getData());
      }, this);
    },
    
    /**
     * Create a new table instance. Expects a map, keys
     * being the column ids, the values maps of information
     * on the columns.
     *
     * @param columnLayout {Map}
     * <pre>
     * {
     *    column1 : {
     *      header : "Column 1",
     *      editable : true/false,
     *      visible : true/false,
     *      renderer : "Boolean", // from the qx.ui.table.cellrenderer namespace
     *      editor : "CheckBox", // from the qx.ui.table.celleditor namespace
     *      width : 12|"2*"
     *    },
     *
     *    column2 : { ....}
     * }
     * </pre>
     * @return {qx.ui.table.Table}
     */
    _createTable: function (columnLayout) {
      // analyze table info
      let columnIds = [], columnHeaders = [];
      for (let columnId in columnLayout) {
        columnIds.push(columnId);
        columnHeaders.push(columnLayout[columnId].header);
      }
      let tableModel = new qcl.data.model.Table();
      
      // set column labels and id
      tableModel.setColumns(columnHeaders, columnIds);
      
      // set columns (un-)editable and unsortable
      for (let i = 0; i < columnIds.length; i++) {
        tableModel.setColumnEditable(i, columnLayout[columnIds[i]].editable || false);
        tableModel.setColumnSortable(i, false);
      }
      
      // create table
      let custom = {
        tableColumnModel: function (obj) {
          return new qx.ui.table.columnmodel.Resize(obj);
        }
      };
      let table = new qx.ui.table.Table(tableModel, custom);
      
      // Use special cell editors and cell renderers
      let tcm = table.getTableColumnModel();
      for (let i = 0; i < columnIds.length; i++) {
        if (columnLayout[columnIds[i]].visible !== undefined) {
          tcm.setColumnVisible(i, columnLayout[columnIds[i]].visible);
        }
        if (columnLayout[columnIds[i]].renderer) {
          tcm.setDataCellRenderer(i, new qx.ui.table.cellrenderer[columnLayout[columnIds[i]].renderer]());
        }
        if (columnLayout[columnIds[i]].editor) {
          tcm.setCellEditorFactory(i, new qx.ui.table.celleditor[columnLayout[columnIds[i]].editor]());
        }
      }
      
      // set selection mode
      table.getSelectionModel().setSelectionMode(qx.ui.table.selection.Model.MULTIPLE_INTERVAL_SELECTION);
      
      // set width of columns
      let behavior = table.getTableColumnModel().getBehavior();
      behavior.setInitializeWidthsOnEveryAppear(true);
      for (let i = 0; i < columnIds.length; i++) {
        behavior.setWidth(i, columnLayout[columnIds[i]].width);
      }
      
      // other table layout settings
      table.setKeepFirstVisibleRowComplete(true);
      table.setShowCellFocusIndicator(false);
      table.setStatusBarVisible(false);
      
      // listeners
      //tableModel.addListener("dataChanged", this._retryApplySelection, this);
  

      return table;
    },
    
    /*
    ---------------------------------------------------------------------------
       EVENT HANDLERS
    ---------------------------------------------------------------------------
    */
    

    /**
     * Called when user clicks on a table cell.
     * Unused.
     */
    _on_table_cellClick: function (e) {
      //let table = e.getTarget();
      //let row = e.getRow();
      //let data = table.getUserData("data");
      //console.log([table,data,row]);
    },
    
    /**
     * Called when the selection in the table changes
     */
    _on_table_changeSelection: function () {
      if (this.__ignoreChangeSelection) {
        return;
      }
      
      let table = this.getTable();
      
      // collect the ids of the selected rows
      let selectionModel = table.getSelectionModel();
      let selectedRowData = [];
      let selectedIds = [];
      selectionModel.iterateSelection(function (index) {
        let rowData = table.getTableModel().getRowData(index);
        if (qx.lang.Type.isObject(rowData)) {
          selectedRowData.push(rowData);
          selectedIds.push(parseInt(rowData.id));
        }
      }, this);
      
      // Save selection data
      this.setSelectedIds(selectedIds);
      this.setSelectedRowData(selectedRowData);
      if (selectedIds.length) {
        this.__ignoreChangeSelection = true; // prevent infinite loop
        this.setModelId(selectedIds[0]);
        this.__ignoreChangeSelection = false;
      }
    },
  
    /*
     ---------------------------------------------------------------------------
        DRAG & DROP EVENT HANDLERS
     ---------------------------------------------------------------------------
     */
  
    /**
     * Outputs verbose drag session debug messages, suppressing duplicate
     * messages. Can be turned off using the `debugDragSession` property.
     * @param msg
     */
    dragDebug : function(msg){
      if( msg !== this.__lastDebugMessage && this.getDebugDragSession()){
        console.log(msg);
        this.__lastDebugMessage = msg;
      }
    },
    
    /**
     * Handles event fired whem a drag session starts.
     * @param e {qx.event.type.Drag} the drag event fired
     */
    _onDragStart: function (e) {
      let actions = this.getDragActions();
      this.dragDebug("Table drag start with actions " + actions.join(", ") );
      actions.forEach(action => e.addAction(action));
      e.addType(qcl.ui.table.TableView.types.ROWDATA);
    },
  
    /**
     * Fired when dragging over another widget.
     * @param e {qx.event.type.Drag} the drag event fired
     */
    _onDragOver: function (e) {
      //if( ! e.supportsType(qcl.ui.table.TableView.types.ROWDATA) ){
        this.dragDebug("Table Drag: Dropping on Table not supported...");
        e.preventDefault();
      //}
    },
  
    /**
     * Fired when dragging over the source widget.
     * @param e {qx.event.type.Drag} the drag event fired
     */
    _onDragHandler: function (e) {
      let relatedTarget = e.getRelatedTarget();
      if( relatedTarget ){
        relatedTarget.setDragModel(this.getSelectedRowData());
        relatedTarget.setDragType(qcl.ui.table.TableView.types.ROWDATA);
        return relatedTarget._onDragAction(e);
      }
    },
  
    /**
     * Handles the event that is fired when the user changes the mode of the drag during
     * the drag session
     * @param e {qx.event.type.Drag}
     * @private
     */
    _onDragChange : function(e){
      this.dragDebug("Table drag change...");
    },
  
    /**
     * Handles the event fired when a drag session ends (with or without drop).
     * @param e {qx.event.type.Drag}
     */
    _onDragEnd: function (e) {
      this.dragDebug("Table drag end.");
    },
  
    /**
     * Drop request handler. Calls the _onDropImpl method implementation.
     * @param e {qx.event.type.Drag}
     */
    _onDrop: function (e) {
      this.dragDebug("Table drop.");
    },
  
    /**
     * Called when a drop request is made
     * @param e {qx.event.type.Drag}
     * @private
     */
    _onDropRequest : function(e){
      this.dragDebug("Table Drop request");
      let type = e.getCurrentType();
      if (type === qcl.ui.table.TableView.types.ROWDATA ) {
        e.addData(qcl.ui.table.TableView.types.ROWDATA, this.getSelectedRowData());
      }
    },

    
    /*
    ---------------------------------------------------------------------------
       HELPER METHODS
    ---------------------------------------------------------------------------
    */
    
    /**
     * Tries to select the rows with the given ids.
     * @return {Boolean} Returns true if successful
     * and false if the ids could not be determined
     */
    _selectIds: function (ids) {
      if (this.__ignoreChangeSelection) {
        return;
      }

      if (!ids) {
        // remove selection?
        return;
      }

      //console.log("old selection is " + this.__selectedIds + ", new selection is " + ids);
      if (this.__selectedIds && "" + ids == "" + this.__selectedIds) {
        //console.log("Same, same");
        return;
      }
      this.__selectedIds = ids;

      //console.log("Selecting " + ids );
      let table = this.getTable();
      let selectionModel = table.getSelectionModel();

      selectionModel.resetSelection();
      this.__ignoreChangeSelection = true;

      ids.forEach(function (id) {
        let row = table.getTableModel().getRowById(id);
        //console.log("Id " + id + " is row "+ row);
        if (row !== undefined) {
          selectionModel.addSelectionInterval(row, row);
          table.scrollCellVisible(0, row);
        } else {
          //console.log("Cannot select row with id " + id + ". Data not loaded yet.");
          this.__selectedIds = null;
        }
      }, this);
      this.__ignoreChangeSelection = false;
      return !! this.__selectedIds;
    },

    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */
    
    /**
     * Shows a status message
     * @param msg {String}
     */
    showMessage: function (msg) {
      if (this._statusLabel) {
        this._statusLabel.setValue(msg);
      }
    },
    
    /**
     *
     * @returns {boolean}
     */
    isTableReady: function () {
      return this.__tableReady;
    },
    
    /**
     * Loads list item content
     * @return {void}
     */
    load: function () {
      
      this.clearTable();
      
      // if we don't have a model type yet, wait until we have one
      if (!this.getModelType()) {
        this.info("No model type for the table, waiting...");
        this.addListenerOnce("changeModelType", function () {
          this.load();
        }, this);
        return;
      }
      
      try {
        if (this.__loadingTableStructure) {
          this.info("We're still loading, ignoring load request...");
          return;
        }
        
        // if the table is not set up, wait for corresponding event
        if (!this.isTableReady()) {
          this.info("Table is not ready - deferring load request ...");
          this.addListenerOnce("tableReady", this.load, this);
          this._loadTableLayout();
          return;
        }
        
        // load a query
        if (this.getQuery()) {
          this.getMarshaler().setQueryParams([
            {
              'datasource': this.getDatasource(),
              'modelType': this.getModelType(),
              'query':
              {
                'properties': this.getColumnIds(),
                'orderBy': this.getQueryData().orderBy,
                'cql': this.getQuery()
              }
            }]);
          this.getController().reload();
          return;
        }
        
        // load a folder content
        if (this.getFolderId()) {
          this.getMarshaler().setQueryParams([
            {
              'datasource': this.getDatasource(),
              'modelType': this.getModelType(),
              'query':
              {
                'properties': this.getColumnIds(),
                'orderBy': this.getQueryData().orderBy,
                'relation':
                {
                  'name': this.getQueryData().relation.name,
                  'foreignId': this.getQueryData().relation.foreignId,
                  'id': this.getFolderId()
                }
              }
            }]);
          this.getController().reload();
          return;
        }
        
        // error
        this.warn("Cannot load - no folderId or query available.");
      } catch (e) {
        // debug
        this.warn(e);
        return;
      }
    },
    
    /**
     * Clear the current table
     */
    clearTable: function () {
      let table = this.getTable();
      if (table) {
        table.getTableModel().clearCache();
        table.getTableModel()._onRowCountLoaded(0);
      }
    },
    
    /**
     * Reload the current table
     */
    reload: function () {
      this.load();
    },
    
    /**
     * Selectes all rows
     */
    selectAll: function () {
      if (this.getTable()) {
        let last = this.getTable().getTableModel().getRowCount();
        this.getTable().getSelectionModel().setSelectionInterval(0, last);
      }
    },

    /**
     * Reset the current selection
     */
    resetSelection: function () {
      this.setSelectedIds([]);
      this.getTable().resetSelection();
    }
  }
});
