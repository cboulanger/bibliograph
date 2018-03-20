/** FILE IS GENERATED, ANY CHANGES WILL BE OVERWRITTEN */

/**
 * 
 * @see app\controllers\EmailController
 * @file /Users/cboulanger/Code/bibliograph/src/server/controllers/EmailController.php
 */
qx.Class.define("rpc.Email",
{ 
  type: 'static',
  statics: {
    /**
     * 
     * @param type 
     * @param namedId 
     * @param subject 
     * @param body 
     * @return {Promise}
     */
    emailCompose : function(type=null, namedId=null, subject=null, body=null){




      return this.getApplication().getRpcClient("email").send("email-compose", [type, namedId, subject, body]);
    },

    /**
     * @param data 
     * @param shelfId 
     * @return {Promise}
     */
    emailConfirm : function(data=null, shelfId=null){


      return this.getApplication().getRpcClient("email").send("email-confirm", [data, shelfId]);
    },

    /**
     * @param dummy 
     * @param shelfId 
     * @param data 
     * @return {Promise}
     */
    emailCorrect : function(dummy=null, shelfId=null, data=null){



      return this.getApplication().getRpcClient("email").send("email-correct", [dummy, shelfId, data]);
    },

    /**
     * @param confirm 
     * @param shelfId 
     * @param data 
     * @return {Promise}
     */
    emailSend : function(confirm=null, shelfId=null, data=null){



      return this.getApplication().getRpcClient("email").send("email-send", [confirm, shelfId, data]);
    },

    /**
     * @param namedId 
     * @return {Promise}
     */
    missingPassword : function(namedId=null){

      return this.getApplication().getRpcClient("email").send("missing-password", [namedId]);
    },

    /**
     * 
     * @param namedId 
     * @return {Promise}
     */
    confirmRegistration : function(namedId=null){

      return this.getApplication().getRpcClient("email").send("confirm-registration", [namedId]);
    },

    /**
     * 
     * @return {Promise}
     */
    resetPasswordDialog : function(){
      return this.getApplication().getRpcClient("email").send("reset-password-dialog", []);
    },

    /**
     * 
     * @param email 
     * @return {Promise}
     */
    passwortResetEmail : function(email=null){

      return this.getApplication().getRpcClient("email").send("passwort-reset-email", [email]);
    },

    /**
     * 
     * @param email 
     * @param nonce 
     * @return {Promise}
     */
    resetPassword : function(email=null, nonce=null){


      return this.getApplication().getRpcClient("email").send("reset-password", [email, nonce]);
    },

    /**
     * @return {Promise}
     */
    index : function(){
      return this.getApplication().getRpcClient("email").send("index", []);
    }
  }
});