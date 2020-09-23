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
  include: [qx.locale.MTranslation],
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
    create: function() {
      let app = qx.core.Init.getApplication();
      this.setQxObjectId("windows");
      qx.core.Id.getInstance().register(this);

      // Datasource list window
      let datasourceWindow = bibliograph.ui.window.DatasourceListWindow.getInstance();
      this.getRoot().add(datasourceWindow);
      this.addOwnedQxObject(datasourceWindow, "datasources");

      // Access Control Tool
      let accessControlTool = new bibliograph.ui.window.AccessControlTool();
      this.addOwnedQxObject(accessControlTool, "access-control");
      accessControlTool.setVisibility("excluded");
      this.getRoot().add(accessControlTool);
      
      // Folder Tree window
      let folderTreeWindow = new bibliograph.ui.window.FolderTreeWindow();
      folderTreeWindow.setVisibility("excluded");
      this.addOwnedQxObject(folderTreeWindow, "folders");
      this.getRoot().add(folderTreeWindow);

      // Preferences window
      let preferencesWindow = new bibliograph.ui.window.PreferencesWindow();
      preferencesWindow.setVisibility("excluded");
      this.addOwnedQxObject(preferencesWindow, "preferences");
      this.getRoot().add(preferencesWindow);
      
      // Import window
      let importWindow = new bibliograph.ui.window.ImportWindow();
      importWindow.setVisibility("excluded");
      this.addOwnedQxObject(importWindow, "import");
      this.getRoot().add(importWindow);

      // About window
      let aboutWindow = new bibliograph.ui.window.AboutWindow();
      aboutWindow.setVisibility("excluded");
      this.addOwnedQxObject(aboutWindow, "about");
      this.getRoot().add(aboutWindow);

      // Search help window
      let searchHelpWindow = new bibliograph.ui.window.SearchHelpWindow();
      searchHelpWindow.setVisibility("excluded");
      this.addOwnedQxObject(searchHelpWindow, "search-help");
      this.getRoot().add(searchHelpWindow);

      // Login Dialog
      let loginDialog = new qxl.dialog.Login();
      this.addOwnedQxObject(loginDialog, "login");
      loginDialog.set({
        widgetId: "app/windows/login",
        allowCancel: true,
        checkCredentials: bibliograph.Utils.checkLogin,
        showForgotPassword: false,
        forgotPasswordHandler: () => {
         app.cmd("forgotPassword");
        }
      });
      // Callback that is called from checkCredetials function with
      // authentication result
      loginDialog.setCallback((err, data) => {
        if (err) {
          this.getApplication().error(err);
          qx.event.Timer.once(() => {
           loginDialog._password.focus();
          }, null, 100);
        }
      });
      
      // initialize task manager window
      if (qx.core.Environment.get("app.taskmonitor.enable")) {
        const tm = qcl.ui.tool.TaskMonitor.getInstance();
        if (qx.core.Environment.get("app.taskmonitor.show")) {
          tm.open();
        }
      }

      // configuration-dependent UI elements
      let cm = app.getConfigManager();
      cm.addListener("ready", () => {
        cm.bindKey("application.title", loginDialog, "text", false);
        cm.bindKey("application.logo", loginDialog, "image", false);
        // hide forgot password button if ldap is enabled @todo - make this configurable
        // Re-enable when email-controller has been reimplemented
        //loginDialog.setShowForgotPassword(!cm.getKey("ldap.enabled"));
      });
    }
  }
});
