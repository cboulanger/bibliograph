/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\SseController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/SseController.php
 */
qx.Class.define("rpc.Sse",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @return {Promise}
     */
    test : function(){
      return this.getApplication().getRpcClient("sse").send("test", []);
    },

    /**
     * 
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("sse").send("index", []);
    },

    /**
     * 
     * @return {Promise}
     */
    time : function(){
      return this.getApplication().getRpcClient("sse").send("time", []);
    }
  }
});