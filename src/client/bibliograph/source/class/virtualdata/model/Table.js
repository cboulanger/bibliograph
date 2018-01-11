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
     * Christian Boulanger (cboulanger)
     * Til Schneider (til132)

************************************************************************ */

/**
 * An implementation of the abstract class qx.ui.table.model.Remote requests 
 * to load data are delegated to the data store through the controller.
 */
qx.Class.define("virtualdata.model.Table",
{
  extend : qx.ui.table.model.Remote,

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  construct : function(serviceName)
  {
    this.base(arguments);
    this.__idIndex = {};
  },
  
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties :
  {

    /**
     * The controller object of this model
     */
    controller :
    {
      check : "qx.core.Object",
      nullable : true
    },
    
    idColumn :
    {
      check : "String",
      init : "id"
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
    __idIndex : null,
    __firstRow : null,
    __lastRow : null,
     
          
    /*
    ---------------------------------------------------------------------------
       ADDED METHODS
    ---------------------------------------------------------------------------
    */         
    
    /**
     * gets the column names as an array
     * @return {Array}
     */
    getColumnIds : function ()
    {
      return this.__columnIdArr;
    },
    
    /** 
     * Reloads a section of the data only
     * @author Most of the code by Til Schneider 
     */
    reloadRows : function(firstRowIndex, lastRowIndex )
    {
//      this.debug("Reloading wanted: " + firstRowIndex + ".." + lastRowIndex);
            
      if (this.__firstLoadingBlock == -1)
      {
        
        var blockSize = this.getBlockSize();
        var totalBlockCount = Math.ceil(this.__rowCount / blockSize);

        // There is currently no request running -> Start a new one
        // NOTE: We load one more block above and below to have a smooth
        //       scrolling into the next block without blank cells
        var firstBlock = parseInt(firstRowIndex / blockSize) - 1;

        if (firstBlock < 0) {
          firstBlock = 0;
        }

        var lastBlock = parseInt(lastRowIndex / blockSize) + 1;

        if (lastBlock >= totalBlockCount) {
          lastBlock = totalBlockCount - 1;
        }
        
//        this.debug(
//          "blockSize: " + blockSize + ", " +
//          "totalBlockCount: " + totalBlockCount + ", " +
//          "firstBlock: " + firstBlock + ", " +
//          "lastBlock: " + lastBlock + ", " +
//          "totalBlockCount: " + blockSize + ", "
//        );

        // Check which blocks we have to load
        var firstBlockToLoad = -1;
        var lastBlockToLoad = -1;

        for (var block=firstBlock; block<=lastBlock; block++)
        {
          if (this.__rowBlockCache[block] == null || this.__rowBlockCache[block].isDirty)
          {
            // We don't have this block
            if (firstBlockToLoad == -1) {
              firstBlockToLoad = block;
            }

            lastBlockToLoad = block;
          }
        }

        // Load the blocks
        if ( firstBlockToLoad != -1 )
        {
          this.__firstRowToLoad = -1;
          this.__lastRowToLoad = -1;

          this.__firstLoadingBlock = firstBlockToLoad;

//           this.debug("Starting server request. rows: " + firstRowIndex + ".." + lastRowIndex + ", blocks: " + firstBlockToLoad + ".." + lastBlockToLoad);
          this._loadRowData(firstBlockToLoad * blockSize, (lastBlockToLoad + 1) * blockSize - 1);
        }
      }
      else
      {
//        this.debug("Request already running ... ");
        
        // There is already a request running -> Remember this request
        // so it can be executed after the current one is finished.
        this.__firstRowToLoad = firstRowIndex;
        this.__lastRowToLoad = lastRowIndex;
      }
      
    },
    
    /**
     * Returns the row index identified by the id in the id column
     * @param id {Integer}
     * @return {Integer}
     */
    getRowById : function( id )
    {
      if ( ! this.__idIndex ) return undefined;
      return this.__idIndex[id];
    },    
    
    /*
    ---------------------------------------------------------------------------
       OVERRIDDEN METHODS
    ---------------------------------------------------------------------------
    */      
 
   
    /**
     * Removes a row from the model. Overridden to remove id of row
     * from id index.
     *
     * @param rowIndex {Integer} the index of the row to remove.
     * @return {void}
     */
    removeRow : function( rowIndex )
    {
      var rowData = this.getRowData( rowIndex );
      if ( qx.lang.Type.isObject(rowData) )
      {
        var idCol = this.getIdColumn();
        var id = rowData[idCol];
        delete this.__idIndex[id];
      }
      this.base(arguments, rowIndex);
    },
    
    /*
    ---------------------------------------------------------------------------
       METHODS CALLED BY THE PARENT CLASSS 
    ---------------------------------------------------------------------------
    */   
    
    /** 
     * Initiates a data request, which is handled by
     * the controller and the connected data store. 
     * When the data arrives, the controller will call
     * this object's 
     */
    _loadRowCount : function() 
    {
      
      // console.log("Row count request..."); 
      /*
       * mark that query is in progress
       */
      this._rowCount = 0; 
      
      /*
       * call the controller's method which will then 
       * trigger a request by the store
       */
      if ( this.getController() )
      {
        this.getController()._loadRowCount();
      }
    },

    /** 
     * Loads row data from the data store. 
     */
    _loadRowData : function( firstRow, lastRow ) 
    {
      //this.debug("Requesting rows " + firstRow + " - " + lastRow );
      
      this.__firstRow = firstRow;
      this.__lastRow = lastRow;
      
      /*
       * call the controller's method which will then 
       * trigger a request by the store
       */
      this.getController()._loadRowData( firstRow, lastRow );
    },
    
    
    /**
     * Sets row data.
     *
     * Has to be called by {@link _loadRowData()}.
     * @override
     * @param rowDataArr {Map[]} the loaded row data or null if there was an error.
     * @return {void}
     */
    _onRowDataLoaded : function(rowDataArr)
    {
      
      //this.debug("Row data returned from server...");
      
      /*
       * call parent method
       */
      this.base(arguments,rowDataArr);
      
      /*
       * create index
       */
      if( qx.lang.Type.isArray( rowDataArr ) )
      {
        var index= this.__firstRow;
        var idCol = this.getIdColumn();
        rowDataArr.forEach(function(row){
          this.__idIndex[row[idCol]]=index++;
        },this);
      }
      
      this.__firstRow = null;
      this.__lastRow = null;      
    },

    /**
     * Rebuilds the row-id map
     * @private
     */
    rebuildIndex : function()
    {
      var idCol = this.getIdColumn();
      this.__idIndex = {};
      this.iterateCachedRows(function(index,data){
        this.__idIndex[index] = data[idCol];
      },this);
    }
    
  }
});
