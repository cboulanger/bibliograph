/* ************************************************************************

 Bibliograph: Online Collaborative Reference Management

 Copyright:
 2007-2017 Christian Boulanger

 License:
 LGPL: http://www.gnu.org/licenses/lgpl.html
 EPL: http://www.eclipse.org/org/documents/epl-v10.php
 See the LICENSE file in the project's top-level directory for details.

 Authors:
 * Christian Boulanger (cboulanger)

 ************************************************************************ */
/*global bibliograph qx qcl dialog*/

/**
 * The application UI
 */
qx.Class.define("bibliograph.ui.MainLayout", {
  extend: qx.core.Object,
  type: "singleton",
  members: {
    getRoot: function() {
      return this.getApplication().getRoot();
    },

    /**
     * Create the main layout
     */
    create: function() {
      
      this.createWindows();

      var app = qx.core.Init.getApplication();
      var bus = qx.event.message.Bus.getInstance();

      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite1 = new qx.ui.container.Composite();
      qxComposite1.setLayout(qxVbox1);
      this.getRoot().add(qxComposite1, {
        edge: 0
      });

      /*
       * Toolbar
       */
      var ui_mainToolbar1 = new bibliograph.ui.main.Toolbar();
      qxComposite1.add(ui_mainToolbar1);

      /*
       * Splitpane
       */
      var qxHsplit1 = new qx.ui.splitpane.Pane("horizontal");
      qxHsplit1.setOrientation("horizontal");
      qxComposite1.add(qxHsplit1, {
        flex: 1
      });
      var qxVbox2 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite2 = new qx.ui.container.Composite();
      qxComposite2.setLayout(qxVbox2);
      qxHsplit1.add(qxComposite2, 1);
      var accordeon = new qx.ui.form.RadioGroup();
      accordeon.setAllowEmptySelection(true);

      /*
       * Folder Tree
       */
      var ui_mainFolderTreePanel1 = new bibliograph.ui.main.FolderTreePanel();
      qxComposite2.add(ui_mainFolderTreePanel1, {
        flex: 1
      });
      var qxVsplit1 = new qx.ui.splitpane.Pane("vertical");
      qxVsplit1.setOrientation("vertical");
      qxVsplit1.setDecorator(null);
      qxHsplit1.add(qxVsplit1, 3);
      
      /*
       * Reference Listview
       */
      var ui_mainReferenceListView1 = new bibliograph.ui.main
        .ReferenceListView();
      qxVsplit1.add(ui_mainReferenceListView1);
      
      /*
       * Item view
       */
      var ui_mainItemView1 = new bibliograph.ui.main.ItemViewUi();
      ui_mainItemView1.setWidgetId("bibliograph/itemView");
      qxVsplit1.add(ui_mainItemView1);
      ui_mainItemView1.bind("view", this.getApplication(), "itemView", {});
      this.getApplication().bind("itemView", ui_mainItemView1, "view", {});
    },

    /**
     * Create the application windows 
     */
    createWindows: function() 
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
return;
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
        checkCredentials: this.checkLogin,
        showForgotPassword: false,
        forgotPasswordHandler: this.forgotPassword.bind(this)
      });

      loginDialog.setCallback(function(loginSuccessful) {
        if (loginSuccessful) {
          loginDialog.hide();
        }
      });

      // bind messages to configuration values
      app.getConfigManager().addListener("ready", function() {
        app
          .getConfigManager()
          .bindKey("application.title", loginDialog, "text", false);
      });
      app.getConfigManager().addListener("ready", function() {
        app
          .getConfigManager()
          .bindKey("application.logo", loginDialog, "image", false);
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
