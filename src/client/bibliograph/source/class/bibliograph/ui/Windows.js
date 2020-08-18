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
      datasourceWindow.setQxObjectId("datasources");
      this.addOwnedQxObject(datasourceWindow);

      // Access Control Tool
      let accessControlTool = new bibliograph.ui.window.AccessControlTool();
      this.addOwnedQxObject(accessControlTool, "access-control");
      accessControlTool.setVisibility("excluded");
      accessControlTool.setQxObjectId("access");
      this.addOwnedQxObject(accessControlTool);
      this.getRoot().add(accessControlTool);
      
      // Folder Tree window
      let folderTreeWindow = new bibliograph.ui.window.FolderTreeWindow();
      folderTreeWindow.setWidgetId("app/windows/folders");
      folderTreeWindow.setVisibility("excluded");
      folderTreeWindow.setQxObjectId("folders");
      this.addOwnedQxObject(folderTreeWindow);
      this.getRoot().add(folderTreeWindow);

      // Preferences window
      let preferencesWindow = new bibliograph.ui.window.PreferencesWindow();
      this.addOwnedQxObject(preferencesWindow, "preferences");
      preferencesWindow.setVisibility("excluded");
      preferencesWindow.setQxObjectId("preferences");
      this.addOwnedQxObject(preferencesWindow);
      this.getRoot().add(preferencesWindow);
      
      // Import window
      let importWindow = new bibliograph.ui.window.ImportWindow();
      importWindow.setWidgetId("app/windows/import");
      importWindow.setVisibility("excluded");
      importWindow.setQxObjectId("import");
      this.addOwnedQxObject(importWindow);
      this.getRoot().add(importWindow);

      // About window
      let aboutWindow = new bibliograph.ui.window.AboutWindow();
      aboutWindow.setWidgetId("app/windows/about");
      aboutWindow.setVisibility("excluded");
      aboutWindow.setQxObjectId("about");
      this.addOwnedQxObject(aboutWindow);
      this.getRoot().add(aboutWindow);

      // Search help window
      let searchHelpWindow = new bibliograph.ui.window.SearchHelpWindow();
      searchHelpWindow.setWidgetId("app/windows/help-search");
      searchHelpWindow.setVisibility("excluded");
      searchHelpWindow.setQxObjectId("search-help");
      this.addOwnedQxObject(searchHelpWindow);
      this.getRoot().add(searchHelpWindow);

      // Login Dialog
      let loginDialog = new qxl.dialog.Login();
      loginDialog.setQxObjectId("login");
      this.addOwnedQxObject(loginDialog);
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
        // qx.bom.Lifecycle.onReady(() => {
        //   console.warn(qx.core.Id.getInstance().getRegisteredObjects());
        //   // add menu entry to system meny
        //   let menu = qx.core.Id.getQxObject("app/toolbar/system");
        //   let button = new qx.ui.menu.Button(this.tr("Task Monitor"));
        //   button.addListener("execute", () => {
        //     tm.getCommand().execute();
        //   });
        //   menu.add(button);
        // });
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
