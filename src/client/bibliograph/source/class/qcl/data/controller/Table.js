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
 * Controller for Table widget
 * @asset(virtualdata/ajax-loader.gif)
 */
qx.Class.define("qcl.data.controller.Table", 
{
  extend : qx.core.Object,
  include: [ qx.data.controller.MSelection ],

  /*
   *****************************************************************************
      CONSTRUCTOR
   *****************************************************************************
   */
   
   /**
    * @param target {qx.ui.table.Table} The target table.
    * @param store {Object?null} The store that retrieves the data
    */
  construct : function( target, store )  
  {
    this.base(arguments);
    
    if( target != undefined ) 
    {
       this.setTarget( target );
    }
     
    if( store != undefined ) 
    {
       this.setStore( store );
    }     
     
    this.__currentRequestIds = [];
  },

   /*
   *****************************************************************************
      PROPERTIES
   *****************************************************************************
   */  
   
   properties : 
   {
     /** The data supplied by the store */
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
       check: "qx.ui.table.Table",
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
     * Fired when a block has been loaded from the server
     */
    "blockLoaded" : "qx.event.type.Data",

    /**
     * Fired when a message should be displayed
     */
    "statusMessage" : "qx.event.type.Data"
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
     __currentRequestIds : null,
     __rowCount : null,

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
     
     /**
      * Set a new Table as target
      */
     _applyTarget : function( target, old )
     {
       if ( old )
       {
         // @todo remove listeners
       }
       
       if ( target )
       {
         /*
          * catch the dataEdited event which is fired when the
          * user has made a manual change to the data
          */
         //target.addListener("dataEdited", this._targetOnDataEdited, this );
         
         /*
          * catch events like add, remove, etc. 
          */
         //target.getTableModel().addListener("change", this._targetOnChange, this );
         
         /*
          * store a reference to controller in the model. This can be
          * problematic if several Tables share a datasource.
          */
         target.getTableModel().setController(this);
       }
       
     },
     
     
     /**
      * Set a new store and adds event listeners
      */
     _applyStore : function ( store, old )
     {

       
     },
     
     /**  
      * Apply-method which will be called after the model had been 
      * changed. This forwards the data sent by the server to the
      * controlled widget's data model.
      * 
      * @param value {qx.core.Object|null} The model contaning the new nodes.
      * @param old {qx.core.Object|null} The old model, if any.
      */    
     _applyModel: function( model, old ) 
     {
       var targetModel  = this.getTarget().getTableModel();
        
       /*
        * clear the table if the model is set to null
        */
       if ( model === null )
       {
         targetModel.clearCache();
         this.__rowCountRequest = false;
         this.__currentRequestIds = [];
         return;
       }
       
       var requestId = model.getRequestId();
       
       /*
        * was this a row count request?
        */
       if ( model.getRowCount() !== null && this.__rowCountRequest )
       {
         targetModel._onRowCountLoaded( model.getRowCount() );
         this.__rowCountRequest = false;
       }
       
       /*
        * was this a row data request? if yes, does the requestId fit?
        */
       else if ( model.getRowData() !== null 
           && qx.lang.Array.contains( this.__currentRequestIds, requestId ) )
       {
         targetModel._onRowDataLoaded( model.getRowData() );
         qx.lang.Array.remove( this.__currentRequestIds, requestId );
       }
     },
     
     /*
     ---------------------------------------------------------------------------
        API METHODS
     ---------------------------------------------------------------------------
     */       
     
     /**
      * Reloads the data of the target model
      * @return {Void}
      */
     reload : function()
     {
       if( this.getTarget() && this.getStore() )
       {
         /*
          * visually reset the table
          */
         var table = this.getTarget();
         var model = table.getTableModel();
         table.resetSelection();
         model.reloadData();
         table.scrollCellVisible(0,0);
       }
     },
     
     /*
     ---------------------------------------------------------------------------
        METHODS CALLED BY THE CONTROLLED TABLE'S DATA MODEL
     ---------------------------------------------------------------------------
     */        
     
     /**
      * Triggers the request for  the number of rows through the store. 
      * The value will be in the rowCount property of the model that 
      * is provided by the store.
      * @return {void}
      */
     _loadRowCount : function()
     {
       if ( ! this.getTarget() ) return;
       
       var store      = this.getStore();
       var tableModel = this.getTarget().getTableModel();
       var marshaler  = store.getMarshaler();
       var params     = marshaler.getQueryParams();
       var app        = qx.core.Init.getApplication();
      
       delete this.__firstRow;
       
      this.fireDataEvent("statusMessage", app.tr( "Getting number of rows..." ) );

       /*
        * load the row count and pass it to th model
        */
       store.load( marshaler.getMethodGetRowCount(), params, function(){
         this.fireDataEvent( "statusMessage", null );
         this.__rowCount = store.getModel().getRowCount();
         tableModel._onRowCountLoaded( this.__rowCount );
       },this );
     },
     
     
    /**
     * Triggers the request for row data through the store. 
     * The data will be in the rowData property of the model that 
     * is provided by the store.
     * @return {void}
     */
     _loadRowData : function( firstRow, lastRow ) 
     {
        
       //if ( this.__rowDataRequest ) return;
       //this.info( "Requesting " + firstRow + " - " + lastRow );
      
       /*
        * prevent retrieving the same data more than once
        */
       if ( firstRow === this.__firstRow )
       {
         return;
       }      
       this.__firstRow = firstRow;
       
       var store = this.getStore();
       var marshaler = store.getMarshaler();
       var tableModel = this.getTarget().getTableModel();
       var app = qx.core.Init.getApplication();

       /*
        * store request id so that we can differentiate different
        * tables connected to the same store
        */
       var requestId = store.getNextRequestId(); 
       this.__currentRequestIds.push( requestId );
       
       /*
        * add firstRow, lastRow, requestId at the beginning of the 
        * parameters
        * @todo do we really need the request id?
        */
       var params = [firstRow, lastRow, requestId].concat( marshaler.getQueryParams() );
         
       this.fireDataEvent("statusMessage", app.tr(
          "Loading rows %1 - %2 of %3 ...",
          firstRow, Math.min( lastRow, this.__rowCount), this.__rowCount 
        ) /*,this.getTarget() */);
       
       /*
        * load data
        * @todo Catch server errors to avoid to break the remote table
        * model when something goes wrong on the server
        */
       store.load( marshaler.getMethodGetRowData(), params, function()
       {

         this.fireDataEvent("statusMessage",null);
        
        /*
         * check data
         */
        var rowData = store.getModel().getRowData();
        if ( ! qx.lang.Type.isArray( rowData ) || ! rowData.length )
        {
          this.warn("Invalid server response"); 
          rowData = null;
        }
        
        /*
         * pass data to the table model
         */
        tableModel._onRowDataLoaded( rowData );
        
        /*
         * fire event
         */
        this.fireDataEvent("blockLoaded",{
          'firstRow' : firstRow,
          'lastRow'  : lastRow
        });
       }, this );
     },
     
     /*
     ---------------------------------------------------------------------------
        EVENT LISTENERS
     ---------------------------------------------------------------------------
     */
    endOfFile : true
  }
});