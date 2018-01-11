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

 ************************************************************************ */
/*global qx qcl virtualdata*/

/**
 * Marshaler for data for qx.ui.treevirtual.TreeVirtual
 * @ignore(qx.data.model.TreeVirtual)
 * @ignore(qx.data.model.Table)
 */
qx.Class.define("virtualdata.marshal.TreeVirtual", 
{
  extend : qx.core.Object,

  
  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */
  
  /**
   * @param delegate {Object} An object containing one of the mehtods described 
   *   in {@link qx.data.store.IStoreDelegate}.
   */
  construct : function(delegate)
  {
    this.base(arguments);  
    this.__delegate = delegate;
  },

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  
  properties :
  {
    /**  
     * Additional parameters passed to both "getRowCount" and "getRowData"
     * methods.
     */
    queryParams :
    {
      check : "Array",
      nullable : true,
      init : []
    },    

    /**
     * The maximum number of queue elements processed in a query. There is
     * no way to determine how many nodes will be retrieved with one
     * queue element. You'll have to experiment what works best with
     * the kind of tree structures you work with
     */
    maxQueueElementsPerQuery :
    {
      check : "Integer",
      init: 10
    },

    /** 
     * name of the jsonrpc service method that determines node count
     * defaults to "getNodeCount"
     */
    methodGetNodeCount :
    {
      check : "String",
      nullable : false,
      init : "getNodeCount"
    },

    /** 
     * Name of the jsonrpc service method that retrieves the node data
     * defaults to "getNodeData"
     */
    methodGetNodeData :
    {
      check : "String",
      nullable : false,
      init : "getNodeData"
    }
  },

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  
  members :
  {
    __delegate : null,    


    /**
     * Creates for the given data the needed classes. 
     * 
     * @see qx.data.store.IStoreDelegate
     * 
     * @param data {Object} The object for which classes should be created.
     * @param includeBubbleEvents {Boolean} Whether the model should support
     *   the bubbling of change events or not.
     */
    toClass: function(data) 
    {

      // class already exists
      if (qx.Class.isDefined("qx.data.model.TreeVirtual" )) {
        return;
      }

      // define class
      qx.Class.define("qx.data.model.TreeVirtual", 
      {
        extend: qx.core.Object,
        properties : {
          nodeCount : { check: "Integer", init : null },
          childCount : { check: "Integer", init : null },
          nodeData : { check : "Array", init : [] },
          queue : { check : "Array", init : [] },
          events : { check : "Map", init : [] },
          columnLayout : { check: "Map", nullable: true },
          transactionId : { check: "Integer", nullable : true, event: "changeTransactionId" },
          statusText : { check: "String", init : "", event: "changeStatusText" }
        }
      });
    },


    /** 
     * Creates for the given data the needed models. Be sure to have the classes
     * created with {@link #jsonToClass} before calling this method. The creation 
     * of the class itself is delegated to the {@link #__createInstance} method,
     * which could use the {@link qx.data.store.IStoreDelegate} methods, if 
     * given.
     * 
     * @param data {Object} The object for which models should be created.
     * @return {Object}
     */
    toModel: function(data) 
    {   
      var model = new qx.data.model.TreeVirtual();
      model.set(data);
      return model;
    }    
  }
});
