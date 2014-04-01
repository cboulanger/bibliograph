/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2010 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
   *  saaj <mail@saaj.me>
  
************************************************************************ */

/**
 * Provides drag&drop to TreeVirtual. Currently, only the "move" action is
 * supported.
 */
qx.Class.define("qcl.ui.treevirtual.DragDropTree", 
{

  extend  : qx.ui.treevirtual.TreeVirtual,
  include : [qx.ui.treevirtual.MNode],

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */  
  construct : function(headings, custom)
  {
    this._patchCodebase();

    custom = !custom ? {} : custom;
    custom.tablePaneHeader = function(obj)
    {
      /*
       * This is workaround for disabling draggable tree column.
       * Also i could not override it by setting Scroller
       * obj is tablePaneScroller
       */
      var stub                   = function () {};
      obj._onChangeCaptureHeader = stub;
      obj._onMousemoveHeader     = stub;
      obj._onMousedownHeader     = stub;
      obj._onMouseupHeader       = stub;
      obj._onClickHeader         = stub;

      return new qx.ui.table.pane.Header(obj);
    };

    this.base(arguments, headings, custom);

    this.setAllowDragTypes(["*"]);
    this.setAllowDropTypes(["*"]);
    
    this._createIndicator();
  },
  
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties : 
  {

    /**
     * Enable/disable drag and drop
     */
    enableDragDrop :
    {
      check : "Boolean",
      apply : "_applyEnableDragDrop",
      event : "changeEnableDragDrop",
      init  : false
    },

    /**
     * a list of node types allowed to be dragged
     */
    allowDragTypes :
    {
      check    :  "Array",
      nullable : true,
      init     : null
    },

    /**
     * drag action(s). If you supply an array, multiple drag actions will be added
     */
    dragAction :
    {
      nullable : false,
      init     : "move",
      apply    : "_applyDragAction"
    },

    /**
     * the number of milliseconds between scrolling up a row if drag cursor
     * is on the first row or scrolling down if drag cursor is on last row
     * during a drag session. You can turn off this behaviour by setting this
     * property to null.
     **/
    autoScrollInterval :
    {
      check    :  "Number",
      nullable : true,
      init     : 100
    },

    /**
     * whether it is possible to drop between nodes (i.e., for reordering them).
     * the focus indicator changed to a line to mark where the insertion should take place
     **/
    allowDropBetweenNodes :
    {
      check : "Boolean",
      init  : true
    },

    /**
     * array of two-element arrays containing a combination of drag source and
     * drop target types. Type information is in the nodeTypeProperty of the
     * userData hash map. If null, allow any combination. "*" can be used to as a
     * wildcard, i.e. [ ['Foo','*'] ...] will allow the 'Foo' type node to be dropped on any
     * other type, and [ ['*','Bar'] ...] will allow any type to be dropped on a 'Bar' type node.
     * The array ['*'] will allow any combination, null will deny any drop.
     **/
    allowDropTypes :
    {
      check    : "Array",
      nullable : true,
      init     : null
    },

    /**
     * records the target node on which the drag objects has been dropped
     **/
    dropTarget :
    {
      check    : "Object",
      nullable : true,
      init     : null
    },

    /**
     * provide a hint on where the node has been dropped
     * (-1 = above the node, 0 = on the node, 1 = below the node)
     **/
    dropTargetRelativePosition :
    {
      check : [-1, 0, 1],
      init  : 0
    },
    
    /**
     * Whether Drag & Drop should be limited to reordering
     */
    allowReorderOnly : 
    {
      check : "Boolean",
      init : false,
      event : "changeAllowReorderOnly"
    }

  },
  
  /*
  *****************************************************************************
     EVENTS
  *****************************************************************************
  */
  events : 
  {
    /**
     * Fired before a node is added to the tree. Returns the node, which
     * can be manipulated.
     */
    "beforeAddNode" : "qx.event.type.Data",
    
    /**
     * Fired when a node is remove from tree. Returns the node.
     * Node will be deleted after event handling quits
     * Not yet implemented, override prune method
     */
    //"beforeDeleteNode"   : "qx.event.type.Data",
    
    /**
     * Fired when a node changes the position. Returns an object: 
     * {
     *    'node' : <the node which has changed position>
     *    'position' : <numeric position within the parent node's children> 
     * }
     */
    "changeNodePosition" : "qx.event.type.Data"
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
    
    /**
     * The indicator widget
     */
    _indicator : null,
    
   /*
   ---------------------------------------------------------------------------
      INTERNAL METHODS
   ---------------------------------------------------------------------------
    */    

    /**
     * Patch the codebase to make drag & drop in the table possible in
     * the first place
     * FIXME get rid of patches
     */
    _patchCodebase : function()
    {
      qx.Class.include(qx.ui.treevirtual.TreeVirtual, qx.ui.treevirtual.MNode);
      
      /* 
       * The __dropTarget property is not properly initialized int the TreeVirtual
       * widget for some reason, and is therefore often not available in the
       * __onMouseMove() method.  A call to _onMouseOver(e) seems to fix that.
       * Also, when dragging into the tree from a different widget, the drag 
       * cursor is not updated. For this, _onMouseOut(e) has to be called.
       * Don't really understand why, but it works this way.
       */
      var _onMouseMove = qx.event.handler.DragDrop.prototype._onMouseMove;
      qx.event.handler.DragDrop.prototype._onMouseMove = function(e){
        this._onMouseOut(e);
        this._onMouseOver(e);
        _onMouseMove.call(this,e);
      }

      /*
       * have not found official way to set validness check for events within widget.
       * this only works with private optimization turned off
       */
      qx.event.handler.DragDrop.prototype.setValidDrop = function(value)
      {
        this.__validDrop = !!value;
      };

      /*
       * sanitize the api access required; this only works if private
       * optimization is turned off.
       */
      qx.ui.table.pane.Scroller.prototype.getPaneClipper = function()
      {
        return this.__paneClipper;
      };
      qx.ui.table.pane.Scroller.prototype.getRowForPagePos = function(pageX, pageY)
      {
        return this._getRowForPagePos(pageX, pageY);
      };
    },

    /**
     * Create drop indicator
     */
    _createIndicator : function()
    {
      this._indicator = new qx.ui.core.Widget();
      this._indicator.set({
        decorator  : new qx.ui.decoration.Single().set({top : [2, "solid", "#333"]}),
        zIndex     : 100,
        droppable  : true
      });
      this._hideIndicator();

      this._getPaneClipper().add(this._indicator);
    },

    /**
     * Hide indicator
     */
    _hideIndicator : function()
    {
      this._indicator.setOpacity(0);
    },

    /**
     * Show indicator
     */
    _showIndicator : function()
    {
      this._indicator.setOpacity(0.5);
    },

    /**
     * Check if drag element can be dropped
     * @param sourceData {Map} 
     * @param dropTargetRelativePosition {Integer}
     * @param dragDetails {Map}
     * @return {Boolean}
     */
    _checkDroppable : function(sourceData, dropTargetRelativePosition, dragDetails)
    {
      /*
       * get and save drag target
       */
      var targetWidget  = this;
      var targetRowData = this.getDataModel().getRowData(dragDetails.row);
      if( ! targetRowData )
      {
        //this.debug("No targetRowData");
        return false;
      }

      var targetNode = targetRowData[0];
      if( ! targetNode )
      {
        //this.debug("No targetNode");
        return false;
      }

      var targetParentNode = this.nodeGet(targetNode.parentNodeId);
      this.setDropTarget(targetNode);
      this.setDropTargetRelativePosition(dropTargetRelativePosition);
     
      /*
       * @todo the following has to be rewritten to work without the 
       * sourceData var. we should be able to get everything from the
       * event data.
       */
      if( ! sourceData )
      {
        // we do not have any compatible datatype
        //this.debug("No sourceData");
        return false;
      }

      /*
       * use only the first node to determine node type
       */
      var sourceNode = sourceData.nodeData[0];
      if( ! sourceNode )
      {
        /*
         * no node to drag
         */
        //this.debug("No sourceNode");
        return false;
      }

      /*
       * Whether drag & drop is limited to reordering
       */
      if ( this.isAllowReorderOnly() )
      {
        if ( dropTargetRelativePosition === 0 )
        {
          //this.debug("Reordering only and dropped on node");
          return false;  
        }
        if ( targetNode.level !== sourceNode.level )
        {
          //this.debug("Reordering only and dropped on/between subnodes");
          return false;  
        }        
      }            
      
      var sourceWidget = sourceData.sourceWidget;      

      /*
       * if we are dragging within the same widget
       */
      if(sourceWidget == targetWidget)
      {
        /*
         * prevent drop of nodes on themself
         */
        if(sourceNode.nodeId == targetNode.nodeId)
        {
          //this.debug("Drop on itself");
          return false;
        }

        /*
         * prevent drop of parents on children
         */
        var traverseNode = targetNode;
        while(traverseNode.parentNodeId)
        {
          if( traverseNode.parentNodeId == sourceNode.nodeId )
          {
            //this.debug("Drop on subnode");
            return false;
          }
          traverseNode = this.nodeGet(traverseNode.parentNodeId);
        }
      }
      
      // ??
      if ( dropTargetRelativePosition != 0 ) 
      {
        if ( sourceNode.parentNodeId == targetNode.parentNodeId)
        {
          return true;
        }
      }

      /*
       * get allowed drop types. disallow drop if none
       */
      var allowDropTypes = this.getAllowDropTypes();
      if ( ! allowDropTypes )
      {
        //this.debug("No allowDropTypes!");
        return false;
      }

      /*
       * everything can be dropped, allow
       */
      if( allowDropTypes[0] == "*" )
      {
        return true;
      }      

      /*
       * check legitimate source and target type combinations
       */
      var sourceType     = this.getNodeDragType(sourceNode);
      var targetTypeNode = (dropTargetRelativePosition != 0) 
                            ? targetParentNode : targetNode;
      var targetType     = this.getNodeDragType(targetTypeNode);

      if ( ! targetType)
      {
        //this.debug("No target type!");
        return false;
      }

      for(var i = 0; i < allowDropTypes.length; i++)
      {
        if(
          (allowDropTypes[i][0] == sourceType || allowDropTypes[i][0] == "*") &&
          (allowDropTypes[i][1] == targetType || allowDropTypes[i][1] == "*" )
        )
        {
          return true;
        }
      }

      /*
       * do not allow any drop
       */
      //this.debug("No matching allowDropType!");
      return false;
    },

    /**
     * Handle behavior connected to automatic scrolling at the top and the
     * bottom of the tree
     * 
     * @param dragDetails {Map}
     */
    _processAutoscroll : function(dragDetails)
    {
      var interval = this.getAutoScrollInterval();
      var details  = dragDetails;

      if(interval)
      {
        var scroller = this._getTreePaneScroller();

        if(!this.__scrollFunctionId && (details.topDelta > -1 && details.topDelta < 2) && details.row != 0)
        {
          // scroll up if drag cursor at the top
          this.__scrollFunctionId = window.setInterval(function()
          {
            scroller.setScrollY(parseInt(scroller.getScrollY()) - details.rowHeight);
          }, interval);
        }
        else if(!this.__scrollFunctionId && (details.bottomDelta > 0 && details.bottomDelta < 3))
        {
          // scroll down if drag cursor is at the bottom
          this.__scrollFunctionId = window.setInterval(function()
          {
            scroller.setScrollY( parseInt(scroller.getScrollY()) + details.rowHeight);
          }, interval);
        }
        else if(this.__scrollFunctionId)
        {
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
    _processDragInBetween : function(dragDetails)
    {
      var result = 0;
      if( this.getAllowDropBetweenNodes() )
      {
        if(dragDetails.deltaY < 4 || dragDetails.deltaY > (dragDetails.rowHeight - 4))
        {
          if(dragDetails.deltaY < 4)
          {
            this._indicator.setDomTop((dragDetails.row - dragDetails.firstRow) * dragDetails.rowHeight - 2);
            result = -1;
          }
          else
          {
            this._indicator.setDomTop((dragDetails.row - dragDetails.firstRow + 1 ) * dragDetails.rowHeight - 2);
            result = 1;
          }
          this._showIndicator();
        }
        else
        {
          this._indicator.setDomTop(-1000);
          this._hideIndicator();
        }
      }

      return result;
    },
    
   /**
     * Calculate indicator position and display indicator
     * @param dragEvent {} 
     * @return {Map}
     */
    _getDragDetails : function(dragEvent)
    {
      // pane scroller widget takes care of mouse events
      var scroller = this._getTreePaneScroller();

      // calculate row and mouse Y position within row
      var paneClipperElem = this._getPaneClipper().getContentElement().getDomElement();
      var paneClipperTopY = qx.bom.element.Location.get(paneClipperElem, "box").top;
      var rowHeight       = scroller.getTable().getRowHeight();
      var scrollY         = scroller.getScrollY();
      if(scroller.getTable().getKeepFirstVisibleRowComplete())
      {
        scrollY = Math.floor(scrollY / rowHeight) * rowHeight;
      }

      var tableY = scrollY + dragEvent.getDocumentTop() - paneClipperTopY;
      var row    = Math.floor(tableY / rowHeight);
      var deltaY = tableY % rowHeight;

      // calculate relative row position in table
      var firstRow    = scroller.getChildControl("pane").getFirstVisibleRow();
      var rowCount    = scroller.getChildControl("pane").getVisibleRowCount();
      var lastRow     = firstRow + rowCount;
      var scrollY     = parseInt(scroller.getScrollY());
      var topDelta    = row - firstRow;
      var bottomDelta = lastRow - row;

      return {
        rowHeight   : rowHeight,
        row         : row,
        deltaY      : deltaY,
        firstRow    : firstRow,
        topDelta    : topDelta,
        bottomDelta : bottomDelta
      };
    },        
    
//    /**
//     * Returns information on the drag session after the drop has occurred
//     * @param event {Object} the drag event fired
//     * @return {Object} map with the following information:
//     * {
//     *  'nodeData' : an array of selected nodes in the source widget, i.e. those nodes which were dragged,
//     *  'sourceWidget' : the source widget,
//     *  'targetNode' : the node on which the data was dropped,
//     *  'position' : the relative position of the drop action: -1 = above, 0=on, 1= below the node
//     * }
//     * @todo this method should not be necessary and will be removed. All information
//     * can be gathered by using event and widget properties.
//     * @deprecated
//     */
//    _getDropData : function(event)
//    {
//      var dragData = event.getUserData("treevirtualnode");
//      return {
//        'nodeData'     : dragData.nodeData,
//        'sourceWidget' : dragData.sourceWidget,
//        //don't use event.getCurrentAction() 'cause event looses action sometimes on dragchange event
//        'action'       : dragData.action,
//        'targetNode'   : this.getDropTarget(),
//        'position'     : this.getDropTargetRelativePosition()
//      };
//    },    
    
    _getPaneClipper : function()
    {
      return this._getTreePaneScroller().getPaneClipper();
    },
    
    /**
     * get tree column pane scroller widget
     */
    _getTreePaneScroller : function()
    {
      var column = this.getDataModel().getTreeColumn();
      return this._getPaneScrollerArr()[column];
    },    
    
     /*
     ---------------------------------------------------------------------------
        APPLY METHODS
     ---------------------------------------------------------------------------
      */    

    /**
     * enables or disables drag and drop, adds event listeners
     */
    _applyEnableDragDrop : function(value, old)
    {
      if( old && ! value )
      {
        this.setDraggable(true);
        this.setDroppable(true);
        this.removeListener("dragstart",    this.__onDragStart,   this);
        this.removeListener("drag",         this.__onDrag,        this);
        this.removeListener("dragover",     this.__onDragOver,    this);
        this.removeListener("dragend",      this.__onDragEnd,     this);
        this.removeListener("dragleave",    this.__onDragEnd,     this);
        this.removeListener("droprequest",  this.__onDropRequest, this);
        
      }

      if( value && ! old )
      {
        this.addListener("dragstart",   this.__onDragStart,   this);
        this.addListener("dragover",    this.__onDragOver,    this); // dragover must be called *before* drag
        this.addListener("dragleave",   this.__onDragEnd,     this);
        this.addListener("drag",        this.__onDrag,        this);
        this.addListener("dragend",     this.__onDragEnd,     this);
        this.addListener("droprequest", this.__onDropRequest, this);
        this.setDraggable(true);
        this.setDroppable(true);     
      }
    },
    
    _applyDragAction : function( value, old )
    {
      if ( value !== "move" )
      {
        this.error("Invalid drag action. Currently only 'move' is supported.");
      }
    },
    
    /*
    ---------------------------------------------------------------------------
       EVENT HANDLERS
    ---------------------------------------------------------------------------
     */

    /**
     * Handles event fired whem a drag session starts.
     * @param event {Object} the drag event fired
     */
    __onDragStart : function(event)
    {
      
      var selection = this.getDataModel().getSelectedNodes();
      var types     = this.getAllowDragTypes();
      
      /*
       * no drag types, no drag is allowed
       */
      if( types === null )
      {
        return event.preventDefault();
      }
      
      /*
       * check drag type
       */
      if( types[0] != "*" )
      {
        /*
         * check for allowed types for all of the selection, i.e. if one
         * doesn't match, drag is not allowed.
         */
        for(var i = 0; i < selection.length; i++)
        {
          var type = null;
          try
          {
            type = selection[i].data.DragDrop.type;
          }
          catch(e) {}

          /*
           * type is not among the allowed types, do not allow drag
           */
          if(types.indexOf(type) < 0)
          {
            return event.preventDefault();
          }
        }
      }

      // prepare drag data, old style
      var dragData = {
        'nodeData'     : selection,
        'sourceWidget' : this,
        'action'       : this.getDragAction()
      };
      event.setUserData("treevirtualnode", dragData);
      
      /*
       * drag data, new style
       */
      event.addAction(this.getDragAction());
      event.addType("qx/treevirtual-node");
    },
    
    /**
     * Handles the event fired when a drag session ends (with or without drop).
     */
    __onDragEnd : function(e)
    {
      this._hideIndicator(); 
    },    
    
    /**
     * Fired when dragging over another widget. You'll need to attach
     * this
     * @param event {qx.event.type.Drag} the drag event fired
     */
    __onDragOver : function(e)
    {
      /*
       * do not display an indicator if we have a related target,
       * i.e. we are not hovering over this wiget
       */
      if ( ! e.getRelatedTarget() )
      {
        this.__onDragEnd(e);
      }
      else
      {
        this.__onDragAction(e);
      }
      return;
    },    

    /**
     * Fired when dragging over the source widget. 
     * Provides a check on whether drop is allowed, displaying a 
     * insertion cursor for drop-between-nodes.
     * 
     * @param event {qx.event.type.Drag} the drag event fired
     * @param forceDisplayIndicator {Boolean} Internal use only
     */
    __onDrag : function(e)
    {
      if ( ! e.getRelatedTarget() )
      {
        this.__onDragAction(e);
      }
      else
      {
        this.__onDragEnd(e);
      }      
    },
    
    /**
     * Implementation of drag action for drag & dragover
     * @param {} e
     */
    __onDragAction : function(e)
    {
      var target = e.getTarget();      
      var sourceData  = e.getUserData("treevirtualnode");
      var dragDetails = this._getDragDetails(e);
      var valid = false;
      
      /*
       * show indicator if we're within the available rows
       */
      if ( dragDetails.row < this.getDataModel().getRowCount() )
      {
        /*
         * auto-scroll at the beginning and at the end of the column
         */
        this._processAutoscroll( dragDetails );
        
        /*
         * show indicator and return the relative position
         */
        var dropTargetRelativePosition = 
          this._processDragInBetween( dragDetails );
  
        /*
         * check if the dragged item can be dropped at the current
         * position and change drag cursor accordingly
         */
        var valid = this._checkDroppable(
          sourceData, dropTargetRelativePosition, dragDetails
        );
      }
     
      /*
       * set flag whether drop is allowed
       */
      e.getManager().setValidDrop(valid);
      
      /*
       * drag curson
       */
      if(valid)
      {
        qx.ui.core.DragDropCursor.getInstance().setAction(e.getCurrentAction());
      }
      else
      {
        qx.ui.core.DragDropCursor.getInstance().resetAction();
      }
      //this.debug([e.getType(), dropTargetRelativePosition, valid, sourceData.action]);
    },
    
    /**
     * Drop request handler
     * @param e {qx.event.type.Drag}
     */
    __onDropRequest :  function(e)
    {
      this.__onDragEnd(e);
      var action = e.getCurrentAction();
      var type   = e.getCurrentType();
      var source = e.getCurrentTarget();
      
      if (type === "qx/treevirtual-node")
      {
        /*
         * make a copy of the selection
         */
        var selection = this.getSelectedNodes();
        var copy = [];
        for (var i=0, l=selection.length; i<l; i++) 
        {
          if ( ! qx.lang.Type.isObject( selection[i] ) )
          {
            continue;
          }
          copy[i] = selection[i];
        }        
        
        /*
         * remove selection
         */
        this.getSelectionModel().resetSelection();
        
        /*
         * Add data to manager
         */
        if ( copy.length )
        {
          e.addData(type, copy);  
        }
          
        
        return;
      }
      
      this.error("Invalid type '" + type + "'");
    
    },
    
  
    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
     */
    
    
    /**
     * Move the dragged node from the source to the target node. Takes
     * the drag even received by the "drop" even handler
     * @param  e {qx.event.type.Drag}
     */
    moveNode : function(e)
    {
      var action        = e.getCurrentAction() || "move";
      var dropTarget    = this.getDropTarget();
      var dropPosition  = this.getDropTargetRelativePosition();
      
      if( ! qx.lang.Type.isObject(dropTarget) )
      {
        //this.warn("No valid drop target!");
        return false;
      }
      
      /*
       * this method only supports treevirtual nodes
       */
      if ( e.supportsType("qx/treevirtual-node") )
      {
        if( ! dropTarget.children )
        {
          this.error("Drop target is not a folder!");
          return false;
        }
        
        /*
         * check action - only moving nodes is supported inside the
         * tree
         */
        if( action !== "move" )
        {
          this.error("Only the 'move' action is supported.");
          return false;
        }
        
        /*
         * dragged nodes
         */
        var nodes = e.getData("qx/treevirtual-node");
        if ( ! qx.lang.Type.isArray( nodes) )
        {
          this.error("No dragged node data");
          return false;
        }
        
        /*
         * move nodes
         */
        var nodeArr = this.getDataModel().getData();
        for (var i=0, l=nodes.length; i<l; i++) 
        { 
          var node = nodes[i];
          
          /*
           * remove from parent node of dropped node
           */
          var parentNode = nodeArr[node.parentNodeId];
          if( ! parentNode ) this.error("Cannot find the dropped node's parent node!");
          var pnc = parentNode.children;
          pnc.splice( pnc.indexOf( node.nodeId ), 1 );
          
          /*
           * drop on the node itself: add to the children of the target node
           */
          if ( dropPosition === 0 )
          {
            var position = dropTarget.children;
            dropTarget.children.push( node.nodeId  );
            node.parentNodeId = dropTarget.nodeId;
            this.fireDataEvent("changeNodePosition", {
              'node' : node,
              'position' : position
            });
          }
          
          /*
           * drop between nodes: add as a sibling of the drop target
           */
          else if ( this.getAllowDropBetweenNodes() )
          {
            var targetParentNode = nodeArr[ dropTarget.parentNodeId ]
            if( ! targetParentNode ) this.error("Cannot find the target node's parent node!");
            var tpnc = targetParentNode.children;
            var delta = dropPosition > 0 ? 1 : 0;
            var position = tpnc.indexOf(dropTarget.nodeId) + delta;
            tpnc.splice( position, 0, node.nodeId );
            node.parentNodeId = targetParentNode.nodeId;
            this.fireDataEvent("changeNodePosition", {
              'node' : node,
              'position' : position
            });            
          }
          /*
           * else, we have a logic error
           */
          else
          {
            this.error("Dropping in between nodes is not allowed!");
          }          
        }
        
        /*
         * re-render the tree
         */
        this.getDataModel().setData();
      }      
    },    
    
    /**
     * Creates an empty branch (=folder) object. This should really
     * be part of the data model.
     * @return {Object}
     */
    createBranch : function( label, icon )
    {
      return {
        type           : qx.ui.treevirtual.SimpleTreeDataModel.Type.BRANCH,
        nodeId         : null, // must be set
        parentNodeId   : null, // must be set
        label          : label,
        bSelected      : false,
        bOpened        : false,
        bHideOpenClose : false,
        icon           : icon,
        iconSelected   : icon,
        children       : [ ],
        columnData     : [ ]
      };
    },
    
    /**
     * Creates an empty leaf object. This should really
     * be part of the data model.
     * @return {Object}
     */
    createLeaf : function( label, icon )
    {
      var node = this.createBranch( label, icon );
      node.type =  qx.ui.treevirtual.SimpleTreeDataModel.Type.LEAF;
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
    importNode : function(e, nodes)
    {
      var dropTarget    = this.getDropTarget();
      var dropPosition  = this.getDropTargetRelativePosition();
      
      if( ! qx.lang.Type.isObject(dropTarget) )
      {
        //this.warn("No valid drop target!");
        return false;
      }

      if( ! dropTarget.children )
      {
        this.error("Drop target is not a folder!");
        return false;
      }
      
      if ( ! qx.lang.Type.isArray( nodes) )
      {
        this.error("Invalid nodes data");
        return false;
      }
      
      /*
       * move nodes
       */
      var nodeArr = this.getDataModel().getData();
      
      for (var i=0, l=nodes.length; i<l; i++) 
      { 
        /*
         * import the node into the tree's node array
         */
        var nodeData = nodes[i];
        node.nodeId = nodeArr.length;
        nodeArr.push(node);
        
        /*
         * drop on the node itself: add to the children of the target node
         */
        if ( dropPosition === 0 )
        {
          dropTarget.children.push( node.nodeId  );
          node.parentNodeId = dropTarget.nodeId;           
        }
        /*
         * drop between nodes: add as a sibling of the drop target
         */
        else if ( this.getAllowDropBetweenNodes() )
        {
          var targetParentNode = nodeArr[ dropTarget.parentNodeId ]
          if( ! targetParentNode ) this.error("Cannot find the target node's parent node!");
          var tpnc = targetParentNode.children;
          var delta = dropPosition > 0 ? 1 : 0;
          tpnc.splice( tpnc.indexOf(dropTarget.nodeId) + delta, 0, node.nodeId );
          node.parentNodeId = targetParentNode.nodeId;
        }
        /*
         * else, we have a logic error
         */
        else
        {
          this.error("Dropping in between nodes is not allowed!");
        }          
      }
      
      /*
       * event
       */
      this.fireDataEvent("beforeAddNode", node);
      
      /*
       * re-render the tree
       */
      this.getDataModel().setData();  
    },        

 

    /**
     * gets the (drag) type of a node
     * @param nodeReference {Object|Integer}
     * @return {Object} the user-supplied type of the node or null if not set
     */
    getNodeDragType : function (nodeReference)
    {
      try
      {
        if(typeof nodeReference == "object")
        {
          return nodeReference.data.DragDrop.type;
        }
        else
        {
          return this.nodeGet(nodeReference).data.DragDrop.type;
        }
      }
      catch(e)
      {
        return null;
      }
    },

    /**
     * sets the (drag) type of a node
     * @param nodeReference {Object|Integer}
     * @param type {String}
     */
    setNodeDragType : function (nodeReference,type)
    {
      if(typeof type != "string")
      {
        this.error("Drag Type must be a string, got " + (typeof type));
      }

      var node = this.nodeGet(nodeReference);
      if(!node.data)
      {
        node.data = {};
      }
      if(!node.data.DragDrop)
      {
        node.data.DragDrop = {};
      }

      node.data.DragDrop.type = type;
    }
  }
});