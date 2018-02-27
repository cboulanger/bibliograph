/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2015 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/*global qx qcl virtualdata dialog*/

/**
 * Base class for virtual tree widgets which load their data from different 
 * datasources. The data is cached for performance, so that switching the 
 * datasource won't result in expensive reloads.
 * @asset(icon/16/places/folder-remote.png)
 * @asset("icon/16/places/folder.png")
 * @asset("icon/16/apps/utilities-graphics-viewer.png")
 * @asset("icon/16/places/user-trash.png")
 * @asset("icon/16/places/user-trash-full.png")
 * @asset("icon/16/actions/folder-new.png")
 * @asset("icon/16/places/folder-remote.png")
 * @asset("icon/16/actions/help-about.png")
 * @todo Theme It!
 */
qx.Class.define("qcl.ui.treevirtual.TreeView", {
  extend: qx.ui.container.Composite,

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties: {
    /**
     * The headers of the tree columns
     */
    columnHeaders: {
      check: "Array",
      nullable: false
    },

    /** 
     * The datasource of this folderTree 
     */
    datasource: {
      check: "String",
      init: null,
      nullable: true,
      event: "changeDatasource",
      apply: "_applyDatasource"
    },

    /** 
     * The server-side id of the currently selected node  
     */
    nodeId: {
      check: "Integer",
      init: null,
      nullable: true,
      event: "changeNodeId",
      apply: "_applyNodeId"
    },

    /** 
     * The currently selected node
     */
    selectedNode: {
      check: "Object",
      nullable: true,
      event: "changeSelectedNode",
      apply: "_applySelectedNode"
    },

    /** 
     * The currently selecte node type
     */
    selectedNodeType: {
      check: "String",
      nullable: true,
      event: "changeSelectedNodeType"
    },

    /**
     * Callback function if tree is used as a chooser dialogue
     */
    callback: {
      check: "Function",
      nullable: true
    },

    /**
     * The widget displaying the tree
     */
    tree: {
      check: "qx.ui.treevirtual.TreeVirtual",
      nullable: true,
      apply: "_applyTree",
      event: "changeTree"
    },

    /**
    * The current controller
    */
    controller: {
      check: "qx.core.Object",
      nullable: true
    },

    /**
    * The current data store 
    */
    store: {
      check: "qx.core.Object",
      nullable: true
    },

    /**
    * The name of the service which supplies the tree data
    */
    serviceName: {
      check: "String",
      nullable: false
    },

    /**
    * Use a cache to save tree data
    * Not implemented, does nothing currently.
    */
    useCache: {
      check: "Boolean",
      init: true
    },

    /**
    * The service method used to query the number of nodes in the tree
    */
    nodeCountMethod: {
      check: "String",
      init: "getNodeCount"
    },  

    /**
    * The service method used to query the number of nodes in the tree
    */
    childNodeDataMethod: {
      check: "String",
      init: "child-data"
    },

    /**
    * The number of nodes that are transmitted in each request. If null, no limit
    * Not implemented, does nothing
    */
    childrenPerRequest: {
      check: "Integer",
      nullable: false,
      init: null
    },

    /**
    * The member property name of the tree widget 
    */
    treeWidgetContainer: {
      check: "qx.ui.core.Widget",
      nullable: true
    },

    /**
    * The type of model that is displayed as tree data.
    * Used to identify server messages.
    */
    modelType: {
      check: "String"
    },

    /**
     * Enable/disable drag and drop
     * Not implememted, does nothing
     */
    enableDragDrop: {
      check: "Boolean",
      init: false,
      event: "changeEnableDragDrop"
    },

    /**
     * Whether Drag & Drop should be limited to reordering
     * Not implemented, does nothing.
     */
    allowReorderOnly: {
      check: "Boolean",
      init: false,
      event: "changeAllowReorderOnly"
    },

    /**
     * Whether the tree columns should have headers. This works only
     * when set before the creation of the tree - it is not dynamically
     * toggable.
     */
    showColumnHeaders: {
      check: "Boolean",
      init: true
    }
  },

  /*
  *****************************************************************************
     EVENTS
  *****************************************************************************
  */
  events: {
    /**
     * Dispatched when the tree data has been fully loaded
     */
    loaded: "qx.event.type.Event"
  },

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */
  construct: function() {
    this.base(arguments);



    this.__datasources = {};

    this.__prompt = new dialog.Prompt();
    this.setTreeWidgetContainer(this);

    // server databinding
    this.__lastTransactionId = 0;
    let bus = qx.event.message.Bus;
    bus.subscribe(
      "folder.node.update",
      this._updateNode,
      this
    );
    bus.subscribe("folder.node.add", this._addNode, this);
    bus.subscribe(
      "folder.node.delete",
      this._deleteNode,
      this
    );
    bus.subscribe("folder.node.move", this._moveNode, this);
    bus.subscribe(
      "folder.node.reorder",
      this._reorderNodeChildren,
      this
    );
    bus.subscribe(
      "folder.node.select",
      this._selectNode,
      this
    );

    // drag & drop
    this.setAllowReorderOnly(true);
  },

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  members: {
    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
     */

    /**
    * The status label widget
    */
    _statusLabel: null,

    /**
    * A map of references to controller,store and tree widget 
    * connected to each datasource
    */
    __datasources: null,

    /**
    * Data sent with automatic server requests
    */
    __optionalRequestData: null,

    /**
    * reusable prompt box
    */
    __prompt: null,

    /**
    * Attempts to select a node
    */
    __selectAttempts: 0,

    __lastTransactionId: 0,

    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */

    /**
    * Handles the change in the datasource property of the widget
    */
    _applyDatasource: function(value, old) {
      if (value) {
        this.info("Tree is loading datasource " + value);
        this._setupTree(value, true);
      }
    },

    /**
    * Applies the new tree view
    */
    _applyTree: function(value, old) {
      if (old) {
        old.setVisibility("excluded");
      }
      value.setVisibility("visible");
    },

    /**
    * Applies the node id
    */
    _applyNodeId: function(value, old) {
      this.selectByServerNodeId(value);
    },

    _applySelectedNode: function(value, old) {
      // empty stub
    },

    /*
    ---------------------------------------------------------------------------
      SETUP TREE
    ---------------------------------------------------------------------------
    */

    /**
    * Returns a map with all the objects that are needed for a datasource: A tree,
    * a store, and a controller.
    * @param datasource {String}
    * @return {Map} A map containting the keys treeWidget, store and controller
    */
    _getDatasourceObjects: function(datasource) {
      if (this.__datasources[datasource] === undefined) {
        this.__datasources[datasource] = {
          treeWidget: null,
          store: null,
          controller: null
        };
      }
      return this.__datasources[datasource];
    },

    /**
    * Creates a tree and sets up the databinding for it.
    * @param datasource {String}
    */
    _createTree: function(datasource) {
      var ds = this._getDatasourceObjects(datasource);

      // tree widget
      var tree = new qcl.ui.treevirtual.DragDropTree(this.getColumnHeaders(), {
        dataModel: new qcl.data.model.SimpleTreeDataModel(),
        tableColumnModel: function(obj) {
          return new qx.ui.table.columnmodel.Resize(obj);
        }
      });
      tree.set({
        allowStretchY: true,
        alwaysShowOpenCloseSymbol: false,
        statusBarVisible: false,
        backgroundColor: "white",
        useTreeLines: true,
        showCellFocusIndicator: false,
        rowFocusChangeModifiesSelection: false
      });

      // drag & drop, not supported yet
      // this.bind("enableDragDrop", tree, "enableDragDrop");
      // this.bind("allowReorderOnly", tree, "allowReorderOnly");
      // tree.addListener("dragstart", this._on_dragstart, this );
      // tree.addListener("dragend", this._on_dragend, this );
      // tree.addListener("drop", this._on_drop, this );

      // configure columns
      tree
        .getTableColumnModel()
        .getBehavior()
        .setMinWidth(0, 80);
      tree
        .getTableColumnModel()
        .getBehavior()
        .setWidth(0, "6*");
      tree
        .getTableColumnModel()
        .getBehavior()
        .setMinWidth(1, 20);
      tree
        .getTableColumnModel()
        .getBehavior()
        .setWidth(1, "1*");

      // optionally hide header column
      tree.addListener(
        "appear",
        function() {
          if (!this.getShowColumnHeaders()) {
            tree.setHeaderCellsVisible(false);
          }
        },
        this
      );

      // event listeners
      tree.addListener("changeSelection", this._on_treeChangeSelection, this);
      tree.addListener("click", this._on_treeClick, this);
      tree.addListener("dblclick", this._on_treeDblClick, this);

      ds.treeWidget = tree;
      this.getTreeWidgetContainer().add(tree, { flex: 10, height: null });

      // Store
      ds.store = new qcl.data.store.JsonRpcStore(this.getServiceName());

      // Controller
      ds.controller = new qcl.data.controller.TreeVirtual(tree, ds.store);
      return ds;
    },

    /**
     * Creates the tree and optionally loads the data
     * @param datasource {String}
     * @param doLoad {Boolean|undefined}
     * @todo rewrite
     */
    _setupTree: function(datasource, doLoad) {
      //try{
      var loadData = false;
      if (datasource) {
        if (!this._getDatasourceObjects(datasource).treeWidget) {
          this.info("Creating tree...");
          this._createTree(datasource);
          loadData = true;
        }
        var ds = this._getDatasourceObjects(datasource);
        this.setStore(ds.store);
        this.setController(ds.controller);
        this.setTree(ds.treeWidget);

        if (doLoad && loadData) {
          this._loadTreeData(datasource, 0);
        }
      }
      //}catch(e){console.warn(e);}
    },

    /**
     * Retrieve tree data from the server, and synchronize the 
     * attached trees
     * @param datasource {String}
     * @param nodeId {Integer}
     */
    _loadTreeData: async function(datasource, nodeId) 
    {
      this.info("Loading tree data...");
      datasource = this.getDatasource(); // TODO fix parameter

      var app = qx.core.Init.getApplication();
      var store = this.getStore();
      var tree = this.getTree();
      var controller = this.getController();
      nodeId = nodeId || 0;

      // clear all bound trees
      store.setModel(null);
      var storeId = store.getStoreId();
      this.clearSelection();

      this.setEnabled(false);
      this.__loadingTreeData = true;

      // get node count and transaction id from server
      // let data = await store.load( "node-count", [datasource, this.getOptionalRequestData()] );// @todo unhardcode service method
      // var nodeCount = data.nodeCount;
      // var transactionId = data.transactionId;

      // if no tree, return
      // if (!nodeCount) {
      //   this.setEnabled(true);
      //   this.__loadingTreeData = false;
      //   return;
      // }

      // load raw data
      let model = await store.loadRaw( "load" ,[datasource] );// @todo unhardcode service method
      // since this is a raw load, we need to manually set the mode and fire the event
      store.fireDataEvent("loaded",model);
      store.setModel(model);
      this.setEnabled(true);
      this.__loadingTreeData = false;
    },

    /**
     * Returns optional request data for automatically called 
     * server requests
     * @return {unknown}
     */
    getOptionalRequestData: function() {
      return this.__optionalRequestData;
    },

    /**
     * Sets optional request data for automatically called 
     * server requests
     * @param data {unknown}
     * @return {void}
     */
    setOptionalRequestData: function(data) {
      this.__optionalRequestData = data;
    },

    /*
    ---------------------------------------------------------------------------
       EVENT HANDLERS
    ---------------------------------------------------------------------------
    */

    /**
     * Called when user clicks on node
     */
    _on_treeClick: function() {
      // do nothing at this point
    },

    /**
     * Called when user double-clicks on node
     */
    _on_treeDblClick: function() {
      var selNode = this.getSelectedNode();
      if (!selNode) return;
      var dataModel = this.getTree().getDataModel();
      dataModel.setState(selNode, { bOpened: !selNode.bOpened });
      dataModel.setData();
    },

    /**
     * Handler for event 'treeOpenWhileEmpty'
     * @param event {qx.event.type.Event} Event object
     * @return {void} void
     */
    _on_treeOpenWhileEmpty: function(event) {},

    /** 
     * Handler for event 'changeSelection' on the treeVirtual widget in 
     * the folderTree widget
     *
     * @param event {qx.event.type.Event} Event object
     * @return {void} void
     */
    _on_treeChangeSelection: function(event) {
      /*  
       * reset selected row cache 
       */
      this.setSelectedNode(null);
      this.setSelectedNodeType(null);

      /*
       * get new selection
       */
      var selection = event.getData();
      if (selection.length == 0) return;

      /*
       * get data
       */
      var tree = this.getTree();
      var app = this.getApplication();
      var node = selection[0];
      var data = node.data;
      var datasource = data.datasource || this.getDatasource();
      var nodeId = parseInt(data.id);
      //      var nodeType    = tree.getNodeType(node);

      /* 
       * update properties
       */
      this.setSelectedNode(node);
      this.setNodeId(nodeId);
    },

    /*
    ---------------------------------------------------------------------------
       DRAG & DROP
    ---------------------------------------------------------------------------
    */

    /**
     * Called when the user starts dragging a node
     * @param e {qx.event.type.Drag}
     */
    _on_dragstart: function(e) {
      this.__dragsession = true;
    },

    /**
     * Called when the drag session ends
     * @param e {qx.event.type.Drag}
     */
    _on_dragend: function(e) {
      this.__dragsession = false;
    },

    /**
     * Called when a dragged element is dropped onto the tree widget.
     * Override for your own behavior
     * @param e {qx.event.type.Drag}
     */
    _on_drop: function(e) {
      if (e.supportsType("qx/treevirtual-node")) {
        this.moveNode(e);
      }
    },

    /*
    ---------------------------------------------------------------------------
       SERVER DATABINDING EVENT HANDLERS
    ---------------------------------------------------------------------------
    */

    _selectNode: function(e) {
      this.setNodeId(e.getData());
    },

    /**
     * @todo rewrite the cache stuff! if the transaction id doesnt'change,
     * no need to update the cache!
     */
    _updateNode: function(e) {
      var data = e.getData();
      var tree = this.getTree();
      if (!tree) return;
      var dataModel = tree.getDataModel();
      var controller = this.getController();
      if (
        data.datasource == this.getDatasource() &&
        data.modelType == this.getModelType()
      ) {
        var nodeId = controller.getClientNodeId(data.nodeData.data.id);
        //console.warn( "updating client #" + nodeId + " server #" + data.nodeData.data.id);
        if (nodeId) {
          dataModel.setState(nodeId, data.nodeData);
          dataModel.setData();
          //controller.setTransactionId(data.transactionId);
          //this.cacheTreeData(data.transactionId);
        }
      }
    },

    _addNode: function(e) {
      var data = e.getData();
      var tree = this.getTree();
      if (!tree) return;
      var dataModel = tree.getDataModel();
      var controller = this.getController();
      if (
        data.datasource == this.getDatasource() &&
        data.modelType == this.getModelType()
      ) {
        var parentNodeId = controller.getClientNodeId(
          data.nodeData.data.parentId
        );
        //console.warn( "adding node to #" + parentNodeId );
        if (parentNodeId) {
          var nodeId;
          if (data.nodeData.isBranch) {
            nodeId = dataModel.addBranch(parentNodeId);
          } else {
            nodeId = dataModel.addLeaf(parentNodeId);
          }
          dataModel.setState(nodeId, data.nodeData);
          dataModel.setData();
          controller.setTransactionId(data.transactionId);
          //this.cacheTreeData(data.transactionId);
        }
      }
    },

    _moveNode: function(e) {
      var data = e.getData();
      var tree = this.getTree();
      if (!tree) return;
      var dataModel = tree.getDataModel();
      var controller = this.getController();
      if (
        data.datasource == this.getDatasource() &&
        data.modelType == this.getModelType()
      ) {
        var nodeId = controller.getClientNodeId(data.nodeId);
        var parentNodeId = controller.getClientNodeId(data.parentId);
        //console.warn( "moving #" + nodeId + " to #" + parentNodeId );
        if (nodeId && parentNodeId !== undefined) {
          var node = dataModel.getData()[nodeId];
          var oldParentNode = dataModel.getData()[node.parentNodeId];
          var newParentNode = dataModel.getData()[parentNodeId];
          node.parentNodeId = parentNodeId;
          oldParentNode.children.splice(
            oldParentNode.children.indexOf(nodeId),
            1
          );
          newParentNode.children.push(nodeId);
          dataModel.setData();
          controller.setTransactionId(data.transactionId);
          //this.cacheTreeData(data.transactionId);
        }
      }
    },

    /**
     * Called when the message "folder.node.delete" is received
     * @param e {qx.event.message.Message}
     */
    _deleteNode: function(e) {
      var data = e.getData();
      var tree = this.getTree();
      if (!tree) return;
      var dataModel = tree.getDataModel();
      var controller = this.getController();
      if (
        data.datasource == this.getDatasource() &&
        data.modelType == this.getModelType()
      ) {
        var nodeId = controller.getClientNodeId(data.nodeId);
        //console.warn( "deleting #" + nodeId );
        if (nodeId) {
          dataModel.prune(nodeId, true);
          dataModel.setData();
          controller.remapNodeIds();
          controller.setTransactionId(data.transactionId);
          //this.cacheTreeData(data.transactionId);
        }
      }
    },

    /**
     * Called by a server message to reorder the child nodes of
     * a given node.
     * @param e {qx.event.message.Message}
     */
    _reorderNodeChildren: function(e) {
      var data = e.getData();
      var tree = this.getTree();
      if (!tree) return;

      // check if the message concerns us
      if (
        data.datasource != this.getDatasource() ||
        data.modelType != this.getModelType()
      )
        return;

      // get the node data
      var dataModel = tree.getDataModel();
      var controller = this.getController();
      var nodeId = controller.getClientNodeId(data.nodeId);
      var parentNodeId = controller.getClientNodeId(data.parentNodeId);
      var parentNode = dataModel.getData()[parentNodeId];

      // reorder node children
      var pnc = parentNode.children;
      var oldPos = pnc.indexOf(nodeId);
      if (oldPos == data.position) {
        //this.debug("Node already at new position");
        return;
      }
      pnc.splice(oldPos, 1);
      pnc.splice(data.position, 0, nodeId);
      //this.debug("Changed child position");

      // render tree
      dataModel.setData();

      // save new tree state in cache
      controller.setTransactionId(data.transactionId);
      //this.cacheTreeData(data.transactionId);
    },

    /*
    ---------------------------------------------------------------------------
       PUBLIC API
    ---------------------------------------------------------------------------
    */

    /**
     * Clears the tree and loads a datasource into the tree display,
     * optionally with a pre-selected node
     * @param datasource {String}
     * @param nodeId {Int} Optional
     */

    load: function(datasource, nodeId) {
      /*
       * clear tree and load new tree data
       */
      if (datasource) {
        this._loadTreeData(datasource, nodeId);
      } else {
        this.warn("Cannot load: no datasource!");
      }
    },

    /**
     * Reload the widget
     * @return {void} void
     */
    reload: function() {
      /*
       * clear the tree and reload
       */
      var datasource = this.getDatasource();
      this.clearTree();
      this.load(datasource);
    },

    /**
     * Empties the tree view
     */
    clearTree: function() {
      try {
        this.getTree().resetSelection();
        this.getTree()
          .getDataModel()
          .prune(0);
      } catch (e) {}
    },

    /**
     * Returns true if the tree is still loading data.
     * @return {Boolean}
     */
    isLoading: function() {
      return this.__loadingTreeData;
    },

    /**
     * Selects a tree node by its server-side node id. If the tree is not
     * loaded, we wait for the "loaded" event first 
     * @param serverNodeId {Integer} TODOC
     */
    selectByServerNodeId: function(serverNodeId) {
      if (!this.getTree() || this.isLoading()) {
        this.addListenerOnce(
          "loaded",
          function() {
            this._selectByServerNodeId(serverNodeId);
          },
          this
        );
      } else {
        this._selectByServerNodeId(serverNodeId);
      }
    },

    /**
     * Selects a tree node by its server-side node id. Implements 
     * the selectByServerNodeId() method.
     * @param serverNodeId {Integer}  TODOC
     */
    _selectByServerNodeId: function(serverNodeId) {
      var id = this.getController().getClientNodeId(serverNodeId);
      if (!id) return;

      var tree = this.getTree();
      var model = tree.getDataModel();
      var node = tree.nodeGet(id);

      /*
       * open the tree so that the node is rendered
       */
      for (
        var parentId = node.parentNodeId;
        parentId;
        parentId = node.parentNodeId
      ) {
        node = tree.nodeGet(parentId);
        model.setState(node, { bOpened: true });
      }
      model.setData();

      /*
       * we need a timeout because tree rendering also uses
       * timeouts, so this is not synchronous
       */
      qx.event.Timer.once(
        function() {
          var row = model.getRowFromNodeId(id);
          if (row) {
            this.clearSelection();
            tree.getSelectionModel().setSelectionInterval(row, row);
          }
        },
        this,
        500
      );
    },

    /**
     * Clears the selection
     */
    clearSelection: function() {
      this.getTree()
        .getSelectionModel()
        .resetSelection();
    }
  }
});
