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
 * This singleton class contains the commands that can be executed in menus and buttons.
 * Call them via qx.core.Init.getApplication().cmd("methodName",arg)
 *
 */
qx.Class.define("bibliograph.Commands",
{
  extend : qx.core.Object,
  include : [qcl.ui.MLoadingPopup, qx.locale.MTranslation],
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
    showLoginDialog : async function(value, app) {
      // check if https login is enforced
      let l= window.location;
      var enforce_https = app.getConfigManager().getKey("access.enforce_https_login");
      if (enforce_https && l.protocol != "https:") {
        let msg = this.tr("To log in, you need a secure connection. After you press 'OK', the application will be reloaded in secure mode. After the application finished loading, you can log in again.");
        await dialog.Dialog.alert(msg).promise();
        l.href = "https://" + l.host + l.pathname + l.hash;
      } else {
        // check if access is restricted
        // if ( app.getConfigManager().getKey("bibliograph.access.mode" ) == "readonly" &&
        //   !this.__readonlyConfirmed) {
        //   var msg = this.tr("The application is currently in a read-only state. Only the administrator can log in.");
        //   var explanation = this.getConfigManager().getKey("bibliograph.access.no-access-message");
        //   if (explanation) {
        //     msg += "\n" + explanation;
        //   }
        //   await dialog.Dialog.alert(msg).promise();
        //   this.__readonlyConfirmed = true
        // } else {
          // else show login dialog
          app.getWidgetById("app/windows/login").show();
        //}
      }
    },

    /**
     * called when user clicks on the "forgot password?" button
     */
    forgotPassword : async function(data, app) {
      app.showPopup(this.tr("Please wait ..."));
      await app.getApplication().getRpcClient("actool").send("resetPasswordDialog");
      app.hidePopup();
    },
    
    /**
     * Log out current user on the server
     * @return {Promise<Object>}
     */
    logout : async function(data, app) {
      if (!app) {
       app = this.getApplication();
      }
      qx.core.Id.getQxObject("toolbar/logout").setEnabled(false);
      app.createPopup();
      app.showPopup(this.tr("Logging out ..."));
      // remove state
      app.setFolderId(null);
      app.setModelId(null);
      // logout
      await app.getAccessManager().logout();
      qx.core.Id.getQxObject("toolbar/logout").setEnabled(true);
      app.hidePopup();
    },

   /*
    ---------------------------------------------------------------------------
       Toolbar commands
    ---------------------------------------------------------------------------
    */

    /**
     * opens a window with the online help
     */
    showHelpWindow : function(path) {
      let url = "https://sites.google.com/a/bibliograph.org/docs-v2-de/" + path;
      this.__helpWindow = window.open(url, "bibliograph-help-window");
      if (!this.__helpWindow) {
        dialog.Dialog.alert(this.tr("Cannot open window. Please disable the popup-blocker of your browser for this website."));
      }
      this.__helpWindow.focus();
    },

    /**
     * Shows the "about" window
     */
    showAboutWindow : function(data, app) {
      app.getWidgetById("app/windows/about").open();
    },
  
    /**
     * Edit the data of the current user
     * @param data
     * @param app
     * @return {Promise<void>}
     */
    editUserData : async function(data, app) {
      var activeUser = app.getAccessManager().getActiveUser();
      if (activeUser.getEditable()) {
        app.showPopup(this.tr("Retrieving user data..."));
        await rpc.AccessConfig.edit("user", activeUser.getNamedId());
        app.hidePopup();
      }
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
     * @ignore(Element)
     */
    print : function(domElement, app) {
      if (!(domElement instanceof Element)) {
        this.error("print() takes a DOM element as argument");
        return;
      }
      var win = window.open();
      win.document.open();
      win.document.write(domElement.innerHTML);
      win.document.close();
      win.print();
    },



    endOfFile : true
  },

  /**
   * Setup event listeners in the defer function by iterating through the member methods
   * and setting up up a message subscriber for "bibliograph.command.{method name}".
   * When the message is dispatched, the method is called with the signature
   * ({mixed} messageData, {qx.application.Standalone} app)
   */
  defer: function(statics, members, properties) {
    for (let methodName in members) {
      if (typeof members[methodName] == "function") {
        qx.event.message.Bus.subscribe("bibliograph.command." + methodName, function(e) {
          try {
            let maybePromise = members[methodName](e.getData(), qx.core.Init.getApplication());
            if (maybePromise instanceof Promise || maybePromise instanceof qx.Promise) {
              (async () => {
                await maybePromise;
              })();
            }
          } catch (e) {
            throw new Error(`Exception raised during call to bibliograph.command.${methodName}():${e}`);
          }
        }, this);
      }
    }
  }
});
