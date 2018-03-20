qx.Class.define("rpc.Folder",
{ 
  extend: qx.core.Object,
  statics: {

    /**
     * @param datasource
     * @param options
     * @return {Promise}
     */
    nodeCount : function(datasource=null, options=null){


      return this.getApplication().getRpcClient("folder").send("node-count", [datasource, options]);
    },

    /**
     * @param datasource
     * @param nodeId
     * @param options
     * @return {Promise}
     */
    childCount : function(datasource=null, nodeId=null, options=null){



      return this.getApplication().getRpcClient("folder").send("child-count", [datasource, nodeId, options]);
    },

    /**
     * @param datasource
     * @param options
     * @return {Promise}
     */
    load : function(datasource=null, options=null){


      return this.getApplication().getRpcClient("folder").send("load", [datasource, options]);
    },

    /**
     * @param datasource
     * @param folderId
     * @return {Promise}
     */
    edit : function(datasource=null, folderId=null){


      return this.getApplication().getRpcClient("folder").send("edit", [datasource, folderId]);
    },

    /**
     * @param data
     * @param datasource
     * @param folderId
     * @return {Promise}
     */
    save : function(data=null, datasource=null, folderId=null){



      return this.getApplication().getRpcClient("folder").send("save", [data, datasource, folderId]);
    },

    /**
     * @param datasource
     * @param folderId
     * @return {Promise}
     */
    visibilityDialog : function(datasource=null, folderId=null){


      return this.getApplication().getRpcClient("folder").send("visibility-dialog", [datasource, folderId]);
    },

    /**
     * @param data
     * @param datasource
     * @param folderId
     * @return {Promise}
     */
    visibilityChange : function(data=null, datasource=null, folderId=null){



      return this.getApplication().getRpcClient("folder").send("visibility-change", [data, datasource, folderId]);
    },

    /**
     * @param datasource
     * @param folderId
     * @return {Promise}
     */
    addDialog : function(datasource=null, folderId=null){


      return this.getApplication().getRpcClient("folder").send("add-dialog", [datasource, folderId]);
    },

    /**
     * @param data
     * @param datasource
     * @param parentFolderId
     * @return {Promise}
     */
    create : function(data=null, datasource=null, parentFolderId=null){



      return this.getApplication().getRpcClient("folder").send("create", [data, datasource, parentFolderId]);
    },

    /**
     * @param datasource
     * @param folderId
     * @return {Promise}
     */
    removeDialog : function(datasource=null, folderId=null){


      return this.getApplication().getRpcClient("folder").send("remove-dialog", [datasource, folderId]);
    },

    /**
     * @param data
     * @param datasource
     * @param folderId
     * @return {Promise}
     */
    remove : function(data=null, datasource=null, folderId=null){



      return this.getApplication().getRpcClient("folder").send("remove", [data, datasource, folderId]);
    },

    /**
     * @param datasource
     * @param folderId
     * @param parentId
     * @return {Promise}
     */
    move : function(datasource=null, folderId=null, parentId=null){



      return this.getApplication().getRpcClient("folder").send("move", [datasource, folderId, parentId]);
    },

    /**
     * @param datasource
     * @param folderId
     * @param position
     * @return {Promise}
     */
    positionChange : function(datasource=null, folderId=null, position=null){



      return this.getApplication().getRpcClient("folder").send("position-change", [datasource, folderId, position]);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("folder").send("index", []);
    },
    ___eof : null
  }
});