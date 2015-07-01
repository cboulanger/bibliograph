/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/*global qx qcl bibliograph dialog rssfolder*/

/**
 * Plugin Initializer Class
 * 
 */
qx.Class.define("rssfolder.Plugin",
{
  extend : qx.core.Object,
  include : [qx.locale.MTranslation],
  type : "singleton",
  members : {
    init : function()
    {
      
      // Manager shortcuts
      var app = qx.core.Init.getApplication();
      var permMgr = app.getAccessManager().getPermissionManager();
      var confMgr = app.getConfigManager();
      
      // add feed url button
      var settingsMenu = app.getWidgetById("bibliograph/folder-settings-menu");
      var feedUrlBtn = new qx.ui.menu.Button(this.tr('Folder RSS Feed'));
      feedUrlBtn.setVisibility("excluded");
      settingsMenu.add(feedUrlBtn);
      
      permMgr.create("rssfolder.view").bind("state", feedUrlBtn, "visibility", {
        converter : qcl.bool2visibility
      });
      app.bind("folderId",feedUrlBtn,"enabled",{
        converter : function(folderId){ return folderId > 0;}
      });

      // action for feed url button
      feedUrlBtn.addListener("execute", function(e) {
        app.getRpcManager().execute(
          "rssfolder.service","getFeedUrl",
          [app.getDatasource(),app.getFolderId()],
          function (url){ 
            this.__rssWindow = window.open(url,"bibliograph-rss-window");
            if (!this.__rssWindow) {
              dialog.Dialog.alert(this.tr("Cannot open window. Please disable the popup-blocker of your browser for this website."));
            }
            this.__rssWindow.focus();            
          }
        );
      }, this);  
      
      /*
       * Import window
       */
      var importWindow = new rssfolder.ImportWindowUi();
      importWindow.setWidgetId("rssfolder/importWindow");
      importWindow.setVisibility("excluded");
      this.getApplication().getRoot().add(importWindow);      
      
      /*
       * Overlay for import menu
       */
      var importMenu = app.getWidgetById("bibliograph/importMenu");
      var menuButton1 = new qx.ui.menu.Button(this.tr("From RSS Feed"));
      menuButton1.setEnabled(false);
      menuButton1.setVisibility("excluded");
      app.addListener("changeFolderId",function(folderId){
        if(folderId){menuButton1.setEnabled(true)};
      });
      menuButton1.addListener("execute", function() {
        importWindow.open();
      });
      permMgr.create("isbnscanner.import").bind("state", menuButton1, "visibility", {
        converter : function(v){ return v ? "visible" : "excluded" }
      });
      importMenu.add(menuButton1);

    }
  }
});

