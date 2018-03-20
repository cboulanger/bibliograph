/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * A setup a specific version of the application. This is mainly for testing.
 * 
 * @see app\controllers\SseController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/SseController.php
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
      return this.getApplication().getRpcClient("sse").send("test", []);
    },

    /**
     * Endpoint for the EventSource URL
     * 
     * @return {Promise}
     * @see SseController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("sse").send("index", []);
    },

    /**
     * Server Side Events source mainly for testing that will
     * return the current time
     * 
     * @return {Promise}
     * @see SseController::actionTime
     */
    time : function(){
      return this.getApplication().getRpcClient("sse").send("time", []);
    }
  }
});