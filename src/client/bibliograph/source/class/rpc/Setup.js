qx.Class.define("rpc.Setup",
{ 
  extend: qx.core.Object,
  statics: {

    /**

     * @return {Promise}
     */
    version : function(){

      return this.getApplication().getRpcClient("setup").send("version", []);
    },

    /**

     * @return {Promise}
     */
    confirmMigrations : function(){

      return this.getApplication().getRpcClient("setup").send("confirm-migrations", []);
    },

    /**

     * @return {Promise}
     */
    setup : function(){

      return this.getApplication().getRpcClient("setup").send("setup", []);
    },

    /**
     * @param upgrade_to
     * @param upgrade_from
     * @return {Promise}
     */
    setupVersion : function(upgrade_to=null, upgrade_from=null){


      return this.getApplication().getRpcClient("setup").send("setup-version", [upgrade_to, upgrade_from]);
    },

    /**

     * @return {Promise}
     */
    index : function(){

      return this.getApplication().getRpcClient("setup").send("index", []);
    },
    ___eof : null
  }
});