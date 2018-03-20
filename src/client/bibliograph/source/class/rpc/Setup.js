/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Setup controller. Needs to be the first controller called
 * by the application after loading
 * 
 * @see app\controllers\SetupController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/SetupController.php
 */
qx.Class.define("rpc.Setup",
{ 
  type: 'static',
  statics: {
    /**
     * Returns the application verision as per package.json
     * 
     * @return {Promise}
     * @see SetupController::actionVersion
     */
    version : function(){
      return this.getApplication().getRpcClient("setup").send("version", []);
    },

    /**
     * Called by the confirm dialog
     * 
     * @return {Promise}
     * @see SetupController::actionConfirmMigrations
     */
    confirmMigrations : function(){
      return this.getApplication().getRpcClient("setup").send("confirm-migrations", []);
    },

    /**
     * The setup action. Is called as first server method from the client
     * 
     * @return {Promise}
     * @see SetupController::actionSetup
     */
    setup : function(){
      return this.getApplication().getRpcClient("setup").send("setup", []);
    },

    /**
     * A setup a specific version of the application. This is mainly for testing.
     * 
     * @param upgrade_to {String} (optional) The version to upgrade from.
     * @param upgrade_from {String} (optional) The version to upgrade to.
     * @return {Promise}
     * @see SetupController::actionSetupVersion
     */
    setupVersion : function(upgrade_to=null, upgrade_from=null){
      qx.core.Assert.assertString(upgrade_to);
      qx.core.Assert.assertString(upgrade_from);
      return this.getApplication().getRpcClient("setup").send("setup-version", [upgrade_to, upgrade_from]);
    },

    /**
     * @return {Promise}
     * @see SetupController::actionIndex
     */
    index : function(){
      return this.getApplication().getRpcClient("setup").send("index", []);
    }
  }
});