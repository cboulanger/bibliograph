/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright:
    2003-2020 Christian Boulanger

  License:
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors:
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * The main toolbar
 */
qx.Class.define("bibliograph.ui.main.Toolbar",
{
  extend : qx.ui.toolbar.ToolBar,
  include: [qcl.access.MPermissions],
  type: "singleton",
  construct: function() {
    this.base(arguments);
    this.__am = qx.core.Init.getApplication().getAccessManager();
    this.__pm = this.__am.getPermissionManager();
    this.__um = this.__am.getUserManager();
    this.add(this.getQxObject("toolbar-part-1"));
    this.add(this.getQxObject("toolbar-part-2"));
    this.add(new qx.ui.basic.Atom(), { flex : 10 });
    this.add(this.getQxObject("title"));
    this.add(this.getQxObject("search-bar"));
  },

  members :
  {
    _createQxObjectImpl(id) {
      let control;
      switch (id) {
        case "toolbar-part-1":
          control = new qx.ui.toolbar.Part();
          control.add(this.getQxObject("login-button"));
          control.add(this.getQxObject("logout-button"));
          control.add(this.getQxObject("user-button"));
          break;
        case "toolbar-part-2":
          control = new qx.ui.toolbar.Part();
          control.add(this.getQxObject("datasource-button"));
          control.add(this.getQxObject("system-button"));
          control.add(this.getQxObject("import-button"));
          control.add(this.getQxObject("help-button"));
          if (qx.core.Environment.get("qx.debug")) {
            control.add(this.getQxObject("developer-button"));
          }
          break;
        case "login-button":
          control = new qx.ui.toolbar.Button();
          control.set({
            icon: "icon/16/status/dialog-password.png",
            label: this.tr("Login"),
            visibility: "excluded",
            enabled: false
          });
          this.bindVisibilityToProp(this.__am, "authenticatedUser", control, true);
          control.addListener("execute", () => this.getApplication().cmd("showLoginDialog"));
          break;
        case "logout-button":
          control = new qx.ui.toolbar.Button();
          control.set({
            icon: "icon/16/actions/application-exit.png",
            label: this.tr("Logout"),
            visibility: "excluded",
            enabled: false
          });
          this.bindVisibilityToProp(this.__am, "authenticatedUser", control);
          control.addListener("execute", () => this.getApplication().cmd("logout"));
          break;
        case "user-button":
          control = new qx.ui.toolbar.Button(this.tr("Loading..."));
          control.setIcon("icon/16/apps/preferences-users.png");
          this.__um.bind("activeUser.fullname", control, "label");
          this.bindVisibilityToProp(this.__am, "authenticatedUser", control);
          control.addListener("execute", () => this.getApplication().cmd("editUserData"));
          break;
        case "datasource-button":
          control = new qx.ui.toolbar.Button();
          control.setLabel(this.tr("Datasources"));
          control.setVisibility("excluded");
          control.setIcon("icon/16/apps/utilities-archiver.png");
          control.addListener("execute", () => qx.core.Id.getQxObject("windows/datasources").open());
          break;
        case "system-button":
          control = new qx.ui.toolbar.MenuButton(this.tr("System"));
          control.setIcon("icon/22/categories/system.png");
          control.setVisibility("excluded");
          this.bindVisibilityToProp(this.__pm.create("system.menu.view"), "state", control);
          control.setMenu(this.getQxObject("system-menu"));
          break;
        case "system-menu":
          control = new qx.ui.menu.Menu();
          control.add(this.getQxObject("preferences-button"));
          control.add(this.getQxObject("access-management-button"));
          //control.add(this.getQxObject("plugin-button");
          break;
        case "preferences-button":
          control = new qx.ui.menu.Button(this.tr("Preferences"));
          this.bindVisibilityToProp(this.__pm.create("preferences.view"), "state", control);
          control.addListener("execute", () => qx.core.Id.getQxObject("windows/preferences").show());
          break;
        case "access-management-button":
          control = new qx.ui.menu.Button(this.tr("Access management"));
          this.bindVisibilityToProp(this.__pm.create("access.manage"), "state", control);
          control.addListener("execute", () => qx.core.Id.getQxObject("windows/access-control").show());
          break;
        case "plugin-button":
          control = new qx.ui.menu.Button();
          control.setLabel(this.tr("Plugins"));
          this.bindVisibilityToProp(this.__pm.create("plugin.manage"), "state", control);
          this.getApplication().alert("Not implemented");
          //control.addListener("execute", () => rpc.Plugin.manage());
          break;
        case "import-button":
          control = new qx.ui.toolbar.MenuButton(this.tr("Import"));
          control.setVisibility("excluded");
          control.setIcon("icon/22/places/network-server.png");
          this.bindVisibilityToProp(this.__pm.create("reference.import"), "state", control);
          control.setMenu(this.getQxObject("import-menu"));
          break;
        case "import-menu":
          control = new qx.ui.menu.Menu();
          control.add(this.getQxObject("import-text-button"));
          break;
        case "import-text-button":
          control = new qx.ui.menu.Button(this.tr("Import text file"));
          control.addListener("execute", () => qx.core.Id.getQxObject("windows/import").show());
          break;
        case "help-button":
          control = new qx.ui.toolbar.MenuButton();
          control.setIcon("icon/22/apps/utilities-help.png");
          control.setMenu(this.getQxObject("help-menu"));
          break;
        case "help-menu":
          control = new qx.ui.menu.Menu();
          control.add(this.getQxObject("locale-button"));
          control.add(this.getQxObject("online-help-button"));
          control.add(this.getQxObject("about-button"));
          break;
        case "locale-button":
          control = new qx.ui.menu.Button(this.tr("Language"));
          control.setMenu(this.getQxObject("locale-menu"));
          break;
        case "locale-menu": {
          control = new qx.ui.menu.Menu();
          let localeManager = qx.locale.Manager.getInstance();
          let locales = localeManager.getAvailableLocales().sort();
          locales.forEach(locale => {
            let localeButton = new qx.ui.menu.Button(locale);
            control.add(localeButton);
            localeButton.addListener("execute", () => {
              localeManager.setLocale(locale);
              this.getApplication().getConfigManager().setKey("application.locale", locale);
            });
          });
          break;
        }
        case "online-help-button":
          control = new qx.ui.menu.Button(this.tr("Online Help"));
          control.addListener("execute", function(e) {
            this.getApplication().cmd("showHelpWindow");
          }, this);
          break;
        case "about-button":
          control = new qx.ui.menu.Button(this.tr("About Bibliograph"));
          control.addListener("execute", function(e) {
            this.getApplication().cmd("showAboutWindow");
          }, this);
          break;
        case "developer-button":
          control = new qx.ui.toolbar.MenuButton(this.tr("Developer"));
          control.setVisibility("excluded");
          this.bindVisibilityToProp(this.__am, "authenticatedUser", control);
          control.setMenu(this.getQxObject("developer-menu"));
          break;
        case "developer-menu":
          control = new qx.ui.menu.Menu();
          control.add(this.getQxObject("developer-rpc-test"));
          break;
        case "developer-rpc-test":
          control = new qx.ui.menu.Button(this.tr("Run method of 'test' service"));
          control.addListener("execute", async () => {
            let method = await this.getApplication().prompt("Enter method name");
            this.getApplication().getRpcClient("test").request(method);
          }, this);
          break;
        case "title":
          control = new qx.ui.basic.Label("Bibliograph");
          control.setPadding(10);
          control.setRich(true);
          control.setTextAlign("right");
          this.applicationTitleLabel = control;
          control.setWidgetId("app/toolbar/title");
          break;
        case "search-bar":
          control = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
          this.bindVisibilityToProp(this.__pm.create("reference.search"), "state", control);
          control.add(this.getQxObject("search-box"));
          control.add(this.getQxObject("search-button"));
          control.add(this.getQxObject("search-clear"));
          //...control.add(this.getQxObject("search-help"));
          break;
        case "search-box":
          control = this.creakenTokenFieldSearch();
          this.searchbox = control;
          control.addListener("focus", e => {
            control.setLayoutProperties({ flex : 10 });
            //this.getApplication().setInsertTarget(searchbox);
          });
          control.addListener("blur", e => {
            let timer = qx.util.TimerManager.getInstance();
            timer.start(function() {
              if (!qx.ui.core.FocusHandler.getInstance().isFocused(control)) {
                control.setLayoutProperties({ flex : 1 });
              }
            }, null, this, null, 5000);
          });
          break;
        case "search-button":
          control = new qx.ui.toolbar.Button();
          control.setIcon("bibliograph/icon/16/search.png");
          control.addListener("execute", () => this.startSearch());
          break;
        case "search-clear":
          control = new qx.ui.toolbar.Button();
          control.setIcon("bibliograph/icon/16/cancel.png");
          control.setMarginRight(5);
          control.addListener("execute", () => {
            this.searchbox.reset ? this.searchbox.reset() : null;
            this.searchbox.setValue ? this.searchbox.setValue("") : null;
            this.searchbox.focus();
            this.getSearchHelpWindow().hide();
          });
          break;
      }
      return control || this.base(arguments, id);
    },
    
    
    createTextFieldSearch() {
      let control = new qx.ui.form.TextField();
      control.setMarginTop(8);
      control.setPlaceholder(this.tr("Enter search term"));
      control.addListener("keypress", e => {
        if (e.getKeyIdentifier() === "Enter") {
          let app = this.getApplication();
          let query = control.getValue();
          app.setFolderId(0);
          app.setQuery(query);
          qx.event.message.Bus.dispatch(new qx.event.message.Message("bibliograph.userquery", query));
          this.getSearchHelpWindow().hide();
        }
      });
      control.addListener("dblclick", e => e.stopPropagation());
      return control;
    },
    
    creakenTokenFieldSearch() {
      let control = new tokenfield.Token();
      control.set({
        marginTop: 8,
        maxHeight: 24,
        width: 400,
        selectionMode: "multi",
        closeWhenNoResults: true,
        showCloseButton: false,
        minChars : 1,
        selectOnce: false,
        labelPath: "token",
        wildcards : ["?"],
        noResultsText : this.tr("No results"),
        searchingText : this.tr("Searching..."),
        typeInText: this.tr("Enter search term or ? for suggestions")
      });
      control.addListener("loadData", async e => {
        let input = e.getData();
        let tokens = control.getTokenLabels();
        let inputPosition = control.getInputPosition();
        let data = await rpc.Reference.tokenizeQuery(input, inputPosition, tokens, this.getApplication().getDatasource());
        control.populateList(input, data);
      });
      control.addListener("enterKeyWithContent", e => {
        let app = this.getApplication();
        let query = control.getTextContent();
        control.close();
        app.setFolderId(0);
        app.setQuery(query);
        qx.event.message.Bus.dispatch(new qx.event.message.Message("bibliograph.userquery", query));
        this.getSearchHelpWindow().hide();
      });
      this.getApplication().addListener("changeQuery", e => {
        if (e.getData() === control.getTextContent()) {
          return;
        }
        control.reset();
        //searchbox.getChildControl('textfield').setWidth(null);
        //searchbox.getChildControl('textfield').setValue(e.getData());
        //searchbox.search(e.getData());
      });
      return control;
    },
    
    startSearch() {
      let query;
      if (this.searchbox instanceof qx.ui.form.TextField) {
        query = this.searchbox.getValue();
      } else {
        query = this.searchbox.getTextContent();
      }
      this.searchbox.close();
      let app = this.getApplication();
      this.getSearchHelpWindow().hide();
      app.setFolderId(0);
      //if (app.getQuery() === query) app.setQuery(null); // execute query again
      app.setQuery(query);
      qx.event.message.Bus.dispatch(new qx.event.message.Message("bibliograph.userquery", query));
    },
    
    getSearchHelpWindow() {
      return qx.core.Id.getQxObject("windows/search-help");
    }
  }
});
