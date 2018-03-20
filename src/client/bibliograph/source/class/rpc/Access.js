qx.Class.define("rpc.Access",
{ 
  extend: qx.core.Object,
  statics: {

    /**
     * @param username
     * @param password
     * @param data
     * @return {Promise}
     */
    register : function(username=null, password=null, data=null){



      return this.getApplication().getRpcClient("access").send("register", [username, password, data]);
    },

    /**
     * @param username
     * @return {Promise}
     */
    challenge : function(username=null){

      return this.getApplication().getRpcClient("access").send("challenge", [username]);
    },

    /**

     * @return {Promise}
     */
    ldapSupport : function(){

      return this.getApplication().getRpcClient("access").send("ldap-support", []);
    },

    /**
     * @param first
     * @param password
     * @return {Promise}
     */
    authenticate : function(first=null, password=null){


      return this.getApplication().getRpcClient("access").send("authenticate", [first, password]);
    },

    /**

     * @return {Promise}
     */
    logout : function(){

      return this.getApplication().getRpcClient("access").send("logout", []);
    },

    /**

     * @return {Promise}
     */
    renewPassword : function(){

      return this.getApplication().getRpcClient("access").send("renew-password", []);
    },

    /**

     * @return {Promise}
     */
    username : function(){

      return this.getApplication().getRpcClient("access").send("username", []);
    },

    /**

     * @return {Promise}
     */
    userdata : function(){

      return this.getApplication().getRpcClient("access").send("userdata", []);
    },

    /**

     * @return {Promise}
     */
    count : function(){

      return this.getApplication().getRpcClient("access").send("count", []);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("access").send("index", []);
    },
    ___eof : null
  }
});