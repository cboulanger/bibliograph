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
 * This class instantiates the application windows
 */
qx.Class.define("bibliograph.ui.Windows",
{
  extend : qx.core.Object,
  type: "singleton",

  /**
   * The methods and simple properties of this class
   */
  members :
  {

    getRoot: function() {
      return this.getApplication().getRoot();
    },

    /**
     * Create the application windows 
     */
    create: function() 
    {
      var app = qx.core.Init.getApplication();
      var bus = qx.event.message.Bus.getInstance();

      /*
       * Datasource list window
       */
      var ui_winDatasourceListWindow1 = new bibliograph.ui.window
        .DatasourceListWindow();
      ui_winDatasourceListWindow1.setWidgetId("bibliograph/datasourceWindow");
      ui_winDatasourceListWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winDatasourceListWindow1);

      /*
       * Access Control Tool
       */
      var ui_winAccessControlTool1 = new bibliograph.ui.window
        .AccessControlTool();
      ui_winAccessControlTool1.setWidgetId("bibliograph/accessControlTool");
      ui_winAccessControlTool1.setVisibility("excluded");
      this.getRoot().add(ui_winAccessControlTool1);
      
      /*
       * Folder Tree window
       */
      var ui_winFolderTreeWindow1 = new bibliograph.ui.window
        .FolderTreeWindowUi();
      ui_winFolderTreeWindow1.setWidgetId("bibliograph/folderTreeWindow");
      ui_winFolderTreeWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winFolderTreeWindow1);

      /*
       * Preferences window
       */
      var ui_winPreferencesWindow1 = new bibliograph.ui.window
        .PreferencesWindow();
      ui_winPreferencesWindow1.setWidgetId("bibliograph/preferencesWindow");
      ui_winPreferencesWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winPreferencesWindow1);
      
      /*
       * Import window
       */
      var ui_winImportWindow1 = new bibliograph.ui.window.ImportWindowUi();
      ui_winImportWindow1.setWidgetId("bibliograph/importWindow");
      ui_winImportWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winImportWindow1);

      /*
       * About window
       */
      var ui_winAboutWindow1 = new bibliograph.ui.window.AboutWindow();
      ui_winAboutWindow1.setWidgetId("bibliograph/aboutWindow");
      ui_winAboutWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winAboutWindow1);

      /*
       * Search help window
       */
      var ui_winSearchHelpWindow1 = new bibliograph.ui.window
        .SearchHelpWindow();
      ui_winSearchHelpWindow1.setWidgetId("bibliograph/searchHelpWindow");
      ui_winSearchHelpWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winSearchHelpWindow1);

      /*
       * Login Dialog
       */

      var loginDialog = new dialog.Login();
      loginDialog.set({
        widgetId: "bibliograph/loginDialog",
        allowCancel: true,
        checkCredentials: bibliograph.Utils.checkLogin,
        showForgotPassword: false,
        forgotPasswordHandler: function(){ app.cmd("forgotPassword");}
      });

      loginDialog.setCallback(async function(err, data) {
        if ( err ) {
          await dialog.Dialog.error(err).promise();
          qx.event.Timer.once( () => { loginDialog._password.focus() }, null, 100);
        }
      });

      // bind messages to configuration values
      let cm = app.getConfigManager()
      cm.addListener("ready", ()=> {
        cm.bindKey("application.title", loginDialog, "text", false);
      });
      cm.addListener("ready", () => {
        cm.bindKey("application.logo", loginDialog, "image", false);
      });

      // hide forgot password button if ldap is enabled
      bus.subscribe(
        "ldap.enabled",
        function(e) {
          loginDialog.setShowForgotPassword(!e.getData());
        },
        this
      );
    }
  }
});