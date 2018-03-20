/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Backend service class for the access control tool widget
 * 
 * @see app\controllers\AccessConfigController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/AccessConfigController.php
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
      return this.getApplication().getRpcClient("access-config").send("types", []);
    },

    /**
     * Return ListItem data for access models
     * 
     * @param type {String} The type of the element
     * @param filter {Array} An associative array that can be used in a ActiveQuery::where() method call
     * @return {Promise}
     * @see AccessConfigController::actionElements
     */
    elements : function(type, filter=null){
      qx.core.Assert.assertString(type);
      qx.core.Assert.assertArray(filter);
      return this.getApplication().getRpcClient("access-config").send("elements", [type, filter]);
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
    data : function(type=null, namedId=null){
      // @todo Document type for 'type' in app\controllers\AccessConfigController::actionData
      // @todo Document type for 'namedId' in app\controllers\AccessConfigController::actionData
      return this.getApplication().getRpcClient("access-config").send("data", [type, namedId]);
    },

    /**
     * Returns the tree of model relationships based on the selected element
     * 
     * @param elementType 
     * @param namedId 
     * @return {Promise}
     * @see AccessConfigController::actionTree
     */
    tree : function(elementType=null, namedId=null){
      // @todo Document type for 'elementType' in app\controllers\AccessConfigController::actionTree
      // @todo Document type for 'namedId' in app\controllers\AccessConfigController::actionTree
      return this.getApplication().getRpcClient("access-config").send("tree", [elementType, namedId]);
    },

    /**
     * Add an empty model record. When creating a datasource,
     * a default bibliograph datasource is created.
     * Creates the form editor
     * @param type {String} 
     * @param namedId {String} 
     * @param edit {Boolean} 
     * @return {Promise}
     * @see AccessConfigController::actionAdd
     */
    add : function(type=null, namedId=null, edit=null){
      qx.core.Assert.assertString(type);
      qx.core.Assert.assertString(namedId);
      qx.core.Assert.assertBoolean(edit);
      return this.getApplication().getRpcClient("access-config").send("add", [type, namedId, edit]);
    },

    /**
     * Edit the element data by returning a form to the user
     * 
     * @param first The type of the element or boolean true
     * @param second {String} The namedId of the element
     * @param third If the first argument is boolean true, then the second and third
     * arguments are the normal signature
     * @return {Promise}
     * @see AccessConfigController::actionEdit
     */
    edit : function(first=null, second=null, third=null){
      // @todo Document type for 'first' in app\controllers\AccessConfigController::actionEdit
      qx.core.Assert.assertString(second);
      // @todo Document type for 'third' in app\controllers\AccessConfigController::actionEdit
      return this.getApplication().getRpcClient("access-config").send("edit", [first, second, third]);
    },

    /**
     * Save the form produced by edit()
     * 
     * @param data The form data or null if the user cancelled the form
     * @param type The type of the model or null if the user cancelled the form
     * @param namedId The namedId of the model or null if the user cancelled the form
     * @return {Promise}
     * @see AccessConfigController::actionSave
     */
    save : function(data=null, type=null, namedId=null){
      // @todo Document type for 'data' in app\controllers\AccessConfigController::actionSave
      // @todo Document type for 'type' in app\controllers\AccessConfigController::actionSave
      // @todo Document type for 'namedId' in app\controllers\AccessConfigController::actionSave
      return this.getApplication().getRpcClient("access-config").send("save", [data, type, namedId]);
    },

    /**
     * Delete a model record
     * 
     * @param type {String} The type of the model
     * @param ids An array of ids to delete
     * @return {Promise}
     * @see AccessConfigController::actionDelete
     */
    delete : function(type=null, ids=null){
      qx.core.Assert.assertString(type);
      // @todo Document type for 'ids' in app\controllers\AccessConfigController::actionDelete
      return this.getApplication().getRpcClient("access-config").send("delete", [type, ids]);
    },

    /**
     * Delete a datasource
     * 
     * @param doDeleteModelData 
     * @param namedId 
     * @return {Promise}
     * @see AccessConfigController::actionDeleteDatasource
     */
    deleteDatasource : function(doDeleteModelData=null, namedId=null){
      // @todo Document type for 'doDeleteModelData' in app\controllers\AccessConfigController::actionDeleteDatasource
      // @todo Document type for 'namedId' in app\controllers\AccessConfigController::actionDeleteDatasource
      return this.getApplication().getRpcClient("access-config").send("delete-datasource", [doDeleteModelData, namedId]);
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
    link : function(linkedModelData=null, type=null, namedId=null){
      qx.core.Assert.assertString(linkedModelData);
      qx.core.Assert.assertString(type);
      qx.core.Assert.assertString(namedId);
      return this.getApplication().getRpcClient("access-config").send("link", [linkedModelData, type, namedId]);
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
    unlink : function(linkedModelData=null, type=null, namedId=null){
      // @todo Document type for 'linkedModelData' in app\controllers\AccessConfigController::actionUnlink
      // @todo Document type for 'type' in app\controllers\AccessConfigController::actionUnlink
      // @todo Document type for 'namedId' in app\controllers\AccessConfigController::actionUnlink
      return this.getApplication().getRpcClient("access-config").send("unlink", [linkedModelData, type, namedId]);
    },

    /**
     * Presents the user with a form to enter user data
     * 
     * @return {Promise}
     * @see AccessConfigController::actionNewUserDialog
     */
    newUserDialog : function(){
      return this.getApplication().getRpcClient("access-config").send("new-user-dialog", []);
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
      return this.getApplication().getRpcClient("access-config").send("add-user", [data]);
    },

    /**
     * Presents the user a form to enter the data of a new datasource to be created
     * 
     * @return {Promise}
     * @see AccessConfigController::actionNewDatasourceDialog
     */
    newDatasourceDialog : function(){
      return this.getApplication().getRpcClient("access-config").send("new-datasource-dialog", []);
    },

    /**
     * Action to add a new datasource from client-supplied data
     * 
     * @param data {Object} 
     * @return {Promise}
     * @see AccessConfigController::actionAddDatasource
     */
    addDatasource : function(data=null){
      qx.core.Assert.assertObject(data);
      return this.getApplication().getRpcClient("access-config").send("add-datasource", [data]);
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
      return this.getApplication().getRpcClient("access-config").send("schemaclass-exists", [$class]);
    },

    /**
     * @return {Promise}
     * @see AccessConfigController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("access-config").send("index", []);
    }
  }
});