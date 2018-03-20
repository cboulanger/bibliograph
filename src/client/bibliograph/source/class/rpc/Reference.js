qx.Class.define("rpc.Reference",
{ 
  extend: qx.core.Object,
  statics: {

    /**
     * @param datasource
     * @param modelClassType
     * @return {Promise}
     */
    tableLayout : function(datasource=null, modelClassType=null){


      return this.getApplication().getRpcClient("reference").send("table-layout", [datasource, modelClassType]);
    },

    /**
     * @param clientQueryData
     * @return {Promise}
     */
    rowCount : function(clientQueryData){

      return this.getApplication().getRpcClient("reference").send("row-count", [clientQueryData]);
    },

    /**
     * @param firstRow {Number}
     * @param lastRow {Number}
     * @param requestId {Number}
     * @param clientQueryData
     * @return {Promise}
     */
    rowData : function(firstRow, lastRow, requestId, clientQueryData){
      qx.core.Assert.assertNumber(firstRow);
      qx.core.Assert.assertNumber(lastRow);
      qx.core.Assert.assertNumber(requestId);

      return this.getApplication().getRpcClient("reference").send("row-data", [firstRow, lastRow, requestId, clientQueryData]);
    },

    /**
     * @param datasource
     * @param reftype
     * @return {Promise}
     */
    formLayout : function(datasource=null, reftype=null){


      return this.getApplication().getRpcClient("reference").send("form-layout", [datasource, reftype]);
    },

    /**
     * @param datasource
     * @return {Promise}
     */
    referenceTypeList : function(datasource=null){

      return this.getApplication().getRpcClient("reference").send("reference-type-list", [datasource]);
    },

    /**
     * @param datasource
     * @return {Promise}
     */
    types : function(datasource=null){

      return this.getApplication().getRpcClient("reference").send("types", [datasource]);
    },

    /**
     * @param datasource
     * @param arg2
     * @param arg3
     * @param arg4
     * @return {Promise}
     */
    item : function(datasource=null, arg2=null, arg3=null, arg4=null){




      return this.getApplication().getRpcClient("reference").send("item", [datasource, arg2, arg3, arg4]);
    },

    /**
     * @param datasource
     * @param field
     * @param input
     * @return {Promise}
     */
    autocomplete : function(datasource=null, field=null, input=null){



      return this.getApplication().getRpcClient("reference").send("autocomplete", [datasource, field, input]);
    },

    /**
     * @param datasource
     * @param referenceId
     * @param data
     * @return {Promise}
     */
    save : function(datasource=null, referenceId=null, data=null){



      return this.getApplication().getRpcClient("reference").send("save", [datasource, referenceId, data]);
    },

    /**
     * @param datasource
     * @param field
     * @return {Promise}
     */
    listField : function(datasource=null, field=null){


      return this.getApplication().getRpcClient("reference").send("list-field", [datasource, field]);
    },

    /**
     * @param datasource
     * @param folderId
     * @param data
     * @return {Promise}
     */
    create : function(datasource=null, folderId=null, data=null){



      return this.getApplication().getRpcClient("reference").send("create", [datasource, folderId, data]);
    },

    /**
     * @param first
     * @param second
     * @return {Promise}
     */
    remove : function(first=null, second=null){


      return this.getApplication().getRpcClient("reference").send("remove", [first, second]);
    },

    /**
     * @param datasource
     * @param folderId
     * @return {Promise}
     */
    folderRemove : function(datasource=null, folderId=null){


      return this.getApplication().getRpcClient("reference").send("folder-remove", [datasource, folderId]);
    },

    /**
     * @param datasource
     * @param folderId
     * @param targetFolderId
     * @param ids
     * @return {Promise}
     */
    move : function(datasource=null, folderId=null, targetFolderId=null, ids=null){




      return this.getApplication().getRpcClient("reference").send("move", [datasource, folderId, targetFolderId, ids]);
    },

    /**
     * @param datasource
     * @param folderId
     * @param targetFolderId
     * @param ids
     * @return {Promise}
     */
    copy : function(datasource=null, folderId=null, targetFolderId=null, ids=null){




      return this.getApplication().getRpcClient("reference").send("copy", [datasource, folderId, targetFolderId, ids]);
    },

    /**
     * @param datasource
     * @param referenceId
     * @return {Promise}
     */
    tableHtml : function(datasource=null, referenceId=null){


      return this.getApplication().getRpcClient("reference").send("table-html", [datasource, referenceId]);
    },

    /**
     * @param datasource
     * @param id
     * @return {Promise}
     */
    itemHtml : function(datasource=null, id=null){


      return this.getApplication().getRpcClient("reference").send("item-html", [datasource, id]);
    },

    /**
     * @param datasource
     * @param referenceId
     * @return {Promise}
     */
    containers : function(datasource=null, referenceId=null){


      return this.getApplication().getRpcClient("reference").send("containers", [datasource, referenceId]);
    },

    /**
     * @param datasource
     * @param referenceId
     * @return {Promise}
     */
    duplicatesData : function(datasource=null, referenceId=null){


      return this.getApplication().getRpcClient("reference").send("duplicates-data", [datasource, referenceId]);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("reference").send("index", []);
    },
    ___eof : null
  }
});