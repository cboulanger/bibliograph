/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/

   Copyright:
     2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger) using code from qx.data.controller.Tree
     * Martin Wittemann (martinwittemann) 

************************************************************************ */

/**
 * Controller for TreeVirtual widget
 */
qx.Class.define("qcl.data.controller.TreeVirtual", 
{
  extend : qx.core.Object,
  include: qx.data.controller.MSelection,

  /*
   *****************************************************************************
      CONSTRUCTOR
   *****************************************************************************
   */
   
   /**
    * @param target {qx.ui.tree.Tree?null} The target widgets which should be a tree.
    * @param store { Object?null } The store that retrieves the data
    */
   construct : function( target, store )  
   {
     this.base(arguments);
    
     if( target != null ) 
     {
       this.setTarget( target );
     }
     
     if( store != null ) 
     {
       this.setStore( store );
     } 
     
     /*
      * node id map
      */
     this.__nodeIdMap={
       0 : 0
     };
   },

   /*
   *****************************************************************************
      PROPERTIES
   *****************************************************************************
   */  
   
   properties : 
   {
     /** The root element of the data. */
     model : 
     {
       check: "qx.core.Object",
       apply: "_applyModel",
       event: "changeModel",
       nullable: true
     },
     
     
     /** The tree to bind the data to. */
     target : 
     {
       check: "qx.ui.treevirtual.TreeVirtual",
       event: "changeTarget",
       apply: "_applyTarget",
       init: null
     },
     
     /** The store to get the data from */
     store : 
     {
       event: "changeStore",
       apply: "_applyStore",
       init: null
     },
     
     
     /**
      * Delegation object, which can have one ore more function defined by the
      * {@link #IControllerDelegate} Interface.  
      */
     delegate : 
     {
       apply: "_applyDelegate",
       init: null,
       nullable: true
     },
     
     /**
      * A number indicating the state of the tree data. This way, client and
      * server state can be compared. If one transaction id is higher than the other,
      * the other is out of date. 
      */
     transactionId :
     {
       check : "Integer",
       event : "changeTransactionId"
     }
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
     * A map connecting client-side node ids (key) with the server-side
     * node id (value) 
     */
    __nodeIdMap : null,
    
     /*
     ---------------------------------------------------------------------------
        APPLY METHODS
     ---------------------------------------------------------------------------
     */   
     
     /**
      * If a new delegate is set, it applies the stored configuration for the
      * tree folder to the already created folders once.
      * 
      * @param value {qx.core.Object|null} The new delegate.
      * @param old {qx.core.Object|null} The old delegate.
      */
     _applyDelegate: function(value, old) {
       //
     },
     
     /*
      * Set a new TreeVirtual as target
      */
     _applyTarget : function( target, old )
     {
       if ( old )
       {
         // @todo remove listeners
       }
       
       if ( target )
       {
         var targetModel = target.getDataModel();
         
         /*
          * catch events like add, remove, etc. 
          */         
         targetModel.getModel().addListener("change", this._targetOnChange, this);
         /*
          * catch property changes in the nodes
          */         
         targetModel.getModel().addListener("changeBubble", this._targetOnChangeBubble, this);         
       }

     },
     
     /**
      * Set a new store and adds event listeners
      */
     _applyStore : function ( store, old )
     {
       if ( old )
       {
         // @todo remove event listeners and bindings
       }       
       
       if ( store )
       {
         store.bind( "model", this, "model" );
         store.addListener( "change", this._storeOnChange, this );
         store.addListener( "changeBubble", this._storeOnChangeBubble, this );
       }
     },     
     
     /**  
      * Apply-method which will be called after the model loaded
      * by the data store has been passed to this controller.
      * This adds the nodes contained in the model to the
      * tree data model of the target.
      * 
      * @param value {qx.core.Object|null} The model contaning the new nodes.
      * @param old {qx.core.Object|null} The old model, if any.
      */    
     _applyModel: function( model, old ) 
     {
       var targetModel  = this.getTarget().getDataModel();
        
       /*
        * clear the tree if the model is set to null
        */
       if ( model === null )
       {
         targetModel.clearData();
         return;
       }
       
       /*
        * check if there are any nodes to add
        */
       var nodeData = model.getNodeData();   
       if ( ! qx.lang.Type.isArray( nodeData ) )
       {
          throw new Error("Invalid node data!");
       }
       if ( ! nodeData.length ) return;
       
       /*
        * add tree data to the model
        */
       nodeData.forEach( function(node){
          
          var serverNodeId = node.data.id;
          if ( ! qx.lang.Type.isNumber( serverNodeId ) )
          {
            throw new Error("Missing  or invalid server node id in node data.")
          }
          var serverParentId = node.data.parentId;
          if ( ! qx.lang.Type.isNumber( serverParentId ) )
          {
            throw new Error("Missing or invalid server parent node id in node data.")
          }
          
          var parentNodeId = this.__nodeIdMap[serverParentId];
          if ( parentNodeId === undefined )
          {
            throw new Error( "Cannot add node #" + serverNodeId + ": parent node #" + serverParentId + " has not been loaded yet." )
          }
          
          if ( node.isBranch )
          {
            var clientNodeId = targetModel.addBranch(
              parentNodeId,
              node.label,
              node.bOpened,
              node.bHideOpenCloseButton,
              node.icon,
              node.iconSelected
            );
          }
          else
          {
            var clientNodeId = targetModel.addLeaf(
              parentNodeId,
              node.label,
              node.icon,
              node.iconSelected
            );
          }
          
          /*
           * set column und node data
           */
          targetModel.setState(clientNodeId, {
            columnData : node.columnData,
            data       : node.data
          });
          
          /*
           * save node in map
           */
          this.__nodeIdMap[serverNodeId] = clientNodeId;
          
       }, this);
          
       targetModel.setData();         

     },
    
     /**
      * Given the server-side node id, return the client-side one
      * @param serverNodeId {Integer}
      * @return {Integer}
      */
     getClientNodeId : function( serverNodeId )
     {
        return this.__nodeIdMap[serverNodeId];
     },
     
     /**
      * Remap the server node id to the client node id if the
      * node data has not been loaded with the store.
      */
     remapNodeIds : function()
     {
        var data = this.getTarget().getDataModel().getData();
        data.forEach(function(node){
          try
          {
            this.__nodeIdMap[node.data.id] = node.nodeId;
          }
          catch(e){}
        },this);
     },
     
     /*
     ---------------------------------------------------------------------------
        EVENT LISTENERS
     ---------------------------------------------------------------------------
     */
     
     /**
      * Called when the target has dispatched a "change" event.
      * Propagates it to the store, adding data that is implicit
      * in the event.
      * @param event {qx.event.type.Data}
      * @return {Void}
      */
     _targetOnChange : function( event )
     {
       //this.info( "Received event '" + event.getType() + "' from " + event.getTarget() );
       
        // @todo: not implemented
     },

     /**
      * Called when the target has dispatched a "changeBubble" event.
      * Propagates it to the store.
      * @param event {qx.event.type.Data}
      * @return {Void}
      */     
     _targetOnChangeBubble : function( event )
     {
        //this.info( "Received event '" + event.getType() + "' from " + event.getTarget() );
        
        //@todo: not implemented
     },

     /**
      * Called when the store dispatches a 'change' event
      * @param event {qx.event.type.Data?null}
      * @return {void}
      */
      _storeOnChange : function( event )
      {
        
         //this.info( "Received event '" + event.getType() + "' from " + event.getTarget() );
         // @todo : not implemented
      },
      
      /**
       * Called when the store dispatches a 'changeBubble' event
       * @param event {qx.event.type.Data?null}
       * @return {void}
       */
      _storeOnChangeBubble : function( event )
      {
        
         //this.info( "Received event '" + event.getType() + "' from " + event.getTarget() );
         // @todo : not implemented
      }     
     
  }
});