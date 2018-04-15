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

      // Access Control Tool
      let accessControlTool = new bibliograph.ui.window.AccessControlTool();
      accessControlTool.setWidgetId("app/windows/access-control");
      accessControlTool.setVisibility("excluded");
      this.getRoot().add(accessControlTool);
      
      // Folder Tree window
      let folderTreeWindow = new bibliograph.ui.window
        .FolderTreeWindow();
      folderTreeWindow.setWidgetId("app/windows/folders");
      folderTreeWindow.setVisibility("excluded");
      this.getRoot().add(folderTreeWindow);

      // Preferences window
      let preferencesWindow = new bibliograph.ui.window.PreferencesWindow();
      preferencesWindow.setWidgetId("app/windows/preferences");
      preferencesWindow.setVisibility("excluded");
      this.getRoot().add(preferencesWindow);
      
      // Import window
      let importWindow = new bibliograph.ui.window.ImportWindowUi();
      importWindow.setWidgetId("app/windows/import");
      importWindow.setVisibility("excluded");
      this.getRoot().add(importWindow);

      // About window
      let aboutWindow = new bibliograph.ui.window.AboutWindow();
      aboutWindow.setWidgetId("app/windows/about");
      aboutWindow.setVisibility("excluded");
      this.getRoot().add(aboutWindow);

      // Search help window
      let searchHelpWindow = new bibliograph.ui.window.SearchHelpWindow();
      searchHelpWindow.setWidgetId("app/windows/help-search");
      searchHelpWindow.setVisibility("excluded");
      this.getRoot().add(searchHelpWindow);

      // Login Dialog
      let loginDialog = new dialog.Login();
      loginDialog.set({
        widgetId: "app/windows/login",
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