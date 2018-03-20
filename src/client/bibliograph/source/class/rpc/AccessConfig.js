qx.Class.define("rpc.AccessConfig",
{ 
  extend: qx.core.Object,
  statics: {

    /**

     * @return {Promise}
     */
    types : function(){

      return this.getApplication().getRpcClient("access-config").send("types", []);
    },

    /**
     * @param type {String}
     * @param filter {Array}
     * @return {Promise}
     */
    elements : function(type, filter=null){
      qx.core.Assert.assertString(type);
      qx.core.Assert.assertArray(filter);
      return this.getApplication().getRpcClient("access-config").send("elements", [type, filter]);
    },

    /**
     * @param type
     * @param namedId
     * @return {Promise}
     */
    data : function(type=null, namedId=null){


      return this.getApplication().getRpcClient("access-config").send("data", [type, namedId]);
    },

    /**
     * @param elementType
     * @param namedId
     * @return {Promise}
     */
    tree : function(elementType=null, namedId=null){


      return this.getApplication().getRpcClient("access-config").send("tree", [elementType, namedId]);
    },

    /**
     * @param type
     * @param namedId
     * @param edit
     * @return {Promise}
     */
    add : function(type=null, namedId=null, edit=null){



      return this.getApplication().getRpcClient("access-config").send("add", [type, namedId, edit]);
    },

    /**
     * @param first
     * @param second
     * @param third
     * @return {Promise}
     */
    edit : function(first=null, second=null, third=null){



      return this.getApplication().getRpcClient("access-config").send("edit", [first, second, third]);
    },

    /**
     * @param data
     * @param type
     * @param namedId
     * @return {Promise}
     */
    save : function(data=null, type=null, namedId=null){



      return this.getApplication().getRpcClient("access-config").send("save", [data, type, namedId]);
    },

    /**
     * @param type
     * @param ids
     * @return {Promise}
     */
    delete : function(type=null, ids=null){


      return this.getApplication().getRpcClient("access-config").send("delete", [type, ids]);
    },

    /**
     * @param doDeleteModelData
     * @param namedId
     * @return {Promise}
     */
    deleteDatasource : function(doDeleteModelData=null, namedId=null){


      return this.getApplication().getRpcClient("access-config").send("delete-datasource", [doDeleteModelData, namedId]);
    },

    /**
     * @param linkedModelData
     * @param type
     * @param namedId
     * @return {Promise}
     */
    link : function(linkedModelData=null, type=null, namedId=null){



      return this.getApplication().getRpcClient("access-config").send("link", [linkedModelData, type, namedId]);
    },

    /**
     * @param linkedModelData
     * @param type
     * @param namedId
     * @return {Promise}
     */
    unlink : function(linkedModelData=null, type=null, namedId=null){



      return this.getApplication().getRpcClient("access-config").send("unlink", [linkedModelData, type, namedId]);
    },

    /**

     * @return {Promise}
     */
    newUserDialog : function(){

      return this.getApplication().getRpcClient("access-config").send("new-user-dialog", []);
    },

    /**
     * @param data
     * @return {Promise}
     */
    addUser : function(data){

      return this.getApplication().getRpcClient("access-config").send("add-user", [data]);
    },

    /**

     * @return {Promise}
     */
    newDatasourceDialog : function(){

      return this.getApplication().getRpcClient("access-config").send("new-datasource-dialog", []);
    },

    /**
     * @param data
     * @return {Promise}
     */
    addDatasource : function(data=null){

      return this.getApplication().getRpcClient("access-config").send("add-datasource", [data]);
    },

    /**
     * @param class {String}
     * @return {Promise}
     */
    schemaclassExists : function(class){
      qx.core.Assert.assertString(class);
      return this.getApplication().getRpcClient("access-config").send("schemaclass-exists", [class]);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("access-config").send("index", []);
    },
    ___eof : null
  }
});