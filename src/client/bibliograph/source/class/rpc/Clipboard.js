/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Class ClipboardController
 * Implements a user-level clipboard
 * 
 * @see app\controllers\ClipboardController
 * @file ClipboardController.php
 */
qx.Class.define("rpc.Clipboard",
{ 
  type: 'static',
  statics: {
    /**
     * Adds a clipboard entry for a given mime type and the current user
     * 
     * @param mime_type {String} 
     * @param data {String} 
     * @return {Promise}
     * @see ClipboardController::actionAdd
     */
    add : function(mime_type, data){
      qx.core.Assert.assertString(mime_type);
      qx.core.Assert.assertString(data);
      return qx.core.Init.getApplication().getRpcClient("clipboard").send("add", [mime_type, data]);
    },

    /**
     * Returns the current user's clipboard data for a given mime type
     * 
     * @param mime_type {String} 
     * @return {Promise}
     * @see ClipboardController::actionGet
     */
    get : function(mime_type){
      qx.core.Assert.assertString(mime_type);
      return qx.core.Init.getApplication().getRpcClient("clipboard").send("get", [mime_type]);
    }
  }
});