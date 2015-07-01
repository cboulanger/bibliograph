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

/*global qx isbnscanner bibliograph*/

/**
 * ISBN scanner plugin
 */
qx.Class.define("isbnscanner.Plugin",
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
      // Manager shortcuts
      var app = qx.core.Init.getApplication();
      var permMgr = app.getAccessManager().getPermissionManager();
      var confMgr = app.getConfigManager();

      /*
       * Overlays for import menu
       */
      var importMenu = app.getWidgetById("bibliograph/importMenu");
      
      /*
       * use barcode scanner (keyboard mode)
       */
      var menuButton1 = new qx.ui.menu.Button(this.tr("From ISBN, using barcode scanner or manual input"));
      menuButton1.setToolTip(new qx.ui.tooltip.ToolTip(this.tr("Please select a folder into which the item will be imported. Requires a barcode scanner in keyboard mode")));
      menuButton1.setEnabled(false);
      menuButton1.setVisibility("excluded");
      app.addListener("changeFolderId",function(folderId){
        if(folderId){menuButton1.setEnabled(true)};
      });
      menuButton1.addListener("execute", function() {
        listener.stop_listening();
        app.showPopup(this.tr("Please wait ..."));
        app.getRpcManager().execute(
            "isbnscanner.Service", "enterIsbnDialog",
            [app.getDatasource(),app.getFolderId()],
            function(data) {
              app.hidePopup();
            }, this);
      });
      permMgr.create("isbnscanner.import").bind("state", menuButton1, "visibility", {
        converter : function(v){ return v ? "visible" : "excluded" }
      });
      importMenu.add(menuButton1);
      
      
//      /*
//       * use iOD device to scan ISBN
//       */
//      var menuButton2 = new qx.ui.menu.Button(this.tr("From ISBN barcode, scanned with iOS device"));
//      menuButton2.setToolTip(new qx.ui.tooltip.ToolTip(this.tr("Please select a folder into which the item will be imported. Requires the Scanner Go app on the iOS device.")));
//      menuButton2.setEnabled(false);
//      app.addListener("changeFolderId",function(folderId){
//        if(folderId){menuButton2.setEnabled(true)};
//      });
//      menuButton2.addListener("execute", function() {
//        listener.stop_listening();
//        app.showPopup(this.tr("Please wait ..."));
//        app.getRpcManager().execute(
//            "isbnscanner.Service", "confirmEmailAddress",
//            [app.getDatasource(),app.getFolderId()],
//            function(data) {
//              app.hidePopup();
//            }, this);
//      });
//      importMenu.add(menuButton2);

      /*
       * setup global listener for input of isbn numbers (with scanner)
       */
      var listener = new window.keypress.Listener();
      listener.sequence_combo("9 7 8", function() {
        listener.stop_listening();
        app.showPopup(this.tr("ISBN input detected. Please wait for the input dialog and repeat the input ..."));
        app.getRpcManager().execute(
            "isbnscanner.Service", "enterIsbnDialog",
            [app.getDatasource(),app.getFolderId()],
            function(data) {
              app.hidePopup();
            }, this);
      }.bind(this), true);

      var bus = qx.event.message.Bus;
      bus.subscribe("plugin.isbnscanner.ISBNInputListener.start", function(e){ // todo rename
        listener.listen();
      },this);
      bus.subscribe("plugin.isbnscanner.ISBNInputListener.stop", function(e){
        listener.stop_listening();
      },this);


      /*
       * Overlays for preference window
       */
      var prefsTabView = app.getWidgetById("bibliograph/preferences-tabView");
      var pluginTab = new qx.ui.tabview.Page( this.tr('Import by ISBN') );
      pluginTab.setVisibility("excluded");
      
      permMgr.create("isbnscanner.import").bind("state", pluginTab, "visibility", {
        converter : function(v){ return v ? "visible" : "excluded" }
      });

      var gridlayout = new qx.ui.layout.Grid();
      gridlayout.setSpacing(5);
      pluginTab.setLayout(gridlayout);
      gridlayout.setColumnWidth(0, 200);
      gridlayout.setColumnFlex(1, 2);

      var msg= this.tr("How should names (e.g. William Shakespeare) be converted to a sortable format (Shakespeare, William)?");
      var label1 = new qx.ui.basic.Label(msg);
      label1.setRich(true);
      pluginTab.add(label1,{row : 0, column : 0 });

      var modelSelectBox = new qx.ui.form.SelectBox();
      modelSelectBox.setMaxHeight(30);
      pluginTab.add(modelSelectBox,{ row : 0, column : 1 });

      var qxListItem1 = new qx.ui.form.ListItem(this.tr('Name parser (Fast, Correct for most western names)'));
      modelSelectBox.add(qxListItem1);
      qxListItem1.setUserData("value", "parser");

      var qxListItem2 = new qx.ui.form.ListItem(this.tr('Webservice (Slower)'));
      modelSelectBox.add(qxListItem2);
      qxListItem2.setUserData("value", "web");

      var prefName = "bibliograph.sortableName.engine";
      modelSelectBox.addListener("appear", function(e)
      {
        var engine = confMgr.getKey(prefName);
        modelSelectBox.getSelectables().forEach(function(elem) {
          if (elem.getUserData("value") == engine) modelSelectBox.setSelection([elem]);
        }, this);
      }, this);
      modelSelectBox.addListener("changeSelection", function(e) {
        if (e.getData().length) confMgr.setKey(prefName, e.getData()[0].getUserData("value"));
      }, this);
      
      // add tab to tabview (must be done at the end)
      prefsTabView.add(pluginTab);      
    }
  }
});
