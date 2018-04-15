/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2018 Christian Boulanger
 *
 * License: LGPL: http://www.gnu.org/licenses/lgpl.html EPL:
 * http://www.eclipse.org/org/documents/epl-v10.php See the LICENSE file in the
 * project's top-level directory for details.
 *
 * Authors: Christian Boulanger (cboulanger)
 *
 ******************************************************************************/

/*global qx dialog qcl*/

/**
 * The main toolbar
 */
qx.Class.define("bibliograph.ui.main.Toolbar",
{
  extend : qx.ui.toolbar.ToolBar,
  construct : function()
  {
    this.base(arguments);

    // shorthand vars
    let app = qx.core.Init.getApplication();
    this.accsMgr = app.getAccessManager();
    this.permMgr = this.accsMgr.getPermissionManager();
    this.userMgr = this.accsMgr.getUserManager();
    
    // Toolbar
    let toolBar = this;
    toolBar.setWidgetId("app/toolbar");

    let toolBarPart = new qx.ui.toolbar.Part();
    toolBar.add(toolBarPart);
    toolBarPart.add(this.createLoginButton());
    toolBarPart.add(this.createLogoutButton());
    toolBarPart.add(this.createUserButton());

    let toolBarPart2 = new qx.ui.toolbar.Part();
    toolBar.add(toolBarPart2);
    toolBarPart2.add(this.createDatasourceButton());
    toolBarPart2.add(this.createSystemMenu());
    toolBarPart2.add(this.createImportMenu());
    toolBarPart2.add(this.createHelpMenu());
    // @todo toggle with server config
    //toolBarPart2.add(this.createDeveloperMenu());

    toolBar.add(new qx.ui.basic.Atom(), { flex : 10 }); // why not a spacer?
    toolBar.add(this.createTitleLabel());
    toolBar.add(this.createSearchBox(), { flex : 1 });
    this.createSearchButtons().map( button => toolBar.add(button) );
  },

  members :
  {
    createLoginButton : function()
    {
      let button = new qx.ui.toolbar.Button();
      button.setIcon("icon/16/status/dialog-password.png");
      button.setLabel(this.tr('Login'));
      button.setVisibility("excluded");
      this.accsMgr.bind("authenticatedUser", button, "visibility", {
        converter : function(v) {
          return v ? 'excluded' : 'visible'
        }
      });
      button.addListener("execute", () => this.getApplication().cmd("showLoginDialog") );
      return button;
    },
    
    createLogoutButton : function()
    {
      let button = new qx.ui.toolbar.Button();
      button.setLabel(this.tr('Logout'));
      button.setIcon("icon/16/actions/application-exit.png");
      button.setVisibility("excluded");
      this.accsMgr.bind("authenticatedUser", button, "visibility", {
        converter : function(v) {
          return v ? 'visible' : 'excluded'
        }
      });
      button.addListener("execute", () => this.getApplication().cmd("logout") );
      return button;
    },

    createUserButton : function()
    {
      // User button
      let button = new qx.ui.toolbar.Button();
      button.setLabel(this.tr('Loading...'));
      button.setIcon("icon/16/apps/preferences-users.png");
      this.userMgr.bind("activeUser.fullname", button, "label" );
      this.getApplication().getAccessManager().bind("authenticatedUser", button, "visibility", {
        converter : function(v) {
          return v ? 'visible' : 'excluded'
        }
      });
      button.addListener("execute", function(e) {
        this.getApplication().cmd("editUserData");
      }, this);
      return button;
    },

    createDatasourceButton : function()
    {
      let button = new qx.ui.toolbar.Button();
      button.setLabel(this.tr('Datasources'));
      button.setWidgetId("app/toolbar/buttons/datasource");
      button.setVisibility("excluded");
      button.setIcon("icon/16/apps/utilities-archiver.png");
      button.addListener("execute", function(e) {
        this.getApplication().getWidgetById("app/windows/datasources").show();
      }, this);
      return button;
    },

    createSystemMenu : function()
    {
      let button = new qx.ui.toolbar.MenuButton();
      button.setIcon("icon/22/categories/system.png");
      button.setVisibility("excluded");
      button.setLabel(this.tr('System'));
      this.permMgr.create("system.menu.view").bind("state", button, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      let systemMenu = new qx.ui.menu.Menu();
      button.setMenu(systemMenu);
      systemMenu.setWidgetId("app/toolbar/menus/system");

      // menu content
      systemMenu.add(this.createPreferencesButton());
      systemMenu.add(this.createAccessManagementButton());
      //systemMenu.add(this.createPluginButton());
      return button;
    },

    createPreferencesButton : function()
    {
      let button = new qx.ui.menu.Button();
      button.setLabel(this.tr('Preferences'));
      this.permMgr.create("preferences.view").bind("state", button, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      button.addListener("execute", function(e) {
        let win = this.getApplication().getWidgetById("app/windows/preferences").show();
      }, this);
      return button;
    },

    createAccessManagementButton : function()
    {
      let button = new qx.ui.menu.Button();
      button.setLabel(this.tr('Access management'));
      this.permMgr.create("access.manage").bind("state", button, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      button.addListener("execute", function(e) {
        let win = this.getApplication().getWidgetById("app/windows/access-control").show();
      }, this);
      return button;
    },

    createPluginButton : function()
    {
      let button = new qx.ui.menu.Button();
      button.setLabel(this.tr('Plugins'));
      this.permMgr.create("plugin.manage").bind("state", button, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      button.addListener("execute", function(e) {
        this.getApplication().getRpcClient("plugin").send( "manage");
      }, this);
      return button;
    },

    createImportMenu : function()
    {
      let button = new qx.ui.toolbar.MenuButton();
      button.setLabel(this.tr('Import'));
      button.setIcon("icon/22/places/network-server.png");
      this.permMgr.create("reference.import").bind("state", button, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      let menu = new qx.ui.menu.Menu();
      menu.setWidgetId("app/toolbar/menus/import");
      button.setMenu(menu);

      // menu content
      //menu.add( this.createImportTextButton() );

      return button;
    },

    createImportTextButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr('Import text file'));
      button.addListener("execute", function(e) {
        this.getApplication().getWidgetById("app/windows/import").show();
      }, this);
      return button;
    },
 
    createHelpMenu : function()
    {
      let menuButton = new qx.ui.toolbar.MenuButton();
      menuButton.setIcon("icon/22/apps/utilities-help.png");

      let menu = new qx.ui.menu.Menu();
      menuButton.setMenu(menu);
      menu.setWidgetId("app/toolbar/menus/help");

      let button1 = new qx.ui.menu.Button(this.tr('Online Help'));
      menu.add(button1);
      button1.addListener("execute", function(e) {
        this.getApplication().cmd("showHelpWindow");
      }, this);

      let button2 = new qx.ui.menu.Button();
      button2.setLabel(this.tr('About Bibliograph'));
      menu.add(button2);
      button2.addListener("execute", function(e) {
        this.getApplication().cmd("showAboutWindow");
      }, this);

      return menuButton;
    },

    createDeveloperMenu : function()
    {
      let menuButton = new qx.ui.toolbar.MenuButton();
      menuButton.setLabel(this.tr('Developer'));

      let menu = new qx.ui.menu.Menu();
      menuButton.setMenu(menu);
      menu.setWidgetId("app/toolbar/menus/developer");

      let button1 = new qx.ui.menu.Button(this.tr('Run RPC method test.test'));
      menu.add(button1);
      button1.addListener("execute", function(e) {
        this.getApplication().getRpcClient("test").send("test");
      }, this);

      return menuButton;
    },

    createTitleLabel : function()
    {
      let label = new qx.ui.basic.Label();
      label.setPadding(10);
      label.setRich(true);
      label.setTextAlign("right");

      this.applicationTitleLabel = label;
      label.setWidgetId("app/toolbar/title");
      return label;
    },

    createSearchBox : function()
    {
      // let t = new tokenfield.Token();
      // t.setMarginTop(8);
      // t.setSelectionMode('multi');
      // t.setSelectOnce(true);
      // t.setLabelPath("name");
      // t.setHintText("Token field test...");
      // t.addListener("loadData", e => {
      //   let input = e.getData();
      //   rpc.Reference.autocomplete()
      //   t.populateList(input, data);
      // });
      //
      
      let searchbox = new qx.ui.form.TextField();
      this.searchbox = searchbox;
      searchbox.setWidgetId("app/toolbar/searchbox");
      searchbox.setMarginTop(8);
      searchbox.setPlaceholder(this.tr('Enter search term'));
      this.permMgr.create("reference.search").bind("state", searchbox, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      searchbox.addListener("keypress", function(e) {
        if (e.getKeyIdentifier() == "Enter")
        {
          let app = this.getApplication();
          let query = searchbox.getValue();
          app.setFolderId(0);
          app.setQuery(query);
          qx.event.message.Bus.dispatch(new qx.event.message.Message("bibliograph.userquery", query));
          app.getWidgetById("app/windows/help-search").hide();
        }
      }, this);
      searchbox.addListener("dblclick", function(e) {
        e.stopPropagation();
      }, this);
      searchbox.addListener("focus", function(e)
      {
        searchbox.setLayoutProperties( {
          flex : 10
        });
        this.getApplication().setInsertTarget(searchbox);
      }, this);
      searchbox.addListener("blur", function(e)
      {
        let timer = qx.util.TimerManager.getInstance();
        timer.start(function() {
          if (!qx.ui.core.FocusHandler.getInstance().isFocused(searchbox)) {
            searchbox.setLayoutProperties( {
              flex : 1
            });
          }
        }, null, this, null, 5000);
      }, this);
      return searchbox
    },

    createSearchButtons : function()
    {
      // search button
      let searchButton = new qx.ui.toolbar.Button();
      searchButton.setIcon("bibliograph/icon/16/search.png");
      this.permMgr.create("reference.search").bind("state", searchButton, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      searchButton.addListener("execute", () => {
        let query = this.searchbox.getValue();
        let app = this.getApplication();
        app.getWidgetById("app/windows/help-search").hide();
        app.setFolderId(0);
        if (app.getQuery() == query) app.setQuery(null); // execute query again
        app.setQuery(query);
        qx.event.message.Bus.dispatch(new qx.event.message.Message("bibliograph.userquery", query));
      });
      
      // cancel button
      let cancelButton = new qx.ui.toolbar.Button();
      cancelButton.setIcon("bibliograph/icon/16/cancel.png");
      cancelButton.setMarginRight(5);
      this.permMgr.create("reference.search").bind("state", cancelButton, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      cancelButton.addListener("execute", () => {
        this.searchbox.setValue("");
        this.searchbox.focus();
        this.getApplication().getWidgetById("app/windows/help-search").hide();
      });
      
      // search help button
      let helpButton = new qx.ui.toolbar.Button();
      helpButton.setIcon("bibliograph/icon/16/help.png");
      helpButton.setMarginRight(5);
      this.permMgr.create("reference.search").bind("state", helpButton, "visibility", {
        converter : bibliograph.Utils.bool2visibility
      });
      helpButton.addListener("execute", function(e) {
        let hwin = this.getApplication().getWidgetById("app/windows/help-search");
        hwin.show();
        hwin.center();
      }, this);
      return [searchButton, cancelButton, helpButton];
    }
  }
});
