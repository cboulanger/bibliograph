/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * Backend service class for the access control tool widget
 * 
 * @see app\controllers\EmailController
 * @file EmailController.php
 */
qx.Class.define("rpc.Email",
{ 
  type: 'static',
  statics: {
    /**
     * Sends an informational email to different groups of
     * 
     * @param type 
     * @param namedId 
     * @param subject 
     * @param body 
     * @return {Promise}
     * @see EmailController::actionEmailCompose
     */
    emailCompose : function(type, namedId, subject, body){
      // @todo Document type for 'type' in app\controllers\EmailController::actionEmailCompose
      // @todo Document type for 'namedId' in app\controllers\EmailController::actionEmailCompose
      // @todo Document type for 'subject' in app\controllers\EmailController::actionEmailCompose
      // @todo Document type for 'body' in app\controllers\EmailController::actionEmailCompose
      return qx.core.Init.getApplication().getRpcClient("email").send("email-compose", [type, namedId, subject, body]);
    },

    /**
     * @param data 
     * @param shelfId 
     * @return {Promise}
     * @see EmailController::actionEmailConfirm
     */
    emailConfirm : function(data, shelfId){
      // @todo Document type for 'data' in app\controllers\EmailController::actionEmailConfirm
      // @todo Document type for 'shelfId' in app\controllers\EmailController::actionEmailConfirm
      return qx.core.Init.getApplication().getRpcClient("email").send("email-confirm", [data, shelfId]);
    },

    /**
     * @param dummy 
     * @param shelfId 
     * @param data 
     * @return {Promise}
     * @see EmailController::actionEmailCorrect
     */
    emailCorrect : function(dummy, shelfId, data){
      // @todo Document type for 'dummy' in app\controllers\EmailController::actionEmailCorrect
      // @todo Document type for 'shelfId' in app\controllers\EmailController::actionEmailCorrect
      // @todo Document type for 'data' in app\controllers\EmailController::actionEmailCorrect
      return qx.core.Init.getApplication().getRpcClient("email").send("email-correct", [dummy, shelfId, data]);
    },

    /**
     * @param confirm 
     * @param shelfId 
     * @param data 
     * @return {Promise}
     * @see EmailController::actionEmailSend
     */
    emailSend : function(confirm, shelfId, data){
      // @todo Document type for 'confirm' in app\controllers\EmailController::actionEmailSend
      // @todo Document type for 'shelfId' in app\controllers\EmailController::actionEmailSend
      // @todo Document type for 'data' in app\controllers\EmailController::actionEmailSend
      return qx.core.Init.getApplication().getRpcClient("email").send("email-send", [confirm, shelfId, data]);
    },

    /**
     * @param namedId 
     * @return {Promise}
     * @see EmailController::actionMissingPassword
     */
    missingPassword : function(namedId){
      // @todo Document type for 'namedId' in app\controllers\EmailController::actionMissingPassword
      return qx.core.Init.getApplication().getRpcClient("email").send("missing-password", [namedId]);
    },

    /**
     * Service to confirm a registration via email
     * 
     * @param namedId 
     * @return {Promise}
     * @see EmailController::actionConfirmRegistration
     */
    confirmRegistration : function(namedId){
      // @todo Document type for 'namedId' in app\controllers\EmailController::actionConfirmRegistration
      return qx.core.Init.getApplication().getRpcClient("email").send("confirm-registration", [namedId]);
    },

    /**
     * Displays a dialog to reset the password
     * 
     * @return {Promise}
     * @see EmailController::actionResetPasswordDialog
     */
    resetPasswordDialog : function(){
      return qx.core.Init.getApplication().getRpcClient("email").send("reset-password-dialog", []);
    },

    /**
     * Service to send password reset email
     * 
     * @param email 
     * @return {Promise}
     * @see EmailController::actionPasswortResetEmail
     */
    passwortResetEmail : function(email){
      // @todo Document type for 'email' in app\controllers\EmailController::actionPasswortResetEmail
      return qx.core.Init.getApplication().getRpcClient("email").send("passwort-reset-email", [email]);
    },

    /**
     * Service to reset email. Called by a REST request
     * 
     * @param email 
     * @param nonce 
     * @return {Promise}
     * @see EmailController::actionResetPassword
     */
    resetPassword : function(email, nonce){
      // @todo Document type for 'email' in app\controllers\EmailController::actionResetPassword
      // @todo Document type for 'nonce' in app\controllers\EmailController::actionResetPassword
      return qx.core.Init.getApplication().getRpcClient("email").send("reset-password", [email, nonce]);
    }
  }
});