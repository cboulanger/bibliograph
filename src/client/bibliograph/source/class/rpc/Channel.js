/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
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
     */
    send : function(name=null, data=null){


      return this.getApplication().getRpcClient("channel").send("send", [name, data]);
    },

    /**
     * @param name 
     * @param data 
     * @return {Promise}
     */
    broadcast : function(name=null, data=null){


      return this.getApplication().getRpcClient("channel").send("broadcast", [name, data]);
    },

    /**
     * @return {Promise}
     */
    fetch : function(){
      return this.getApplication().getRpcClient("channel").send("fetch", []);
    },

    /**
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("channel").send("index", []);
    }
  }
});