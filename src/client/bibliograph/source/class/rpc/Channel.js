/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * The class used for authentication of users. Adds LDAP authentication
 * 
 * @see app\controllers\ChannelController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/ChannelController.php
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
    send : function(name=null, data=null){
      // @todo Document type for 'name' in app\controllers\ChannelController::actionSend
      // @todo Document type for 'data' in app\controllers\ChannelController::actionSend
      return this.getApplication().getRpcClient("channel").send("send", [name, data]);
    },

    /**
     * @param name 
     * @param data 
     * @return {Promise}
     * @see ChannelController::actionBroadcast
     */
    broadcast : function(name=null, data=null){
      // @todo Document type for 'name' in app\controllers\ChannelController::actionBroadcast
      // @todo Document type for 'data' in app\controllers\ChannelController::actionBroadcast
      return this.getApplication().getRpcClient("channel").send("broadcast", [name, data]);
    },

    /**
     * @return {Promise}
     * @see ChannelController::actionFetch
     */
    fetch : function(){
      return this.getApplication().getRpcClient("channel").send("fetch", []);
    },

    /**
     * @return {Promise}
     * @see ChannelController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("channel").send("index", []);
    }
  }
});