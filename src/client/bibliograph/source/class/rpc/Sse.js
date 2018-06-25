/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * A setup a specific version of the application. This is mainly for testing.
 * 
 * @see app\controllers\SseController
 * @file SseController.php
 */
qx.Class.define("rpc.Sse",
{ 
  type: 'static',
  statics: {
    /**
     * Renders a pure HTML test client
     * 
     * @return {Promise}
     * @see SseController::actionTest
     */
    test : function(){
      return qx.core.Init.getApplication().getRpcClient("sse").send("test", []);
    },

    /**
     * Server Side Events source mainly for testing that will
     * return the current time
     * 
     * @return {Promise}
     * @see SseController::actionTime
     */
    time : function(){
      return qx.core.Init.getApplication().getRpcClient("sse").send("time", []);
    }
  }
});