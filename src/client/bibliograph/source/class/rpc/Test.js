/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\TestController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/TestController.php
 */
qx.Class.define("rpc.Test",
{ 
  type: 'static',
  statics: {
    /**
     * @return {Promise}
     */
    error : function(){
      return this.getApplication().getRpcClient("test").send("error", []);
    },

    /**
     * @return {Promise}
     */
    test : function(){
      return this.getApplication().getRpcClient("test").send("test", []);
    },

    /**
     * @param result 
     * @param message 
     * @return {Promise}
     */
    test2 : function(result=null, message=null){


      return this.getApplication().getRpcClient("test").send("test2", [result, message]);
    },

    /**
     * @param message 
     * @return {Promise}
     */
    alert : function(message=null){

      return this.getApplication().getRpcClient("test").send("alert", [message]);
    },

    /**
     * @return {Promise}
     */
    simpleEvent : function(){
      return this.getApplication().getRpcClient("test").send("simple-event", []);
    },

    /**
     * 
     * @param json 
     * @return {Promise}
     */
    shelve : function(json=null){

      return this.getApplication().getRpcClient("test").send("shelve", [json]);
    },

    /**
     * 
     * @param shelfId 
     * @return {Promise}
     */
    unshelve : function(shelfId=null){

      return this.getApplication().getRpcClient("test").send("unshelve", [shelfId]);
    },

    /**
     * @return {Promise}
     */
    createSearch : function(){
      return this.getApplication().getRpcClient("test").send("create-search", []);
    },

    /**
     * @return {Promise}
     */
    retrieveSearch : function(){
      return this.getApplication().getRpcClient("test").send("retrieve-search", []);
    },

    /**
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("test").send("index", []);
    }
  }
});