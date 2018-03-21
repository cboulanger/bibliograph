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
qx.Class.define("bibliograph.ui.reference.ListView",
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
      nullable: true
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
    }
  },

  /*
  *****************************************************************************
      EVENTS
  *****************************************************************************
   */
  events: {
    "tableReady": "qx.event.type.Event"
  },

  /*
  *****************************************************************************
      CONSTRUCTOR
  *****************************************************************************
   */
  construct: function () {
    this.base(arguments);
    this.setSelectedRowData([]);
    this.setSelectedIds([]);
    this.setColumnIds([]);
    this.setTableContainer(this);
    this.setLayout(new qx.ui.layout.Grow());
    this.__tableModelTypes = {};
    qx.event.message.Bus.subscribe("folder.reload", this._on_reloadFolder, this);
    qx.event.message.Bus.subscribe("reference.changeData", this._on_changeReferenceData, this);
    qx.event.message.Bus.subscribe("reference.removeRows", this._on_removeRows, this);
    qx.event.message.Bus.subscribe(bibliograph.AccessManager.messages.LOGOUT, this._on_logout, this);
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
       WIDGETS
    ---------------------------------------------------------------------------
    */
    contentPane: null,
    referenceViewLabel: null,
    statusLabel: null,

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
      qx.util.TimerManager.getInstance().start(function () {

        // if the folder id has already changed, do not load
        if (folderId != this.getFolderId()) {
          return;
        }

        // show breadcrumb
        var mainFolderTree = this.getApplication().getWidgetById("app/treeview");
        var selectedNode = mainFolderTree.getSelectedNode();

        if (selectedNode && selectedNode.data.id == folderId) {
          // we can get the folder hierarchy for the breadcrumb
          var hierarchy = mainFolderTree.getTree().getHierarchy(selectedNode);
          hierarchy.unshift(this.getApplication().getDatasourceLabel());
          var breadcrumb = hierarchy.join(" > ");

          // is it a query?
          var query = selectedNode.data.query;
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

      /*
       * clear folder Id
       */
      this.setFolderId(null);

      /*
       * show breadcrumb and load
       */
      var breadcrumb = this.tr("Query") + ": " + query;
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
      if (counter == "modelId") {
        counter = 0;
      }

      //console.log("Model id changed to " + value);
      if (value && value == this.getModelId()) {
        //console.log("Trying to select id " + value + ", attempt " + counter);
        if ((!this.isTableReady() || this._selectIds([value]) === false) && counter < 10) {
          qx.lang.Function.delay(this._applyModelId, 1000, this, value, old, ++counter);
        }
      }
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

      // notify listeners that the table is ready when it appears
      this.__tableReady = true;
      this.__loadingTableStructure = false;
      this.fireEvent("tableReady");
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
      var table = this.getTable();
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
      var columnIds = [];
      for (var columnId in data.columnLayout) {
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
      var marshaler = new qcl.data.marshal.Table();
      this.setMarshaler(marshaler);

      // create store
      var store = new qcl.data.store.JsonRpcStore(this.getServiceName(), marshaler);
      this.setStore(store);

      // the controller propagates data changes between table and store. note
      // that you don't have to setup the bindings manually
      var controller = new qcl.data.controller.Table(table, store);
      this.setController(controller);

      // show status messages
      controller.addListener("statusMessage", function (e) {
        this.showMessage(e.getData());
      }, this);

      // create reference type list
      // @todo refactor this
      if (data.addItems && data.addItems.length) {
        var model = qx.data.marshal.Json.createModel(data.addItems);
        this.chooseRefTypeList.setModel(model);
        this.listViewAddMenuButton.setEnabled(true);
      }
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
      var columnIds = [], columnHeaders = [];
      for (var columnId in columnLayout) {
        columnIds.push(columnId);
        columnHeaders.push(columnLayout[columnId].header);
      }
      var tableModel = new qcl.data.model.Table();

      // set column labels and id
      tableModel.setColumns(columnHeaders, columnIds);

      // set columns (un-)editable and unsortable
      for (var i = 0; i < columnIds.length; i++) {
        tableModel.setColumnEditable(i, columnLayout[columnIds[i]].editable || false);
        tableModel.setColumnSortable(i, false);
      }

      // create table
      var custom = {
        tableColumnModel: function (obj) {
          return new qx.ui.table.columnmodel.Resize(obj);
        }
      };
      var table = new qx.ui.table.Table(tableModel, custom);

      // Use special cell editors and cell renderers
      var tcm = table.getTableColumnModel();
      for (var i = 0; i < columnIds.length; i++) {
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
      var behavior = table.getTableColumnModel().getBehavior();
      behavior.setInitializeWidthsOnEveryAppear(true);
      for (var i = 0; i < columnIds.length; i++) {
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

    _on_logout: function () {
      this.clearTable();
    },

    /**
     * Called when user clicks on a table cell. Does nothing currently
     */
    _on_table_cellClick: function (e) {
      //var table = e.getTarget();
      //var row = e.getRow();
      //var data = table.getUserData("data");
      //console.log([table,data,row]);
    },

    /**
     * Called when the selection in the table changes
     */
    _on_table_changeSelection: function () {
      if (this.__ignoreChangeSelection) {
        return;
      }

      var table = this.getTable();

      // collect the ids of the selected rows
      var selectionModel = table.getSelectionModel();
      var selectedRowData = [];
      var selectedIds = [];
      selectionModel.iterateSelection(function (index) {
        var rowData = table.getTableModel().getRowData(index);
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

    /**
     * Called when a menu item in the "Add item" menu is clicked
     * @param e {qx.event.type.Event}
     */
    _on_addItemMenu_execute: function (e) {
      qx.core.Init.getApplication().setItemView("referenceEditor-main");
      this.createReference(e.getTarget().getUserData("type"));
    },

    /**
     * Called when the server sends the "reloadFolder" message
     * @param e {qx.event.type.Data}
     */
    _on_reloadFolder: function (e) {
      var data = e.getData();
      if (data.datasource == this.getDatasource() && data.folderId == this.getFolderId()) {
        this.reload();
      }
    },

    /**
     * Called when the server sends the "reference.changeData" message
     * @param e {qx.event.type.Data}
     */
    _on_changeReferenceData: function (e) {
      var data = e.getData();
      var table = this.getTable();
      if (!table) return;
      var tableModel = table.getTableModel();
      var column = tableModel.getColumnIndexById(data.name);
      if (column === undefined) return;
      var row = tableModel.getRowById(data.referenceId);
      if (row === undefined) return;
      tableModel.setValue(column, row, data.value.replace(/\n/, "; "));
    },

    /**
     * Called when the server sends the "removeRows" message
     * @param e {qx.event.type.Data}
     */
    _on_removeRows: function (e) {
      var data = e.getData();

      // is this message really for me?
      if (data.datasource != this.getDatasource()) return;
      if (data.folderId && data.folderId != this.getFolderId()) return
      if (data.query && data.query != this.getQuery()) return;

      var table = this.getTable();
      var tableModel = table.getTableModel();
      if (!qx.lang.Type.isArray(data.ids)) {
        this.error("Invalid id data.")
      }
      this.resetSelection();

      /*
       * get row indexes from ids
       */
      var row, rows = [];
      data.ids.forEach(function (id) {
        row = tableModel.getRowById(id);
        if (row !== undefined) rows.push(row); // FIXME this is a bug
      });

      /*
       * sort row indexes descending and remove them
       */
      rows.sort(function (a, b) {
        return b - a
      });

      if (rows.length) {
        rows.forEach(function (row) {
          tableModel.removeRow(row);
        });
      }
      else {
        this.reload();
      }

      /*
       * rebuild the row-id index because now rows are missing
       */
      tableModel.rebuildIndex();

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
      var table = this.getTable();
      var selectionModel = table.getSelectionModel();

      selectionModel.resetSelection();
      this.__ignoreChangeSelection = true;

      ids.forEach(function (id) {
        var row = table.getTableModel().getRowById(id);
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

      return this.__selectedIds ? true : false;

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
     * Clears the tables for the given datasource
     * @param datasource {String}
     */
    clearTable: function () {
      var table = this.getTable();
      if (table) {
        table.getTableModel().clearCache();
        table.getTableModel()._onRowCountLoaded(0);
      }
    },

    /**
     * Reloads the listview
     */
    reload: function () {
      this.load();
    },

    /**
     * Selectes all rows
     */
    selectAll: function () {
      if (this.getTable()) {
        var last = this.getTable().getTableModel().getRowCount();
        this.getTable().getSelectionModel().setSelectionInterval(0, last);
      }
    },

    resetSelection: function () {
      this.setSelectedIds([]);
      this.getTable().resetSelection();
    },

    createReference: function (reftype) {
      var folderId = this.getFolderId();
      if (!folderId) {
        dialog.Dialog.alert(this.tr("You cannot create an item outside a folder"));
        return false;
      }
      var store = this.getStore();
      store.execute("create", [this.getDatasource(), this.getFolderId(), reftype], function () {
        //this.loadFolder( datasource, folderId );
      }, this);
    },

    /**
     * Remove a reference from a folder
     */
    _removeReference: function () {
      var message = this.getFolderId() ?
      this.tr("Do your really want to remove the selected references from this folder?") :
      this.tr("Do your really want to remove the selected references?");
      var handler = qx.lang.Function.bind(function (result) {
        if (result === true) {
          this.modifyReferences("remove", null);
        }
      }, this);
      dialog.Dialog.confirm(message, handler);
    },

    /**
     * Move reference from one folder to the other
     */
    _moveReference: function () {
      var app = this.getApplication();
      var win = app.getWidgetById("bibliograph/folderTreeWindow");
      win.addListenerOnce("nodeSelected", function (e) {
        var node = e.getData();
        if (!node) {
          dialog.Dialog.alert("No folder selected. Try again");
          return;
        }
        var message = this.tr("Do your really want to move the selected references to '%1'?", [node.label]);
        var handler = qx.lang.Function.bind(function (result) {
          if (result === true) {
            this.modifyReferences("move", parseInt(node.data.id));
          }
        }, this);
        dialog.Dialog.confirm(message, handler);
      }, this);
      win.show();
    },

    /**
     * Copy a reference to a folder
     */
    _copyReference: function () {
      var app = this.getApplication();
      var win = app.getWidgetById("bibliograph/folderTreeWindow");
      win.addListenerOnce("nodeSelected", function (e) {
        var node = e.getData();
        if (!node) {
          dialog.Dialog.alert("No folder selected. Try again");
          return;
        }
        var message = this.tr("Do your really want to copy the selected referencesto '%1'?", [node.label]);
        var handler = qx.lang.Function.bind(function (result) {
          if (result === true) {
            this.modifyReferences("copy", parseInt(node.data.id));
          }
        }, this);
        dialog.Dialog.confirm(message, handler);
      }, this);
      win.show();
    },

    /**
     * Send a server request to modify a reference
     * @param action {String}
     * @param targetFolderId {Integer}
     */
    modifyReferences: function (action, targetFolderId) {
      let datasource = this.getDatasource();
      let folderId = this.getFolderId();
      let selectedIds = this.getSelectedIds();
      let query = this.getQuery() || null;
      let app = this.getApplication();
      app.setModelId(0);
      let params = [datasource, query || folderId, targetFolderId, selectedIds.join(",")];
      app.showPopup(this.tr("Processing request..."));
      app.getRpcClient("reference").send(action, [params])
      .then(() => {
        app.hidePopup();
      });
    },

    /**
     * Exports the selected references via jsonrpc service
     */
    exportSelected: function () {
      var datasource = this.getDatasource();
      var selectedIds = this.getSelectedIds();
      var app = this.getApplication();
      app.showPopup(this.tr("Processing request..."));
      app.getRpcClient("export").send(
      "exportReferencesDialog",
      [datasource, selectedIds],
      function () {
        app.hidePopup();
      }, this
      );
    },

    /**
     * Exports the whole folder or query
     */
    exportFolder: function () {
      var app = this.getApplication();
      app.showPopup(this.tr("Processing request..."));
      app.getRpcClient("export").send(
      "exportReferencesDialog",
      [this.getDatasource(), this.getFolderId() || this.getQuery()],
      function () {
        app.hidePopup();
      }, this
      );
    },

    /**
     * Finds and replaces text in the database using a service
     */
    findReplace: function () {
      var datasource = this.getDatasource();
      var folderId = this.getFolderId();
      var selectedIds = this.getSelectedIds();
      var app = this.getApplication();
      app.showPopup(this.tr("Processing request..."));
      app.getRpcClient("reference").send("findReplaceDialog", [datasource, folderId, selectedIds], function () {
        app.hidePopup();
      }, this);
    },

    /**
     * Empties the current folder
     */
    emptyFolder: function () {
      var datasource = this.getDatasource();
      var folderId = this.getFolderId();
      var app = this.getApplication();
      var msg = this.tr("Do you really want to make the folder empty, moving all references to the trash that are not in other folders?");
      dialog.Dialog.confirm(msg, function (yes) {
        if (!yes) return;
        app.showPopup(this.tr("Emptying the folder ..."));
        app.getRpcClient("reference").send("removeAllFromFolder", [datasource, folderId], function () {
          app.hidePopup();
        }, this);
      }, this);
    },
    dummy: null
  }
});
