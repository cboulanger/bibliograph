/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/* *****
#require(qx.ui.table.cellrenderer)
#require(qx.ui.table.celleditor)
***** */

/**
 * Base class for Table widgets
 */
qx.Class.define("bibliograph.ui.reference.ListView",
{
  extend : qx.ui.container.Composite,
  include : [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties :
  {
    /**
     * the datasource of the current tables
     */
    datasource :
    {
      check : "String",
      nullable : true,
      event : "changeDatasource",
      apply : "_applyDatasource"
    },

    /**
    * the id of the folder that might affect loaded tables
    */
    folderId :
    {
      check : "Integer",
      nullable : true,
      event : "changeFolderId",
      apply : "_applyFolderId"
    },

    /**
     * the current query executed in all the tables
     */
    query :
    {
      check : "String",
      nullable : true,
      event : "changeQuery",
      apply : "_applyQuery"
    },

    /**
     * The type of the currently selected model record, if any
     */
    modelType :
    {
      check : "String",
      nullable : true,
      event : "changeModelType"
    },

    /**
     * The id of the currently selected model record, if any
     */
    modelId :
    {
      check : "Integer",
      nullable : true,
      event : "changeModelId"
    },

    /**
     * The ids of the currently selected rows
     * @type
     */
    selectedIds :
    {
      check : "Array",
      nullable : true,
      event : "changeSelectedIds",
      apply : "_applySelectedIds"
    },

    /**
     * The data of the currently selected rows
     * @type
     */
    selectedRowData :
    {
      check : "Array",
      nullable : true,
      event : "changeSelectedRowData"
    },

    /**
     * The table widget
     */
    table :
    {
      check : "qx.ui.table.Table",
      nullable : true
    },

    /**
     * An array of column ids
     */
    columnIds :
    {
      check : "Array",
      nullable : true
    },

    /*
     * The cotainer for the table widget
     */
    tableContainer :
    {
      check : "qx.ui.core.Widget",
      nullable : true
    },
    controller :
    {
      check : "qx.core.Object",
      nullable : true,
      event : "changeController"
    },
    marshaler :
    {
      check : "qx.core.Object",
      nullable : true,
      event : "changeMarshaler"
    },
    store :
    {
      check : "qx.core.Object",
      nullable : true,
      event : "changeStore"
    },
    serviceName :
    {
      check : "String",
      nullable : true
    },
    queryData :
    {
      check : "Object",
      nullable : true
    }
  },

  /*
  *****************************************************************************
      EVENTS
  *****************************************************************************
   */
  events : {
    "tableReady" : "qx.event.type.Event"
  },

  /*
  *****************************************************************************
      CONSTRUCTOR
  *****************************************************************************
   */
  construct : function()
  {
    this.base(arguments);
    this.setSelectedRowData([]);
    this.setSelectedIds([]);
    this.setColumnIds([]);
    this.setTableContainer(this);
    this.setLayout(new qx.ui.layout.Grow());
    this.__tableModelTypes = {

    };
    qx.event.message.Bus.subscribe("folder.reload", this._on_reloadFolder, this);
    qx.event.message.Bus.subscribe("reference.changeData", this._on_changeReferenceData, this);
    qx.event.message.Bus.subscribe("reference.removeFromFolder", this._on_removeRows, this);
    this.createPopup();
  },

  /*
  *****************************************************************************
      MEMBERS
  *****************************************************************************
   */
  members :
  {
    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */
    __tableReady : false,
    __tableModelTypes : null,

    /*
    ---------------------------------------------------------------------------
       WIDGETS
    ---------------------------------------------------------------------------
    */
    contentPane : null,
    referenceViewLabel : null,
    statusLabel : null,

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
    _applyDatasource : function(value, old) {
      /*
       * hide old table
       */
      if (old)
      {
        this.referenceViewLabel.setValue("");

        /*
         * clear table and mark as not ready so that
         * the next loading request checks for table changes
         */
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
    _applyFolderId : function(folderId, old)
    {
      if (folderId === 0)
      {
        this.clearTable();
        return;
      }

      /*
       * use a small timeout to avoid rapid reloads
       */
      qx.util.TimerManager.getInstance().start(function()
      {
        /*
         * if the folder id has already changed, do not load
         */
        if (folderId != this.getFolderId()) {
          return;
        }

        // @todo this is not very elegant
        var datasource = this.getDatasource();
        var mainFolderTree = this.getApplication().getWidgetById("mainFolderTree");
        var selectedNode = mainFolderTree.getSelectedNode();

        /*
         * if a folder has been selected in the main tree
         */
        if (selectedNode && selectedNode.data.id == folderId)
        {
          /*
           * we can get the folder hierarchy for the caption
           */
          var hierarchy = mainFolderTree.getTree().getHierarchy(selectedNode);
          hierarchy.unshift(this.getApplication().getDatasourceLabel());
          this.referenceViewLabel.setValue(hierarchy.join(" > "));

          /*
           * is it a query?
           * @todo - rewrite this
           */
          var query = selectedNode.data.query;
          if (qx.lang.Type.isString(query) && query != "")
          {
            this.getApplication().setQuery(query);
            return;
          } else
          {
            this.getApplication().setQuery("");
          }
        }

        /*
         * otherwise, load folder
         */
        this.load();
      }, null, this, null, 500);
    },
    _applyQuery : function(query, old) {
      if (query) {
        this.load();
      }
    },
    _applySelectedIds : function(value, old) {
      /*
       * if the table is not set up, wait for corresponding event
       */
      if (!this.isTableReady()) {
        this.addListenerOnce("tableReady", function() {
          this._selectIds(value);
        }, this);
      } else {
        this._selectIds(value);
      }
    },
    _selectIds : function(ids)
    {
      //      this.getController().addListenerOnce("blockLoaded", function(data){

      //        var count=0;

      //        ids.forEach(function(id){

      //          var row = this.getTable().getModel().getRowById(id);

      //          if ( row )

      //          {

      //            this.getTable().getSelectionModel().addSelectionInterval(row,row);

      //            this.getTable().scrollCellVisible(0,row);

      //            count++;

      //          }

      //        },this);

      //      },this);
    },

    /*
    ---------------------------------------------------------------------------
      internal methods
    ---------------------------------------------------------------------------
    */

    /**
     * Checks whether the table layout has to be created or recreated
     * due to changes in the datasource model. Loads table layout
     * from the server if neccessary.
     */
    _checkTableLayout : function()
    {
      /*
       * get table model type from datasource model. if not loaded,
       * wait until loaded and call method again
       */
      var dsModel = this.getApplication().getDatasourceModel();
      if (!dsModel)
      {
        this.getApplication().addListenerOnce("changeDatasourceModel", function(e) {
          if (e.getData()) {
            this._checkTableLayout();
          }
        }, this);
        return;
      }
      var modelType = this.getModelType() || dsModel.getTableModelType();
      var serviceName = this.getServiceName() || dsModel.getTableModelService();

      /*
       * if the datasource model properties relevant for the
       * table haven't changed, use existing table
       */
      if (this.getTable() && modelType == this.getModelType() && serviceName == this.getServiceName())
      {
        this.__tableReady = true;
        this.getTable().setVisibility("visible");
        this.fireEvent("tableReady");
        return;
      }

      /*
       * otherwise, load new table layout
       */
      this.setModelType(modelType);
      this.setServiceName(serviceName);
      this.__loadingTableStructure = true;
      this.showPopup(this.tr("Loading table ..."));

      //console.log([this.getServiceName(), this.getDatasource(), this.getModelType() ]);
      this.getApplication().getRpcManager().execute(this.getServiceName(), "getTableLayout", [this.getDatasource(), this.getModelType()], function(data)
      {
        this.hidePopup();

        /*
         * create the table
         */
        this._createTableLayout(data);

        /*
         * notify listeners that the table is ready when
         * it appears
         */
        this.__tableReady = true;
        this.__loadingTableStructure = false;
        this.fireEvent("tableReady");
        this.getTable().setVisibility("visible");
      }, this);
    },

    /**
     * Creates the table layout from data sent by the server
     * @param data {Map} layout data
     */
    _createTableLayout : function(data)
    {
      /*
       * @todo: Dispose old table and connected objects if they
       * exist. this will result in a fatal error if done as below
       */
      var table = this.getTable();
      if (table)
      {
        //        this.getController().dispose();

        //        this.getStore().dispose();

        //        this.getMarshaler().dispose();

        //        this.getTableContainer().remove( table );

        //        table.dispose();
      }

      /*
       * create table
       */
      table = this._createTable(data.columnLayout);
      table.getSelectionModel().addListener("changeSelection", this._on_table_changeSelection, this);

      /*
       * save columns
       */
      var columnIds = [];
      for (var columnId in data.columnLayout) {
        columnIds.push(columnId)
      }
      this.setColumnIds(columnIds);

      /*
       * query data
       */
      this.setQueryData(data.queryData);

      /*
       * save a reference to the table widget
       */
      this.setTable(table);

      /*
       * add to the layout
       */
      this.getTableContainer().add(table);

      /*
       * marshaler, set the datasource and a null
       * value for query data.
       */
      var marshaler = new virtualdata.marshal.Table();
      this.setMarshaler(marshaler);

      /*
       * create store
       */
      var store = new qcl.data.store.JsonRpc(null, this.getServiceName(), marshaler);
      this.setStore(store);

      /*
       * the controller propagates data changes between table and store. note
       * that you don't have to setup the bindings manually
       */
      var controller = new virtualdata.controller.Table(table, store);
      this.setController(controller);

      /*
       * buttons for add menu
       */
      if (data.addItems && data.addItems.length && this.listViewAddMenu && this.listViewAddMenuButton)
      {
        data.addItems.forEach(function(item)
        {
          var menuButton = new qx.ui.menu.Button(item.label, item.icon);
          menuButton.setUserData("type", item.value);
          menuButton.addListener("execute", this._on_addItemMenu_execute, this);
          this.listViewAddMenu.add(menuButton);
        }, this);
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
    _createTable : function(columnLayout)
    {
      /*
       * analyze table info
       */
      var columnIds = [], columnHeaders = [];
      for (var columnId in columnLayout)
      {
        columnIds.push(columnId);
        columnHeaders.push(columnLayout[columnId].header);
      }
      var tableModel = new virtualdata.model.Table();

      /*
       * set column labels and id
       */
      tableModel.setColumns(columnHeaders, columnIds);

      /*
       * set columns editable
       */
      for (var i = 0; i < columnIds.length; i++) {
        tableModel.setColumnEditable(i, columnLayout[columnIds[i]].editable || false);
      }

      /*
       * create table
       */
      var custom = {
        tableColumnModel : function(obj) {
          return new qx.ui.table.columnmodel.Resize(obj);
        }
      };
      var table = new qx.ui.table.Table(tableModel, custom);

      /*
       * Use special cell editors a
       * nd cell renderers
       */
      var tcm = table.getTableColumnModel();
      for (var i = 0; i < columnIds.length; i++)
      {
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

      /*
       * set selection mode
       */
      table.getSelectionModel().setSelectionMode(qx.ui.table.selection.Model.MULTIPLE_INTERVAL_SELECTION);

      /*
       * set width of columns
       */
      var behavior = table.getTableColumnModel().getBehavior();
      behavior.setInitializeWidthsOnEveryAppear(true);
      for (var i = 0; i < columnIds.length; i++) {
        behavior.setWidth(i, columnLayout[columnIds[i]].width);
      }
      table.setKeepFirstVisibleRowComplete(true);
      table.setShowCellFocusIndicator(false);
      table.setStatusBarVisible(false);
      return table;
    },

    /*
    ---------------------------------------------------------------------------
       EVENT LISTENERS
    ---------------------------------------------------------------------------
    */

    /**
     * Called when user clicks on a table cell
     * @param table
     * @param row
     * @return
     */
    _on_table_cellClick : function(table, row)
    {
      var table = event.getTarget();
      var row = event.getRow();
      var data = table.getUserData("data");

      //console.log([table,data,row]);
    },

    /**
     * Called when the selection in the table changes
     * @param datasource
     * @param type
     * @return
     */
    _on_table_changeSelection : function()
    {
      var table = this.getTable();

      /*
       * collect the ids of the selected rows
       */
      var selectionModel = table.getSelectionModel();
      var selectedRowData = [];
      var selectedIds = [];
      selectionModel.iterateSelection(function(index)
      {
        var rowData = table.getTableModel().getRowData(index);
        if (qx.lang.Type.isObject(rowData))
        {
          selectedRowData.push(rowData);
          selectedIds.push(parseInt(rowData.id));
        }
      }, this);

      /*
       * Save selection data
       */
      this.setSelectedIds(selectedIds);
      this.setSelectedRowData(selectedRowData);
      if (selectedIds.length) {
        this.setModelId(selectedIds[0]);
      }
    },

    /**
     * Called when a menu item in the "Add item" menu is clicked
     * @param e {qx.event.type.Event}
     * @return
     */
    _on_addItemMenu_execute : function(e) {
      this.createReference(e.getTarget().getUserData("type"));
    },

    /**
     * Called when the server sends the "reloadFolder" message
     * @param e {qx.event.type.Data}
     */
    _on_reloadFolder : function(e)
    {
      var data = e.getData();
      if (data.datasource == this.getDatasource() && data.folderId == this.getFolderId()) {
        this.reload();
      }
    },

    /**
     * Called when the server sends the "reference.changeData" message
     * @param e {qx.event.type.Data}
     */
    _on_changeReferenceData : function(e)
    {
      var data = e.getData();
      var table = this.getTable();
      if (!table)return;

      var tableModel = table.getTableModel();
      var column = tableModel.getColumnIndexById(data.name);
      if (column === undefined)return;

      var row = tableModel.getRowById(data.referenceId);
      tableModel.setValue(column, row, data.value.replace(/\n/, "; "));
    },

    /**
     * Called when the server sends the "removeRows" message
     * @param e {qx.event.type.Data}
     */
    _on_removeRows : function(e)
    {
      var data = e.getData();
      if (data.datasource == this.getDatasource() && data.folderId == this.getFolderId())
      {
        var table = this.getTable();
        var tableModel = table.getTableModel();
        if (!qx.lang.Type.isArray(data.ids)) {
          this.error("Invalid id data.")
        }
        table.resetSelection();

        /*
         * get row indexes from ids
         */
        var rows = [];
        data.ids.forEach(function(id) {
          rows.push(tableModel.getRowById(id))
        });

        /*
         * sort row indexes descending and remove them
         */
        rows.sort(function(a, b) {
          return b - a
        });
        rows.forEach(function(row) {
          tableModel.removeRow(row)
        });
      }
    },

    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */
    isTableReady : function() {
      return this.__tableReady;
    },

    /**
     * Loads list item content of the given folder.
     * @return {void}
     */
    load : function()
    {
      if (!this.getModelType())
      {
        this.addListenerOnce("changeModelType", function() {
          this.load();
        }, this);
        return;
      }
      try
      {
        /*
         * if we're still loading the table, don't do anything
         */
        if (this.__loadingTableStructure) {
          return;
        }

        /*
         * if the table is not set up, wait for corresponding event
         */
        if (!this.isTableReady())
        {
          this.addListenerOnce("tableReady", this.load, this);
          this._checkTableLayout();
          return;
        }

        /*
         * load a query
         */
        if (this.getQuery())
        {
          /*
           * convert string query into one the backend can understand
           */
          this.getMarshaler().setQueryParams([
          {
            'datasource' : this.getDatasource(),
            'modelType' : this.getModelType(),
            'query' :
            {
              'properties' : this.getColumnIds(),
              'orderBy' : this.getQueryData().orderBy,
              'cql' : this.getQuery()
            }
          }]);
          this.getController().reload();
          return;
        }

        /*
         * load a folder content
         */
        if (this.getFolderId())
        {
          this.getMarshaler().setQueryParams([
          {
            'datasource' : this.getDatasource(),
            'modelType' : this.getModelType(),
            'query' :
            {
              'properties' : this.getColumnIds(),
              'orderBy' : this.getQueryData().orderBy,
              'link' :
              {
                'relation' : this.getQueryData().link.relation,
                'foreignId' : this.getFolderId()
              }
            }
          }]);
          this.getController().reload();
          return;
        }

        /*
         * error
         */
        this.warn("Cannot load - no folderId or query available.");
      }catch (e)
      {
        // debug
        this.warn(e);
        return;
      }
    },

    /**
     * Clears the tables for the given datasource
     * @param datasource {String}
     */
    clearTable : function()
    {
      var table = this.getTable();
      if (table)
      {
        table.getTableModel().clearCache();
        table.getTableModel()._onRowCountLoaded(0);
      }
    },

    /**
     * Reloads the listview
     */
    reload : function() {
      this.load();
    },

    /**
     * Selectes all rows
     */
    selectAll : function() {
      if (this.getTable())
      {
        var last = this.getTable().getTableModel().getRowCount();
        this.getTable().getSelectionModel().setSelectionInterval(0, last);
      }
    },

    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */
    createReference : function(reftype)
    {
      var folderId = this.getFolderId();
      if (!folderId)
      {
        dialog.alert(this.tr("You cannot create an item outside a folder"));
        return false;
      }
      var store = this.getStore();
      store.execute("create", [this.getDatasource(), this.getFolderId(), reftype], function()
      {
        //this.loadFolder( datasource, folderId );
      }, this);
    },

    /**
     * Remove a reference from a folder
     */
    _removeReference : function()
    {
      var message = this.tr("Do your really want to remove the selected references?");
      var handler = qx.lang.Function.bind(function(result) {
        if (result === true) {
          this.modifyReferences("remove", null);
        }
      }, this);
      dialog.confirm(message, handler);
    },

    /**
     * Move reference from one folder to the other
     */
    _moveReference : function()
    {
      var app = this.getApplication();
      var win = app.getWidgetById("folderTreeWindow");
      win.addListenerOnce("nodeSelected", function(e)
      {
        var node = e.getData();
        if (!node)
        {
          dialog.alert("No folder selected. Try again");
          return;
        }
        var message = this.tr("Do your really want to move the selected references to '%1'?", [node.label]);
        var handler = qx.lang.Function.bind(function(result) {
          if (result === true) {
            this.modifyReferences("move", parseInt(node.data.id));
          }
        }, this);
        dialog.confirm(message, handler);
      }, this);
      win.show();
    },

    /**
     * Copy a reference to a folder
     */
    _copyReference : function()
    {
      var app = this.getApplication();
      var win = app.getWidgetById("folderTreeWindow");
      win.addListenerOnce("nodeSelected", function(e)
      {
        var node = e.getData();
        if (!node)
        {
          dialog.alert("No folder selected. Try again");
          return;
        }
        var message = this.tr("Do your really want to copy the selected referencesto '%1'?", [node.label]);
        var handler = qx.lang.Function.bind(function(result) {
          if (result === true) {
            this.modifyReferences("copy", parseInt(node.data.id));
          }
        }, this);
        dialog.confirm(message, handler);
      }, this);
      win.show();
    },

    /**
     * Send a server request to modify a reference
     * @param action {String}
     * @param targetFolderId {Integer}
     */
    modifyReferences : function(action, targetFolderId)
    {
      var datasource = this.getDatasource();
      var folderId = this.getFolderId();
      var selectedIds = this.getSelectedIds();
      var type = this.getModelType();
      var store = this.getStore();
      this.getApplication().setModelId(0);
      this.showPopup(this.tr("Processing request..."));
      store.execute(action + "References", [datasource, folderId, targetFolderId, selectedIds], function() {
        this.hidePopup();
      }, this);
    },
    exportSelected : function()
    {
      var datasource = this.getDatasource();
      var selectedIds = this.getSelectedIds();
      this.showPopup(this.tr("Processing request..."));
      this.getApplication().getRpcManager().execute("bibliograph.export", "exportReferencesDialog", [datasource, null, selectedIds], function() {
        this.hidePopup();
      }, this);
    },
    exportFolder : function()
    {
      var datasource = this.getDatasource();
      var folderId = this.getFolderId();
      this.showPopup(this.tr("Processing request..."));
      this.getApplication().getRpcManager().execute("bibliograph.export", "exportReferencesDialog", [datasource, folderId, null], function() {
        this.hidePopup();
      }, this);
    },
    findReplace : function()
    {
      var datasource = this.getDatasource();
      var folderId = this.getFolderId();
      var selectedIds = this.getSelectedIds();
      this.showPopup(this.tr("Processing request..."));
      this.getApplication().getRpcManager().execute("bibliograph.reference", "findReplaceDialog", [datasource, folderId, selectedIds], function() {
        this.hidePopup();
      }, this);
    },
    dummy : null
  }
});
