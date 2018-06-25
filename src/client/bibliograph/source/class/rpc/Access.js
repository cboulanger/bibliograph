/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * The class used for authentication of users.
 * 
 * @see app\controllers\AccessController
 * @file AccessController.php
 */
qx.Class.define("rpc.Access",
{ 
  type: 'static',
  statics: {
    /**
     * Given a username, return a string consisting of a random hash and the salt
     * used to hash the password of that user, concatenated by "|"
     * 
     * @param username 
     * @return {Promise}
     * @see AccessController::actionChallenge
     */
    challenge : function(username){
      // @todo Document type for 'username' in app\controllers\AccessController::actionChallenge
      return qx.core.Init.getApplication().getRpcClient("access").send("challenge", [username]);
    },

    /**
     * Action to check if LDAP authentication is supported.
     * 
     * @return {Promise}
     * @see AccessController::actionLdapSupport
     */
    ldapSupport : function(){
      return qx.core.Init.getApplication().getRpcClient("access").send("ldap-support", []);
    },

    /**
     * Exposes {@see AccessController::authenticate} as a controller action
     * 
     * @param first {String|null} Either a token (then the second param must be null), a username (then the seconde
     * param must be the password, or null, then the user logs in anonymously
     * @param password {String|null} 
     * @return {Promise}
     * @see AccessController::actionAuthenticate
     */
    authenticate : function(first, password){
      if(first!==null) qx.core.Assert.assertString(first);
      if(password!==null) qx.core.Assert.assertString(password);
      return qx.core.Init.getApplication().getRpcClient("access").send("authenticate", [first, password]);
    },

    /**
     * Logs out the current user and destroys all session data
     * 
     * @return {Promise}
     * @see AccessController::actionLogout
     */
    logout : function(){
      return qx.core.Init.getApplication().getRpcClient("access").send("logout", []);
    },

    /**
     * 
     * 
     * @return {Promise}
     * @see AccessController::actionRenewPassword
     */
    renewPassword : function(){
      return qx.core.Init.getApplication().getRpcClient("access").send("renew-password", []);
    },

    /**
     * Returns the username of the current user. Mainly for testing purposes.
     * 
     * @return {Promise}
     * @see AccessController::actionUsername
     */
    username : function(){
      return qx.core.Init.getApplication().getRpcClient("access").send("username", []);
    },

    /**
     * Returns the data of the current user, including permissions.
     * 
     * @return {Promise}
     * @see AccessController::actionUserdata
     */
    userdata : function(){
      return qx.core.Init.getApplication().getRpcClient("access").send("userdata", []);
    },

    /**
     * Returns the times this action has been called. Only for testing session storage.
     * 
     * @return {Promise}
     * @see AccessController::actionCount
     */
    count : function(){
      return qx.core.Init.getApplication().getRpcClient("access").send("count", []);
    },

    /**
     * Returns information on users which are/recently have been online
     * 
     * @return {Promise}
     * @see AccessController::actionUsersOnline
     */
    usersOnline : function(){
      return qx.core.Init.getApplication().getRpcClient("access").send("users-online", []);
    }
  }
});