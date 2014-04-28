/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/*global qx bibliograph*/

/**
 * ISBN scanner plugin
 *
 */
qx.Class.define("bibliograph.plugin.isbnscanner.Plugin",
{
  extend : qx.core.Object,
  include : [qx.locale.MTranslation],
  type : "singleton",
  members : {
    /**
     * TODOC
     *
     * @return {void}
     */
    init : function()
    {
      /*
       * find menu to attach menu buttons to 
       */
      var app = qx.core.Init.getApplication();
      var importMenu = app.getWidgetById("importMenu");
      
      /*
       * use barcode scanner (keyboard mode)
       */
      var menuButton1 = new qx.ui.menu.Button(this.tr("From ISBN, using barcode scanner or manual input"));
      menuButton1.setToolTip(new qx.ui.tooltip.ToolTip(this.tr("Please select a folder into which the item will be imported. Requires a barcode scanner in keyboard mode")));
      menuButton1.setEnabled(true); 
      app.addListener("changeFolderId",function(folderId){
        if(folderId){menuButton1.setEnabled(true)};
      });
      menuButton1.addListener("execute", function() {
        app.showPopup(this.tr("Please wait ..."));
        app.getRpcManager().execute(
            "bibliograph.plugin.isbnscanner.Service", "enterIsbnDialog",
            [app.getDatasource(),app.getFolderId()],
            function(data) {
              app.hidePopup();
            }, this);
      });
      importMenu.add(menuButton1);      
      
      
      /*
       * use iOD device to scan ISBN
       */
      var menuButton2 = new qx.ui.menu.Button(this.tr("From ISBN barcode, scanned with iOS device"));
      menuButton2.setToolTip(new qx.ui.tooltip.ToolTip(this.tr("Please select a folder into which the item will be imported. Requires the Scanner Go app on the iOS device.")));
      menuButton2.setEnabled(false);
      app.addListener("changeFolderId",function(folderId){
        if(folderId){menuButton2.setEnabled(true)};
      });
      menuButton2.addListener("execute", function() {
        app.showPopup(this.tr("Please wait ..."));
        app.getRpcManager().execute(
            "bibliograph.plugin.isbnscanner.Service", "confirmEmailAddress",
            [app.getDatasource(),app.getFolderId()],
            function(data) {
              app.hidePopup();
            }, this);
      });
      //importMenu.add(menuButton2);
    }
  }
});

/*
 * initialize plugin
 */
bibliograph.plugin.isbnscanner.Plugin.getInstance().init();
