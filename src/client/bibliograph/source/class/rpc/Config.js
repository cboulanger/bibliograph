/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Service class providing methods to get or set configuration
 * values
 * 
 * @see app\controllers\ConfigController
 * @file ConfigController.php
 */
qx.Class.define("rpc.Config",
{ 
  type: 'static',
  statics: {
    /**
     * Service method to load config data
     * 
     * @param filter Filter
     * xxreturn \app\controllers\dto\ConfigLoadResult
     * @return {Promise}
     * @see ConfigController::actionLoad
     */
    load : function(filter){
      // @todo Document type for 'filter' in app\controllers\ConfigController::actionLoad
      return qx.core.Init.getApplication().getRpcClient("config").send("load", [filter]);
    },

    /**
     * Service method to set a config value
     * 
     * @param key {String} Key
     * @param value Value
     * @return {Promise}
     * @see ConfigController::actionSet
     */
    set : function(key, value){
      qx.core.Assert.assertString(key);
      // @todo Document type for 'value' in app\controllers\ConfigController::actionSet
      return qx.core.Init.getApplication().getRpcClient("config").send("set", [key, value]);
    },

    /**
     * Service method to get a config value
     * 
     * @param key {String} Key
     * @return {Promise}
     * @see ConfigController::actionGet
     */
    get : function(key){
      qx.core.Assert.assertString(key);
      return qx.core.Init.getApplication().getRpcClient("config").send("get", [key]);
    }
  }
});