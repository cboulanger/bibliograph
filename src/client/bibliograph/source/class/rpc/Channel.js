/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * The controller for PubSub communication
 * 
 * @see app\controllers\ChannelController
 * @file ChannelController.php
 */
qx.Class.define("rpc.Channel",
{ 
  type: 'static',
  statics: {
    /**
     * @param name 
     * @param data 
     * @return {Promise}
     * @see ChannelController::actionSend
     */
    send : function(name, data){
      // @todo Document type for 'name' in app\controllers\ChannelController::actionSend
      // @todo Document type for 'data' in app\controllers\ChannelController::actionSend
      return qx.core.Init.getApplication().getRpcClient("channel").send("send", [name, data]);
    },

    /**
     * @param name 
     * @param data 
     * @return {Promise}
     * @see ChannelController::actionBroadcast
     */
    broadcast : function(name, data){
      // @todo Document type for 'name' in app\controllers\ChannelController::actionBroadcast
      // @todo Document type for 'data' in app\controllers\ChannelController::actionBroadcast
      return qx.core.Init.getApplication().getRpcClient("channel").send("broadcast", [name, data]);
    },

    /**
     * @return {Promise}
     * @see ChannelController::actionFetch
     */
    fetch : function(){
      return qx.core.Init.getApplication().getRpcClient("channel").send("fetch", []);
    }
  }
});