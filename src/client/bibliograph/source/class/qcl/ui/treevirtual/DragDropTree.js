/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2018 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
   *  saaj <mail@saaj.me>
  
************************************************************************ */
/*global qx qcl virtualdata*/

/**
 * Provides drag&drop to TreeVirtual. Currently, only the "move" action is
 * supported.
 */
qx.Class.define("qcl.ui.treevirtual.DragDropTree",
{
  
  extend: qx.ui.treevirtual.TreeVirtual,
  include: [qx.ui.treevirtual.MNode, qx.ui.table.MTableContextMenu],
  
  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */
  construct: function (headings, custom) {
    
    custom = !custom ? {} : custom;
    custom.tablePaneHeader = function (obj) {
      /*
       * This is workaround for disabling draggable tree column.
       * Also i could not override it by setting Scroller
       * obj is tablePaneScroller
       */
      let stub = function () {
      };
      obj._onChangeCaptureHeader = stub;
      obj._onMousemoveHeader = stub;
      obj._onMousedownHeader = stub;
      obj._onMouseupHeader = stub;
      obj._onClickHeader = stub;
      
      return new qx.ui.table.pane.Header(obj);
    };
    
    this.base(arguments, headings, custom);
    this._createIndicator();
    this.setAllowDropTypes(['*']);
  },
  
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties:
  {
    
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
     * An array of node types allowed to be dragged
     */
    includeDragTypes:
    {
      check: "qx.data.Array",
      nullable: false,
      init: new qx.data.Array(['*'])
    },
  
    /**
     * A list of node types that are not allowed to be dragged
     */
    excludeDragTypes:
    {
      check: "qx.data.Array",
      nullable: false,
      init: new qx.data.Array()
    },
    
    /**
     * Drag action(s). If you supply an array, multiple drag actions will be added
     */
    dragAction:
    {
      nullable: false,
      init: "move",
      apply: "_applyDragAction"
    },
  
    /**
     * Saves the model data of the node(s) which was/were being dragged
     **/
    dragModel:
    {
      check: "qx.data.Array",
      nullable: true,
      init: null,
      event: "changeDragModel"
    },
  
    /**
     * the number of milliseconds between scrolling up a row if drag cursor
     * is on the first row or scrolling down if drag cursor is on last row
     * during a drag session. You can turn off this behaviour by setting this
     * property to null.
     **/
    autoScrollInterval:
    {
      check: "Number",
      nullable: true,
      init: 100
    },
    
    /**
     * whether it is possible to drop between nodes (i.e., for reordering them).
     * the focus indicator changed to a line to mark where the insertion should take place
     **/
    allowDropBetweenNodes:
    {
      check: "Boolean",
      init: true
    },
    
    /**
     * array of two-element arrays containing a combination of drag source and
     * drop target types. Type information is in the nodeTypeProperty of the
     * userData hash map. If null, allow any combination. "*" can be used to as a
     * wildcard, i.e. [ ['Foo','*'] ...] will allow the 'Foo' type node to be dropped on any
     * other type, and [ ['*','Bar'] ...] will allow any type to be dropped on a 'Bar' type node.
     * The array ['*'] will allow any combination, null will deny any drop.
     **/
    allowDropTypes:
    {
      check: "Array",
      nullable: true,
      init: null
    },
    
    /**
     * Saves the model data of node on which the drag objects has been dropped
     **/
    dropModel:
    {
      check: "Object",
      nullable: true,
      init: null,
      event: "changeDropModel"
    },
    
    /**
     * provide a hint on where the node has been dropped
     * (-1 = above the node, 0 = on the node, 1 = below the node)
     **/
    dropTargetRelativePosition:
    {
      check: [-1, 0, 1],
      init: 0
    },
    
    /**
     * Whether Drag & Drop should be limited to reordering
     */
    allowReorderOnly:
    {
      check: "Boolean",
      init: false,
      event: "changeAllowReorderOnly"
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
    }
    
  },
  
  /*
  *****************************************************************************
     EVENTS
  *****************************************************************************
  */
  events:
  {
    /**
     * Fired before a node is added to the tree. Returns the node, which
     * can be manipulated.
     */
    "beforeAddNode": "qx.event.type.Data",
    
    /**
     * Fired when a node is remove from tree. Returns the node.
     * Node will be deleted after event handling quits
     * Not yet implemented, override prune method
     */
    "beforeDeleteNode"   : "qx.event.type.Data",
    
    /**
     * Fired when a node changes the position. Returns an object:
     * {
     *    'node' : <the node which has changed position>
     *    'position' : <numeric position within the parent node's children> 
     * }
     */
    "changeNodePosition": "qx.event.type.Data"
  },
  
  
  /*
  *****************************************************************************
     STATICS
  *****************************************************************************
  */
  statics :{
    types : {
      TREEVIRTUAL : "qx/treevirtual-node"
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
    
    /**
     * The indicator widget
     */
    __indicator: null,
    
    /*
    ---------------------------------------------------------------------------
       INTERNAL METHODS
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
     * Create drop indicator
     * @todo Not working yet
     */
    _createIndicator: function () {
      this.__indicator = new qx.ui.core.Widget();
      this.__indicator.set({
        decorator: new qx.ui.decoration.Decorator(),
        zIndex: 100,
        height : 5,
        maxHeight : 5,
        anonymous : true,
        backgroundColor : "black",
        droppable: true,
      });
      this._hideIndicator();
      
      // don't add it to the DOM since it's not working yet
      //this._getPaneClipper().add(this.__indicator);
    },
    
    /**
     * Hide indicator
     */
    _hideIndicator: function () {
      //this.__indicator.setOpacity(0);
    },
    
    /**
     * Show indicator
     */
    _showIndicator: function () {
      //this.__indicator.setOpacity(0.5);
    },
  
    /**
     * Sets the position of the indicator relative to
     * the parent container.
     * @param x {Number}
     * @param y {Number}
     * @private
     */
    _setIndicatorPosition( x,y  ){
      //this.__indicator.setDomTop(y);
    },
    
    /**
     *
     * @return {qx.ui.table.pane.Clipper}
     * @private
     */
    _getPaneClipper: function () {
      return this._getTreePaneScroller().getPaneClipper();
    },
    
    /**
     * Returns the tree column pane scroller widget
     * @return {qx.ui.table.pane.Scroller}
     */
    _getTreePaneScroller: function () {
      let column = this.getDataModel().getTreeColumn();
      return this._getPaneScrollerArr()[column];
    },
    
    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
     */
  
    /**
     * Enables or disables drag and drop by adding or removing event listeners
     * @param value {Boolean}
     * @param old {Boolean}
     * @private
     */
    _applyEnableDragDrop: function (value, old) {
      if (old && !value) {
        this.setDraggable(false);
        this.setDroppable(false);
        this.removeListener("dragstart",    this.__onDragStart,   this);
        this.removeListener("drag",         this.__onDragHandler, this);
        this.removeListener("dragover",     this.__onDragOver,    this);
        this.removeListener("dragend",      this.__onDragEnd,     this);
        this.removeListener("dragleave",    this.__onDragEnd,     this);
        this.removeListener("dragchange",   this.__onDragChange,  this);
        this.removeListener("drop",         this.__onDrop,        this);
        this.removeListener("droprequest",  this.__onDropRequest, this);
        this.info("Table Drag & Drop disabled.");
      }
      
      if (value && !old) {
        this.addListener("dragstart",   this.__onDragStart,   this);
        this.addListener("dragover",    this.__onDragOver,    this); // dragover handler must be called *before* drag handler
        this.addListener("drag",        this.__onDragHandler, this);
        this.addListener("dragleave",   this.__onDragEnd,     this);
        this.addListener("dragend",     this.__onDragEnd,     this);
        this.addListener("dragchange",  this.__onDragChange,  this);
        this.addListener("drop",        this.__onDrop,        this);
        this.addListener("droprequest", this.__onDropRequest, this);
        this.setDraggable(true);
        this.setDroppable(true);
        this.info("Table Drag & Drop enabled.");
      }
    },
  
    /**
     * Applies the "dragAction" property
     * @param value {Boolean}
     * @param old {Boolean}
     * @private
     */
    _applyDragAction: function (value, old) {
      if (value !== "move") {
        this.error("Invalid drag action. Currently only 'move' is supported.");
      }
    },
    
    /*
    ---------------------------------------------------------------------------
       DRAG & DROP EVENT HANDLERS
    ---------------------------------------------------------------------------
    */
  
    /**
     * Handles event fired whem a drag session starts.
     * @param e {qx.event.type.Drag} the drag event fired
     */
    __onDragStart: function (e) {

      this.dragDebug("Drag start...");
      if( ! this._onDragStartImpl(e) ){
        this.dragDebug("_onDragStartImpl implementation returned false");
        return e.preventDefault();
      }
      // check if dragged node is selected
      let selection = this.getDataModel().getSelectedNodes();
      let row = this.__getDragCursorPositionData(e).row;
      let nodeData  = this.getDataModel().getRowData(row)[0];
      if( ! selection.includes(nodeData) ){
        this.dragDebug("Selecting drag target " + nodeData.label );
        this.getSelectionModel().resetSelection();
        this.getSelectionModel().setSelectionInterval(row, row);
        selection = [nodeData];
      }
      
      let includedDragTypes = this.getIncludeDragTypes();
      let excludedDragTypes = this.getExcludeDragTypes();
    
      // reasons to prevent drag start
      let reason = null;
      if (includedDragTypes.length === 0 )
        reason = "No drag types...";
      if (selection.length === 0)
        reason = "No selection...";
      // check drag type
      if (includedDragTypes.getItem(0) !== "*" || excludedDragTypes.length > 0) {
        for (let i = 0; i < selection.length; i++) {
          let type;
          try {
            type = selection[i].data.type;
          } catch (e) {
            this.error("Model in selection lacks data.type property");
          }
          // type is not among the allowed types, do not allow drag
          if ( ! includedDragTypes.contains(type) && includedDragTypes.getItem(0) !== "*" )
            reason = type + " is not in list of included types";
          if ( excludedDragTypes.contains(type) )
            reason = type + " is in list of excluded types";
        }
      }
      if( reason ){
        this.dragDebug(reason);
        this.__onDragEnd(e);
        e.preventDefault();
        return;
      }

      // save drag target for later
      this.setDragModel( new qx.data.Array(selection));
      // configure event
      e.addAction(this.getDragAction());
      e.addType(qcl.ui.treevirtual.DragDropTree.types.TREEVIRTUAL);
      e.addData(qcl.ui.treevirtual.DragDropTree.types.TREEVIRTUAL, selection);
    },
    
    /**
     * Fired when dragging over another widget.
     * @param e {qx.event.type.Drag} the drag event fired
     */
    __onDragOver: function (e) {
      this.dragDebug("Dragging over external widget...");
      // do not display an indicator if we have a related target,
      // i.e. we are not hovering over this wiget
      if (!e.getRelatedTarget()) {
        this.__onDragEnd(e);
      } else {
        this.__onDragAction(e);
      }
    },
  
    /**
     * Fired when dragging over the source widget.
     * @param e {qx.event.type.Drag} the drag event fired
     */
    __onDragHandler: function (e) {
      this.dragDebug("Dragging over drag source...");
      if (!e.getRelatedTarget()) {
        this.__onDragAction(e);
      } else {
        this.__onDragEnd(e);
      }
    },
    
    /**
     * Implementation of drag action for drag & dragover. This updates the drag cursor
     * and drag indicator (once implemented)
     * @param e {qx.event.type.Drag}
     */
    __onDragAction: function (e) {
      let positionData = this.__getDragCursorPositionData(e);
      let dropModel = null;
      let dropTargetRelativePosition = 0;
    
      // show indicator if we're within the available rows
      if (positionData.row < this.getDataModel().getRowCount()) {
        
        // auto-scroll at the beginning and at the end of the column
        this.__processAutoscroll(positionData);
      
        // show indicator and return the relative position
        dropTargetRelativePosition = this.__processDragInBetween(positionData);
      
        // check if the dragged item can be dropped at the current position
        dropModel = this.__getDropModel( e, dropTargetRelativePosition, positionData );
      }
      
      // save for later
      this.setDropModel(dropModel);
      this.setDropTargetRelativePosition(dropTargetRelativePosition);
      
      // set flag whether drop is allowed
      let validDropTarget = !!dropModel;
      e.getManager().setDropAllowed(validDropTarget);
      
      // show drag session visually
      if (validDropTarget) {
        // drag cursor
        qx.ui.core.DragDropCursor.getInstance().setAction(e.getCurrentAction());
        // open node after timeout
        if( this.__dragActionTimeout ){
          this.__dragActionTimeout.stop();
        }
        this.__dragActionTimeout = qx.event.Timer.once(()=>{
          this.getDataModel().setState(dropModel, {bOpened: true});
          this.getDataModel().setData();
        },this,500);
      }  else {
        qx.ui.core.DragDropCursor.getInstance().resetAction();
      }
    },
  
    /**
     * Handles the event that is fired when the user changes the mode of the drag during
     * the drag session
     * @param e {qx.event.type.Drag}
     * @private
     */
    __onDragChange : function(e){
      this.dragDebug("Drag change...");
      if( ! this._onDragChangeImpl(e) ){
        this.dragDebug("_onDragChangeImpl() returned false.");
        e.preventDefault();
      }
    },
  
    /**
     * Handles the event fired when a drag session ends (with or without drop).
     * @param e {qx.event.type.Drag}
     */
    __onDragEnd: function (e) {
      void(e);
      this.dragDebug("Drag end.");
      this._hideIndicator();
    },
  
    /**
     * Drop request handler. Calls the _onDropImpl method implementation.
     * @param e {qx.event.type.Drag}
     */
    __onDrop: function (e) {
      this.dragDebug("Successful drop.");
      this.__onDragEnd(e);
      this._onDropImpl(e);
    },
  
    /**
     * Called when a drop request is made
     * @param e {qx.event.type.Drag}
     * @private
     */
    __onDropRequest : function(e){
      this.dragDebug("Drop request");
      if( ! this._onDropRequestImpl(e) ){
        this.dragDebug("_onDropRequestImpl implementation returned false");
        e.preventDefault();
      }
    },
  
    /**
     * Check if the currently dragged nodes can be dropped on the currently hovered node.
     * If yes, the raw model data of this node will be returned, otherwise null
     * @param e {qx.event.type.Drag}
     * @param dropTargetRelativePosition {Integer}
     * @param positionData {Map}
     * @return {Object|null}
     */
    __getDropModel: function (e, dropTargetRelativePosition, positionData) {
      // validation
      let dragModelArr = this.getDragModel();
      let dropModelRowData = this.getDataModel().getRowData(positionData.row);
      let dropModel = dropModelRowData[0];
    
      // iterate through all of the dragged models to see if
      // they match the drop target model
      let validDropTarget = dragModelArr.every(dragModel => {
        // Whether drag & drop is limited to reordering
        if (this.isAllowReorderOnly()) {
          if (dropTargetRelativePosition === 0) {
            this.dragDebug("Reordering only and dropped on node");
            return false;
          }
          if (dropModel.level !== dragModel.level) {
            this.dragDebug("Reordering only and dropped on/between subnodes");
            return false;
          }
        }
  
        // if we are dragging within the same widget
        if ( e.getCurrentTarget() === this ) {
          // prevent drop of nodes on themself
          if ( dragModel.nodeId === dropModel.nodeId) {
            this.dragDebug("Drop on itself not allowed.");
            return false;
          }
    
          // prevent drop of parents on children
          let traverseNode = dropModel;
          while (traverseNode.parentNodeId) {
            if (traverseNode.parentNodeId === dragModel.nodeId) {
              this.dragDebug("Drop on subnode not allowed.");
              return false;
            }
            traverseNode = this.nodeGet(traverseNode.parentNodeId);
          }
        }
  
        // if we're in between nodes, but have the same parent, ok
        // @todo why?
        if (dropTargetRelativePosition !== 0) {
          if (dragModel.parentNodeId === dropModel.parentNodeId) {
            return true;
          }
        }
  
        // get allowed drop types. disallow drop if none
        let allowDropTypes = this.getAllowDropTypes();
        if (!allowDropTypes) {
          this.dragDebug("No allowDropTypes.");
          return false;
        }
  
        // everything can be dropped, allow
        if (allowDropTypes[0] === "*") {
          return true;
        }
  
        // check legitimate source and target type combinations
        let sourceType = this.getNodeDragType(dragModel);
        let targetTypeNode = (dropTargetRelativePosition !== 0)
          ? this.nodeGet(dropModel.parentNodeId)
          : dropModel;
        let targetType = this.getNodeDragType(targetTypeNode);
  
        if (!targetType) {
          this.dragDebug("No target type.");
          return false;
        }
  
        for (let i = 0; i < allowDropTypes.length; i++) {
          if (
          (allowDropTypes[i][0] === sourceType || allowDropTypes[i][0] === "*") &&
          (allowDropTypes[i][1] === targetType || allowDropTypes[i][1] === "*")
          ) {
            return true;
          }
        }
        // do not allow any drop
        this.dragDebug("No matching allowDropType!");
        return null;
      });
      
      return validDropTarget ? dropModel : null;
    },
  
    /**
     * Handle behavior connected to automatic scrolling at the top and the
     * bottom of the tree
     *
     * @param dragDetails {Map}
     */
    __processAutoscroll: function (dragDetails) {
      let interval = this.getAutoScrollInterval();
      let details = dragDetails;
    
      if (interval) {
        let scroller = this._getTreePaneScroller();
      
        if (!this.__scrollFunctionId && (details.topDelta > -1 && details.topDelta < 2) && details.row !== 0) {
          // scroll up if drag cursor at the top
          this.__scrollFunctionId = window.setInterval(function () {
            scroller.setScrollY(parseInt(scroller.getScrollY()) - details.rowHeight);
          }, interval);
        }
        else if (!this.__scrollFunctionId && (details.bottomDelta > 0 && details.bottomDelta < 3)) {
          // scroll down if drag cursor is at the bottom
          this.__scrollFunctionId = window.setInterval(function () {
            scroller.setScrollY(parseInt(scroller.getScrollY()) + details.rowHeight);
          }, interval);
        }
        else if (this.__scrollFunctionId) {
          window.clearInterval(this.__scrollFunctionId);
          this.__scrollFunctionId = null;
        }
      }
    },
  
    /**
     * Handle the bahavior of the indicator in between tree nodes
     * @param dragDetails {Map}
     * @return {Integer}
     */
    __processDragInBetween: function (dragDetails) {
      let result = 0;
      if (this.getAllowDropBetweenNodes()) {
        if (dragDetails.deltaY < 4 || dragDetails.deltaY > (dragDetails.rowHeight - 4)) {
          if (dragDetails.deltaY < 4) {
            this._setIndicatorPosition(0,(dragDetails.row - dragDetails.firstRow) * dragDetails.rowHeight - 2);
            result = -1;
          }
          else {
            this._setIndicatorPosition(0,(dragDetails.row - dragDetails.firstRow + 1) * dragDetails.rowHeight - 2);
            result = 1;
          }
          this._showIndicator();
        }
        else {
          this._setIndicatorPosition(0,-1000);
          this._hideIndicator();
        }
      }
    
      return result;
    },
  
    /**
     * Calculate indicator position and display indicator
     * @param e {qx.event.type.Drag}
     * @return {Map}
     */
    __getDragCursorPositionData: function (e) {
      // pane scroller widget takes care of mouse events
      let scroller = this._getTreePaneScroller();
    
      // calculate row and mouse Y position within row
      let paneClipperElem = this._getPaneClipper().getContentElement().getDomElement();
      let paneClipperTopY = qx.bom.element.Location.get(paneClipperElem, "box").top;
      let rowHeight = scroller.getTable().getRowHeight();
      let scrollY = parseInt(scroller.getScrollY());
      if (scroller.getTable().getKeepFirstVisibleRowComplete()) {
        scrollY = Math.floor(scrollY / rowHeight) * rowHeight;
      }
    
      let tableY = scrollY + e.getDocumentTop() - paneClipperTopY;
      let row = Math.floor(tableY / rowHeight);
      let deltaY = tableY % rowHeight;
    
      // calculate relative row position in table
      let firstRow = scroller.getChildControl("pane").getFirstVisibleRow();
      let rowCount = scroller.getChildControl("pane").getVisibleRowCount();
      let lastRow = firstRow + rowCount;
      //let scrollY     = parseInt(scroller.getScrollY());
      let topDelta = row - firstRow;
      let bottomDelta = lastRow - row;
    
      return {
        rowHeight: rowHeight,
        row: row,
        deltaY: deltaY,
        firstRow: firstRow,
        topDelta: topDelta,
        bottomDelta: bottomDelta
      };
    },
  
  
    /*
    ---------------------------------------------------------------------------
       overridable stubs
    ---------------------------------------------------------------------------
     */
  
    /**
     * Returns true if drag is allowed.
     * Override this method to check for additional condition.
     * @param e {qx.event.type.Drag}
     * @return {boolean}
     */
    _onDragStartImpl : function(e){
      void(e);
      return true;
    },
  
  
    /**
     * Returns true if drag change is allowed.
     * Override this method to check for additional condition.
     * @param e {qx.event.type.Drag}
     * @return {boolean}
     */
    _onDragChangeImpl : function(e){
      void(e);
      return true;
    },
  
    /**
     * Returns true if drop is allowed on the currently hovered node.
     * Override this method to check for additional conditions.
     * @param e {qx.event.type.Drag}
     * @return {boolean}
     */
    _onDropRequestImpl : function(e){
      void(e);
      return true;
    },
  
  
    /**
     * Override this method to do something with successful drop
     * @param e {qx.event.type.Drag}
     * @return {void}
     */
    _onDropImpl : function(e){
      void(e);
      this.warn("_onDropImpl() should be overridden");
    },
    
    
    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
     */
    
    /**
     * Move the dragged node from the source to the target node. Takes
     * the drag even received by the "drop" even handler.
     * @param  e {qx.event.type.Drag}
     */
    moveNode: function (e) {
      let action = e.getCurrentAction() || "move";
      let dropModel = this.getDropModel();
      let dropPosition = this.getDropTargetRelativePosition();
      if (!qx.lang.Type.isObject(dropModel)) {
        this.dragDebug("No valid drop target.");
        return false;
      }
      this.dragDebug("Moving node...");
      
      // this method only supports treevirtual nodes
      if (e.supportsType(qcl.ui.treevirtual.DragDropTree.types.TREEVIRTUAL)) {
        if (!dropModel.children) {
          this.dragDebug("Drop target is not a folder.");
          return false;
        }
        
        // check action - only moving nodes is supported inside the tree
        if (action !== "move") {
          this.dragDebug("Only the 'move' action is supported.");
          return false;
        }
        
        // dragged nodes
        let nodes = e.getData(qcl.ui.treevirtual.DragDropTree.types.TREEVIRTUAL);
        if (!qx.lang.Type.isArray(nodes)) {
          this.error("No dragged node data");
          return false;
        }
        
        // move nodes
        let nodeArr = this.getDataModel().getData();
        for (let i = 0, l = nodes.length; i < l; i++) {
          let node = nodes[i];
          
          // remove from parent node of dropped node
          let parentNode = nodeArr[node.parentNodeId];
          if (!parentNode) this.error("Cannot find the dropped node's parent node!");
          let pnc = parentNode.children;
          pnc.splice(pnc.indexOf(node.nodeId), 1);
          let position;
          
          // drop on the node itself: add to the children of the target node
          if (dropPosition === 0) {
            position = dropModel.children;
            dropModel.children.push(node.nodeId);
            node.parentNodeId = dropModel.nodeId;
            this.fireDataEvent("changeNodePosition", {
              'node': node,
              'position': position
            });
          }
          
          // drop between nodes: add as a sibling of the drop target
          else if (this.getAllowDropBetweenNodes()) {
            let targetParentNode = nodeArr[dropModel.parentNodeId];
            if (!targetParentNode) this.error("Cannot find the target node's parent node!");
            let tpnc = targetParentNode.children;
            let delta = dropPosition > 0 ? 1 : 0;
            position = tpnc.indexOf(dropModel.nodeId) + delta;
            tpnc.splice(position, 0, node.nodeId);
            node.parentNodeId = targetParentNode.nodeId;
            this.fireDataEvent("changeNodePosition", {
              'node': node,
              'position': position
            });
          }
          // else, we have a logic error
          else {
            this.error("Dropping in between nodes is not allowed!");
          }
        }
        
        // re-render the tree
        this.getDataModel().setData();
      }
    },
    
    /**
     * Creates an empty branch (=folder) object. This should really
     * be part of the data model.
     * @return {Object}
     */
    createBranch: function (label, icon) {
      return {
        type: qx.ui.treevirtual.SimpleTreeDataModel.Type.BRANCH,
        nodeId: null, // must be set
        parentNodeId: null, // must be set
        label: label,
        bSelected: false,
        bOpened: false,
        bHideOpenClose: false,
        icon: icon,
        iconSelected: icon,
        children: [],
        columnData: []
      };
    },
    
    /**
     * Creates an empty leaf object. This should really
     * be part of the data model.
     * @return {Object}
     */
    createLeaf: function (label, icon) {
      let node = this.createBranch(label, icon);
      node.type = qx.ui.treevirtual.SimpleTreeDataModel.Type.LEAF;
      return node;
    },
    
    /**
     * Imports a node into the tree at the current drop position. Takes
     * the drag even received by the "drop" even handler and an array of
     * node data. Make sure that the node data is valid, since it is not
     * checked. You can create an empty node using the createBranch() and
     * createLeaf() methods.
     *
     * @param e {qx.event.type.Drag}
     * @param nodes {Object[]} Array of node data.
     */
    importNode: function (e, nodes) {
      let dropModel = this.getDropModel();
      let dropPosition = this.getDropTargetRelativePosition();
      
      if (!qx.lang.Type.isObject(dropModel)) {
        //this.warn("No valid drop target!");
        return false;
      }
      
      if (!dropModel.children) {
        this.error("Drop target is not a folder!");
        return false;
      }
      
      if (!qx.lang.Type.isArray(nodes)) {
        this.error("Invalid nodes data");
        return false;
      }
      
      // move nodes
      let nodeArr = this.getDataModel().getData();
      
      for (let i = 0, l = nodes.length; i < l; i++) {
        // import the node into the tree's node array
        let node = nodes[i];
        node.nodeId = nodeArr.length;
        nodeArr.push(node);
        
        // drop on the node itself: add to the children of the target node
        if (dropPosition === 0) {
          dropModel.children.push(node.nodeId);
          node.parentNodeId = dropModel.nodeId;
        }
        else if (this.getAllowDropBetweenNodes()) {
          // drop between nodes: add as a sibling of the drop target
          let targetParentNode = nodeArr[dropModel.parentNodeId]
          if (!targetParentNode) this.error("Cannot find the target node's parent node!");
          let tpnc = targetParentNode.children;
          let delta = dropPosition > 0 ? 1 : 0;
          tpnc.splice(tpnc.indexOf(dropModel.nodeId) + delta, 0, node.nodeId);
          node.parentNodeId = targetParentNode.nodeId;
        } else {
          this.error("Dropping in between nodes is not allowed!");
        }
      }
      
      this.fireDataEvent("beforeAddNode", node);
      
      // re-render the tree
      this.getDataModel().setData();
    },
    
    
    /**
     * gets the (drag) type of a node
     * @param nodeReference {Object|Integer}
     * @return {Object} the user-supplied type of the node or null if not set
     */
    getNodeDragType: function (nodeReference) {
      try {
        if (typeof nodeReference === "object") {
          return nodeReference.data.type;
        }
        else if (typeof nodeReference === "number"){
          return this.nodeGet(nodeReference).data.type;
        }
      }
      catch (e) {
        this.warn("Invalid node reference or node data");
        return null;
      }
    },
    
    /**
     * sets the (drag) type of a node
     * @param nodeReference {Object|Integer}
     * @param type {String}
     */
    setNodeDragType: function (nodeReference, type) {
      if (typeof type !== "string") {
        this.error("Drag Type must be a string, got " + (typeof type));
      }
      let node = this.nodeGet(nodeReference);
      if (!node.data) {
        node.data = {};
      }
      node.data.type = type;
    }
  }
});