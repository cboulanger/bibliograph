/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Backend service class for the access control tool widget
 * 
 * @see app\controllers\AccessConfigController
 * @file AccessConfigController.php
 */
qx.Class.define("rpc.AccessConfig",
{ 
  type: 'static',
  statics: {
    /**
     * Retuns ListItem data for the types of access models
     * 
     * @return {Promise}
     * @see AccessConfigController::actionTypes
     */
    types : function(){
      return qx.core.Init.getApplication().getRpcClient("access-config").send("types", []);
    },

    /**
     * Return ListItem data for access models
     * 
     * @param type {String} The type of the element
     * @param filter {Array} An associative array that can be used in a ActiveQuery::where() method call
     * @return {Promise}
     * @see AccessConfigController::actionElements
     */
    elements : function(type, filter){
      qx.core.Assert.assertString(type);
      qx.core.Assert.assertArray(filter);
      return qx.core.Init.getApplication().getRpcClient("access-config").send("elements", [type, filter]);
    },

    /**
     * Returns the data of the given model (identified by type and id)
     * Only for testing, disabled in production
     * 
     * @param type 
     * @param namedId 
     * @return {Promise}
     * @see AccessConfigController::actionData
     */
    data : function(type, namedId){
      // @todo Document type for 'type' in app\controllers\AccessConfigController::actionData
      // @todo Document type for 'namedId' in app\controllers\AccessConfigController::actionData
      return qx.core.Init.getApplication().getRpcClient("access-config").send("data", [type, namedId]);
    },

    /**
     * Returns the tree of model relationships based on the selected element
     * 
     * @param elementType 
     * @param namedId 
     * @return {Promise}
     * @see AccessConfigController::actionTree
     */
    tree : function(elementType, namedId){
      // @todo Document type for 'elementType' in app\controllers\AccessConfigController::actionTree
      // @todo Document type for 'namedId' in app\controllers\AccessConfigController::actionTree
      return qx.core.Init.getApplication().getRpcClient("access-config").send("tree", [elementType, namedId]);
    },

    /**
     * Add an empty model record. When creating a datasource,
     * a default bibliograph datasource is created.
     * Creates the form editor
     * @param type {String} The type of the element
     * @param namedId {String} The named id of the element
     * @param schema {String|null} The name of the schema (only relevant for datasource elements)
     * @param edit {Boolean} If true (default), trigger the form to edit the data
     * @return {Promise}
     * @see AccessConfigController::actionAdd
     */
    add : function(type, namedId, schema, edit){
      qx.core.Assert.assertString(type);
      qx.core.Assert.assertString(namedId);
      if(schema!==null) qx.core.Assert.assertString(schema);
      qx.core.Assert.assertBoolean(edit);
      return qx.core.Init.getApplication().getRpcClient("access-config").send("add", [type, namedId, schema, edit]);
    },

    /**
     * Edit the element data by returning a form to the user
     * 
     * @param first {String|bool} The type of the element or boolean true
     * @param second {String} The namedId of the element
     * @param third {|string} If the first argument is boolean true, then the second and third
     * arguments are the normal signature
     * @return {Promise}
     * @see AccessConfigController::actionEdit
     */
    edit : function(first, second, third){
      qx.core.Assert.assertString(first);
      qx.core.Assert.assertString(second);
      // @todo Document type for 'third' in app\controllers\AccessConfigController::actionEdit
      return qx.core.Init.getApplication().getRpcClient("access-config").send("edit", [first, second, third]);
    },

    /**
     * Save the form produced by edit()
     * 
     * @param data The form data or null if the user cancelled the form
     * @param type {String|null} The type of the model or null if the user cancelled the form
     * @param namedId {String|null} The namedId of the model or null if the user cancelled the form
     * @return {Promise}
     * @see AccessConfigController::actionSave
     */
    save : function(data, type, namedId){
      // @todo Document type for 'data' in app\controllers\AccessConfigController::actionSave
      if(type!==null) qx.core.Assert.assertString(type);
      if(namedId!==null) qx.core.Assert.assertString(namedId);
      return qx.core.Init.getApplication().getRpcClient("access-config").send("save", [data, type, namedId]);
    },

    /**
     * Delete a model record
     * 
     * @param type {String} The type of the model
     * @param ids An array of ids to delete
     * @return {Promise}
     * @see AccessConfigController::actionDelete
     */
    delete : function(type, ids){
      qx.core.Assert.assertString(type);
      // @todo Document type for 'ids' in app\controllers\AccessConfigController::actionDelete
      return qx.core.Init.getApplication().getRpcClient("access-config").send("delete", [type, ids]);
    },

    /**
     * Delete a datasource
     * 
     * @param doDeleteModelData {Boolean} 
     * @param namedId {String} 
     * @return {Promise}
     * @see AccessConfigController::actionDeleteDatasource
     */
    deleteDatasource : function(doDeleteModelData, namedId){
      qx.core.Assert.assertBoolean(doDeleteModelData);
      qx.core.Assert.assertString(namedId);
      return qx.core.Init.getApplication().getRpcClient("access-config").send("delete-datasource", [doDeleteModelData, namedId]);
    },

    /**
     * Link two model records
     * 
     * @param linkedModelData {String} A string consisting of type=namedId pairs, separated by commas, defining
     * what models should be linked to the main model
     * @param type {String} The type of the current element
     * @param namedId {String} The named id of the current element
     * @return {Promise}
     * @see AccessConfigController::actionLink
     */
    link : function(linkedModelData, type, namedId){
      qx.core.Assert.assertString(linkedModelData);
      qx.core.Assert.assertString(type);
      qx.core.Assert.assertString(namedId);
      return qx.core.Init.getApplication().getRpcClient("access-config").send("link", [linkedModelData, type, namedId]);
    },

    /**
     * Unlink two model records
     * 
     * @param linkedModelData 
     * @param type 
     * @param namedId 
     * @return {Promise}
     * @see AccessConfigController::actionUnlink
     */
    unlink : function(linkedModelData, type, namedId){
      // @todo Document type for 'linkedModelData' in app\controllers\AccessConfigController::actionUnlink
      // @todo Document type for 'type' in app\controllers\AccessConfigController::actionUnlink
      // @todo Document type for 'namedId' in app\controllers\AccessConfigController::actionUnlink
      return qx.core.Init.getApplication().getRpcClient("access-config").send("unlink", [linkedModelData, type, namedId]);
    },

    /**
     * Presents the user with a form to enter user data
     * 
     * @return {Promise}
     * @see AccessConfigController::actionNewUserDialog
     */
    newUserDialog : function(){
      return qx.core.Init.getApplication().getRpcClient("access-config").send("new-user-dialog", []);
    },

    /**
     * Action to add a new user
     * 
     * @param data 
     * @return {Promise}
     * @see AccessConfigController::actionAddUser
     */
    addUser : function(data){
      // @todo Document type for 'data' in app\controllers\AccessConfigController::actionAddUser
      return qx.core.Init.getApplication().getRpcClient("access-config").send("add-user", [data]);
    },

    /**
     * Creates a new datasource, allowing the user to choose the schema
     * 
     * @return {Promise}
     * @see AccessConfigController::actionCreateDatasourceDialog
     */
    createDatasourceDialog : function(){
      return qx.core.Init.getApplication().getRpcClient("access-config").send("create-datasource-dialog", []);
    },

    /**
     * 
     * 
     * @param formData 
     * @return {Promise}
     * @see AccessConfigController::actionCreateDatasourceHandler
     */
    createDatasourceHandler : function(formData){
      // @todo Document type for 'formData' in app\controllers\AccessConfigController::actionCreateDatasourceHandler
      return qx.core.Init.getApplication().getRpcClient("access-config").send("create-datasource-handler", [formData]);
    },

    /**
     * Presents the user a form to enter the data of a new datasource to be created
     * 
     * @return {Promise}
     * @see AccessConfigController::actionNewDatasourceDialog
     */
    newDatasourceDialog : function(){
      return qx.core.Init.getApplication().getRpcClient("access-config").send("new-datasource-dialog", []);
    },

    /**
     * Action to add a new datasource from client-supplied data
     * 
     * @param data {Object} 
     * @return {Promise}
     * @see AccessConfigController::actionAddDatasource
     */
    addDatasource : function(data){
      qx.core.Assert.assertObject(data);
      return qx.core.Init.getApplication().getRpcClient("access-config").send("add-datasource", [data]);
    },

    /**
     * 
     * 
     * @param $class {String} 
     * @return {Promise}
     * @see AccessConfigController::actionSchemaclassExists
     */
    schemaclassExists : function($class){
      qx.core.Assert.assertString($class);
      return qx.core.Init.getApplication().getRpcClient("access-config").send("schemaclass-exists", [$class]);
    }
  }
});