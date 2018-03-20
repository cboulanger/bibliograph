qx.Class.define("rpc.Channel",
{ 
  extend: qx.core.Object,
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
    },
    ___eof : null
  }
});