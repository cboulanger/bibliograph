/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\SetupController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/SetupController.php
 */
qx.Class.define("rpc.Setup",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @return {Promise}
     */
    version : function(){
      return this.getApplication().getRpcClient("setup").send("version", []);
    },

    /**
     * 
     * @return {Promise}
     */
    confirmMigrations : function(){
      return this.getApplication().getRpcClient("setup").send("confirm-migrations", []);
    },

    /**
     * 
     * @return {Promise}
     */
    setup : function(){
      return this.getApplication().getRpcClient("setup").send("setup", []);
    },

    /**
     * 
     * @param upgrade_to (optional) The version to upgrade from.
     * @param upgrade_from (optional) The version to upgrade to.
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
    }
  }
});