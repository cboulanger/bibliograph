/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright: 
    2018 Christian Boulanger

  License: 
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors: 
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * This is a qooxdoo singleton class
 *
 */
qx.Class.define("bibliograph.Commands",
{
  extend : qx.core.Object,
  include : [qcl.ui.MLoadingPopup,qx.locale.MTranslation],
  type : "singleton",
  members :
  {
    /*
    ---------------------------------------------------------------------------
       LOGIN & LOGOUT
    ---------------------------------------------------------------------------
    */

    /**
     * Called when the user presses the "login" button
     */
    showLoginDialog : async function()
    {
      let app = this.getApplication();
      // check if https login is enforced
      var enforce_https = app.getConfigManager().getKey("access.enforce_https_login");
      if (enforce_https && location.protocol != "https:") {
        let msg = this.tr("To log in, you need a secure connection. After you press 'OK', the application will be reloaded in secure mode. After the application finished loading, you can log in again.");
        await dialog.Dialog.alert(msg).promise();
        location.href = "https://" + location.host + location.pathname + location.hash;
      } else {
        // check if access is restricted
        if ( app.getConfigManager().getKey("bibliograph.access.mode" ) == "readonly" && 
          !this.__readonlyConfirmed) {
          var msg = this.tr("The application is currently in a read-only state. Only the administrator can log in.");
          var explanation = this.getConfigManager().getKey("bibliograph.access.no-access-message");
          if (explanation) {
            msg += "\n" + explanation;
          }
          await dialog.Dialog.alert(msg).promise();
          this.__readonlyConfirmed = true
        } else {
          // else show login dialog
          this.getWidgetById("bibliograph/loginDialog").show();
        }
      }
    },

    /**
     * Callback function that takes the username, password and
     * another callback function as parameters.
     * The passed function is called with a boolean value
     * (true=authenticated, false=authentication failed) and an
     * optional string value which can contain an error message :
     * callback( {Boolean} result, {String} message);
     *
     * @param username {String} TODOC
     * @param password {String} TODOC
     * @return {Promise<void>}
     */
    checkLogin : async function(username)
    {
      var app = qx.core.Init.getApplication();
      app.showPopup(app.tr("Authenticating ..."));
      let data = this.authenticate(username, password);
      app.hidePopup();
      if (data.error) {
        return data.error;
      } 
      await app.getConfigManager().load();
      app.hidePopup();
      qx.event.message.Bus.dispatch(new qx.event.message.Message("authenticated"));
    },

    /**
     * called when user clicks on the "forgot password?" button
     */
    forgotPassword : async function()
    {
      this.showPopup(this.tr("Please wait ..."));
      await this.getApplication().getRpcClient("actool").send("resetPasswordDialog");
      this.hidePopup();
    },
       
    /**
     * Log out current user on the server
     * @param callback {function|undefined} optional callback that is called
     * when logout request returns from server.
     * @param context {object|undefined} Optional context for callback function
     * @return {Promise<Object>}
     */
    logout : async function( callback, context )
    {
      let app = this.getApplication();

      this.showPopup( this.tr("Logging out ...") );
      qx.event.message.Bus.dispatch( new qx.event.message.Message("logout", true ) );

      // remove state
      app.setFolderId(null);
      app.setModelId(null);

      await this.getApplication().getRpcClient('access').notify("logout");
      // re-login as guest
      await this.authenticateAsGuest();
      await this.load();
      await this.getApplication().getConfigManager().load();

      this.hidePopup();
      qx.event.message.Bus.dispatch( new qx.event.message.Message("loggedOut")); // @todo: do we need this??
    },

   /*
    ---------------------------------------------------------------------------
       Toolbar commands
    ---------------------------------------------------------------------------
    */

    /**
     * opens a window with the online help
     */
    showHelpWindow : function(topic) {
      var url = this.getApplication().getServerUrl() +
          "?sessionId=" + this.getAccessManager().getSessionId() +
          "&service=bibliograph.main&method=getOnlineHelpUrl&params=" + (topic||"home");
      this.__helpWindow = window.open(url,"bibliograph-help-window");
      if (!this.__helpWindow) {
        dialog.Dialog.alert(this.tr("Cannot open window. Please disable the popup-blocker of your browser for this website."));
      }
      this.__helpWindow.focus();
    },

    /**
     * Opens a server dialog to submit a bug.
     */
    reportBug : function()
    {
      this.showPopup(this.tr("Please wait ..."));
      this.getRpcClient("main").send("reportBugDialog", [], function() {
        this.hidePopup();
      }, this);
    },

    /**
     * Shows the "about" window
     */
    showAboutWindow : function() {
      this.getWidgetById("bibliograph/aboutWindow").open();
    },

    /*
    ---------------------------------------------------------------------------
       HELPER METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * Prints the content of the given dom element, by opening up a new window,
     * copying the content of the element to this new window, and starting the
     * print.
     *
     * @param domElement {Element}
     */
    print : function(domElement)
    {
      if (!domElement instanceof Element)
      {
        this.error("print() takes a DOM element as argument");
        return;
      }
      var win = window.open();
      win.document.open();
      win.document.write(domElement.innerHTML);
      win.document.close();
      win.print();
    },

    /**
     * Helper function for converters in list databinding. If a selected element
     * exist, returns its model value, otherwise return null
     *
     * @param selection {Array} TODOC
     * @return {String | null} TODOC
     */
    getSelectionValue : function(selection) {
      return selection.length ? selection[0].getModel().getValue() : null;
    },

    editUserData : function()
    {
      var activeUser = this.getAccessManager().getActiveUser();
      if (activeUser.getEditable())
      {
        this.showPopup(this.tr("Retrieving user data..."));
        this.getRpcClient("acl").send("editElement", ["user", activeUser.getNamedId()], function() {
          this.hidePopup()
        }, this);
      }
    },
    endOfFile : true
  }
});