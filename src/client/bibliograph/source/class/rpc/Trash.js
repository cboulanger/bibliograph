/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * 
 * @see app\controllers\TrashController
 * @file TrashController.php
 */
qx.Class.define("rpc.Trash",
{ 
  type: 'static',
  statics: {
    /**
     * Empties the trash folder
     * 
     * @param datasource {String} 
     * @return {Promise}
     * @see TrashController::actionEmpty
     */
    empty : function(datasource){
      qx.core.Assert.assertString(datasource);
      return qx.core.Init.getApplication().getRpcClient("trash").send("empty", [datasource]);
    }
  }
});