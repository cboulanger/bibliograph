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
 * This class instantiates the application windows and sets up the bindings
 * between them and the application state
 */
qx.Class.define("bibliograph.ui.Windows",
{
  extend : qx.core.Object,
  type: "singleton",
  members :
  {
  
    /**
     * Returns application ui root
     * @return {*}
     */
    getRoot: function() {
      return this.getApplication().getRoot();
    },

    /**
     * Create the application windows 
     * @todo : move logic into class definition if window is singleton
     */
    create: function() 
    {
      let app = qx.core.Init.getApplication();
      let bus = qx.event.message.Bus.getInstance();

      // Datasource list window
      this.getRoot().add(bibliograph.ui.window.DatasourceListWindow.getInstance());

      /*
       * Access Control Tool
       */
      let ui_winAccessControlTool1 = new bibliograph.ui.window.AccessControlTool();
      ui_winAccessControlTool1.setWidgetId("app/windows/access-control");
      ui_winAccessControlTool1.setVisibility("excluded");
      this.getRoot().add(ui_winAccessControlTool1);
      
      /*
       * Folder Tree window
       */
      let ui_winFolderTreeWindow1 = new bibliograph.ui.window
        .FolderTreeWindow();
      ui_winFolderTreeWindow1.setWidgetId("app/windows/folders");
      ui_winFolderTreeWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winFolderTreeWindow1);

      /*
       * Preferences window
       */
      let ui_winPreferencesWindow1 = new bibliograph.ui.window.PreferencesWindow();
      ui_winPreferencesWindow1.setWidgetId("app/windows/preferences");
      ui_winPreferencesWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winPreferencesWindow1);
      
      /*
       * Import window
       */
      let ui_winImportWindow1 = new bibliograph.ui.window.ImportWindowUi();
      ui_winImportWindow1.setWidgetId("bibliograph/importWindow");
      ui_winImportWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winImportWindow1);

      /*
       * About window
       */
      let ui_winAboutWindow1 = new bibliograph.ui.window.AboutWindow();
      ui_winAboutWindow1.setWidgetId("bibliograph/aboutWindow");
      ui_winAboutWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winAboutWindow1);

      /*
       * Search help window
       */
      let ui_winSearchHelpWindow1 = new bibliograph.ui.window.SearchHelpWindow();
      ui_winSearchHelpWindow1.setWidgetId("bibliograph/searchHelpWindow");
      ui_winSearchHelpWindow1.setVisibility("excluded");
      this.getRoot().add(ui_winSearchHelpWindow1);

      /*
       * Login Dialog
       */
      let loginDialog = new dialog.Login();
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
      let cm = app.getConfigManager();
      cm.addListener("ready", ()=> {
        cm.bindKey("application.title", loginDialog, "text", false);
      });
      cm.addListener("ready", () => {
        cm.bindKey("application.logo", loginDialog, "image", false);
      });
      
      // hide forgot password button if ldap is enabled
      bus.subscribe( "ldap.enabled",(e) => loginDialog.setShowForgotPassword(!e.getData()));
    }
  }
});