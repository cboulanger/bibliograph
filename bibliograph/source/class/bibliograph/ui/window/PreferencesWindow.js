/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2015 Christian Boulanger
 *
 * License: LGPL: http://www.gnu.org/licenses/lgpl.html EPL:
 * http://www.eclipse.org/org/documents/epl-v10.php See the LICENSE file in the
 * project's top-level directory for details.
 *
 * Authors: Christian Boulanger (cboulanger)
 *
 ******************************************************************************/

/*global qx qcl bibliograph*/

/**
 * The window containing the preferences
 */
qx.Class.define("bibliograph.ui.window.PreferencesWindow",
{
  extend : qx.ui.window.Window,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      var app = qx.core.Init.getApplication();
      var permMgr = app.getAccessManager().getPermissionManager();
      var confMgr = app.getConfigManager();

      /*
       * Window
       */
      var qxWindow1 = this;
      qxWindow1.setCaption(this.tr('Preferences'));
      qxWindow1.setHeight(400);
      qxWindow1.setWidth(600);
      qxWindow1.addListener("appear", function(e) {
        this.center()
      }, this);
      qx.event.message.Bus.getInstance().subscribe("logout", function(e) {
        this.close();
      }, this)
      var qxVbox1 = new qx.ui.layout.VBox(5);
      qxVbox1.setSpacing(5);
      qxWindow1.setLayout(qxVbox1);

      /*
       * Tabview
       */
      var tabView = new qx.ui.tabview.TabView();
      this.tabView = tabView;
      tabView.setWidgetId("bibliograph/preferences-tabView");
      qxWindow1.add(tabView, { flex : 1 });
      
      // work around strange bug that displays first and second tab simultaneously
      tabView.addListener("appear", function(){
        var selectables = tabView.getSelectables();
        tabView.setSelection([selectables[1]]);
        tabView.setSelection([selectables[0]]);
      });      

      /*
       * general settings tab
       */
      var qxPage1 = new qx.ui.tabview.Page(this.tr('General'));
      tabView.add(qxPage1);
      var qxGrid1 = new qx.ui.layout.Grid();
      qxGrid1.setSpacing(5);
      qxPage1.setLayout(qxGrid1);
      qxGrid1.setColumnWidth(0, 200);
      qxGrid1.setColumnFlex(1, 2);
      
      // title
      var qxLabel1 = new qx.ui.basic.Label(this.tr('Application title'));
      qxPage1.add(qxLabel1, {
        row : 0,
        column : 0
      });
      var qxTextarea1 = new qx.ui.form.TextArea(null);
      qxPage1.add(qxTextarea1, {
        row : 0,
        column : 1
      });
      confMgr.addListener("ready", function() {
        confMgr.bindKey("application.title", qxTextarea1, "value", true);
      });
      
      // logo
      var qxLabel2 = new qx.ui.basic.Label(this.tr('Application logo'));
      qxPage1.add(qxLabel2, {
        row : 1,
        column : 0
      });
      var qxTextField1 = new qx.ui.form.TextField(null);
      qxPage1.add(qxTextField1,
      {
        row : 1,
        column : 1
      });
      confMgr.addListener("ready", function() {
        confMgr.bindKey("application.logo", qxTextField1, "value", true);
      });
      
      // force backend language
      var qxLabel3 = new qx.ui.basic.Label(this.tr('Language'));
      qxPage1.add(qxLabel3, {
        row : 2,
        column : 0
      });

      var localeManager = qx.locale.Manager.getInstance();
      var locales = localeManager.getAvailableLocales().sort();
      var currentLocale = localeManager.getLocale();

      var select = new qx.ui.form.SelectBox();
      select.setAllowGrowY(false);
      select.setAlignY('middle');
      
      // setup selectbox
      confMgr.addListener("ready", function(e) {
        
        var localeFromConfig = confMgr.getKey("application.locale");
        
        // default: locale from browser
        var defaultListItem = new qx.ui.form.ListItem(this.tr("Use browser locale"),null,"");
        select.add(defaultListItem);
        
        for (var i=0; i<locales.length; i++)
        {
          var listItem =new qx.ui.form.ListItem(locales[i],null,locales[i]);
          select.add(listItem);
          if ( locales[i] == localeFromConfig ) 
          {
            defaultListItem = listItem;
          }
        }
        
        if (defaultListItem) {
          select.setSelection([defaultListItem]);
        }
      }, this);

      // selectbox change
      select.addListener("changeSelection", function(e)
      {
        var locale = e.getData()[0].getModel();
        if( locale ) {
          qx.locale.Manager.getInstance().setLocale(locale);  
        }
        confMgr.setKey("application.locale",locale);
      });
      
      qxPage1.add(select, {row: 2, column: 1});
      
      
      //@todo: Reset application button
      

      /*
       * Tab to configure which fields to omit
       */
      var qxPage2 = new qx.ui.tabview.Page(this.tr('Fields'));
      tabView.add(qxPage2);
      permMgr.create("bibliograph.fields.manage").bind("state", qxPage2, "visibility", {
        converter : function(v){ return v? "visible" : "excluded" }
      });
      var qxGrid2 = new qx.ui.layout.Grid();
      qxGrid2.setSpacing(5);
      qxPage2.setLayout(qxGrid2);
      qxGrid2.setColumnWidth(0, 200);
      qxGrid2.setColumnFlex(1, 2);
      qxGrid2.setRowFlex(1, 1);
      var qxLabel3 = new qx.ui.basic.Label(this.tr('Database'));
      qxPage2.add(qxLabel3, {
        row : 0,
        column : 0
      });
      var datasourceSelectBox = new qx.ui.form.SelectBox();
      qxPage2.add(datasourceSelectBox,
      {
        row : 0,
        column : 1
      });
      var dsController = new qx.data.controller.List(null, datasourceSelectBox, "label");
      app.getDatasourceStore().bind("model", dsController, "model");
      datasourceSelectBox.addListener("changeSelection", function(e)
      {
        var datasource = e.getData()[0].getModel().getValue();
        var key = this.__datasourceExcludeFieldsKey = "datasource." + datasource + ".fields.exclude";
        var configManager = this.getApplication().getConfigManager();
        if (configManager.keyExists(key))
        {
          this.excludeFieldsTextArea.setReadOnly(false);
          this.excludeFieldsTextArea.setEnabled(true);
          this.excludeFieldsTextArea.setValue(configManager.getKey(key).join("\n"));
        } else
        {
          this.excludeFieldsTextArea.setEnabled(false);
          this.excludeFieldsTextArea.setReadOnly(true);
          this.excludeFieldsTextArea.setValue("");
        }
      }, this);
      var qxLabel4 = new qx.ui.basic.Label(this.tr('Exclude fields'));
      qxLabel4.setValue(this.tr('Exclude fields'));
      qxPage2.add(qxLabel4,
      {
        row : 1,
        column : 0
      });
      var excludeFieldsTextArea = new qx.ui.form.TextArea();
      this.excludeFieldsTextArea = excludeFieldsTextArea;
      excludeFieldsTextArea.setReadOnly(true);
      excludeFieldsTextArea.setPlaceholder(this.tr('Enter the names of fields to exclude.'));
      qxPage2.add(excludeFieldsTextArea,
      {
        row : 1,
        column : 1
      });
      excludeFieldsTextArea.addListener("changeValue", function(e)
      {
        var key = this.__datasourceExcludeFieldsKey;
        var value = e.getData().split("\n");
        if (confMgr.keyExists(key) && confMgr.getKey(key).join("") != value.join("") && this.isVisible()) {
          confMgr.setKey(key, value);
        }
      }, this);

      /*
       * access mode
       */
      var qxPage3 = new qx.ui.tabview.Page(this.tr('Access'));
      tabView.add(qxPage3);
      permMgr.create("access.manage").bind("state", qxPage3, "enabled", {});
      var qxGrid3 = new qx.ui.layout.Grid();
      qxGrid3.setSpacing(5);
      qxPage3.setLayout(qxGrid3);
      qxGrid3.setColumnWidth(0, 200);
      qxGrid3.setColumnFlex(1, 2);
      var qxLabel5 = new qx.ui.basic.Label(this.tr('Access Mode'));
      qxPage3.add(qxLabel5,
      {
        row : 0,
        column : 0
      });
      var modelSelectBox = new qx.ui.form.SelectBox();
      qxPage3.add(modelSelectBox,
      {
        row : 0,
        column : 1
      });
      var qxListItem1 = new qx.ui.form.ListItem(this.tr('Normal'), null, null);
      modelSelectBox.add(qxListItem1);
      qxListItem1.setUserData("value", "normal");
      var qxListItem2 = new qx.ui.form.ListItem(this.tr('Read-Only'), null, null);
      modelSelectBox.add(qxListItem2);
      qxListItem2.setUserData("value", "readonly");
      modelSelectBox.addListener("appear", function(e)
      {
        var mode = this.getApplication().getConfigManager().getKey("bibliograph.access.mode");
        modelSelectBox.getSelectables().forEach(function(elem) {
          if (elem.getUserData("value") == mode)modelSelectBox.setSelection([elem]);

        }, this);
      }, this);
      modelSelectBox.addListener("changeSelection", function(e) {
        if (e.getData().length)this.getApplication().getConfigManager().setKey("bibliograph.access.mode", e.getData()[0].getUserData("value"));

      }, this);
      var qxLabel6 = new qx.ui.basic.Label(this.tr('Custom message'));
      qxPage3.add(qxLabel6,
      {
        row : 1,
        column : 0
      });
      var qxTextarea2 = new qx.ui.form.TextArea(null);
      qxTextarea2.setPlaceholder(this.tr('Shown to users in read-only and no-access mode.'));
      qxPage3.add(qxTextarea2,
      {
        row : 1,
        column : 1
      });
      confMgr.addListener("ready", function() {
        confMgr.bindKey("bibliograph.access.no-access-message", qxTextarea2, "value", true);
      });
    }
  }
});
