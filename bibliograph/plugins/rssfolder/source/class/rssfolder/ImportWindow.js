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
 *
 */
qx.Class.define("rssfolder.ImportWindow",
{
  extend : qx.ui.window.Window,
  include : [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  /**
   * @todo rewrite the cache stuff!
   */
  construct : function()
  {
    this.base(arguments);
    this.createPopup();
    qx.lang.Function.delay(function(){
      this.listView.addListenerOnce("tableReady", function() {
        var controller = this.listView.getController();
        function enableButtons (){
          this.importButton.setEnabled(true);
          this.listView.setEnabled(true);
          this.hidePopup();
        }
        controller.addListener("blockLoaded", enableButtons, this);
        controller.addListener("statusMessage", function(e){
          this.showPopup(e.getData());
          qx.lang.Function.delay( enableButtons, 1000, this);
        }, this);
      }, this);
    },100,this);
  },

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties : {
  },

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  members :
  {
    form : null,
    file : null,
    listView : null,
    toolBar : null,
    importButton : null,
    loadFeedBtn : null,


    /**
     * Apply filter property
     */
    _applyFilter : function(value, old) {
      if (this.uploadButton)
      {
        this.uploadButton.setEnabled(value !== null && this.file.getFieldValue != '');
      }
    },
    
    _on_loadFeedBtn_execute : function()
    {
      var url = this.feedUrlTextField.getValue();
      var app = this.getApplication();
      app.showPopup(this.tr("Please wait ..."));
      app.getRpcManager().execute(
          "rssfolder.service", "parseFeed",
          [url],
          function(data) {
            app.hidePopup();
            this.listView.setFolderId(data.folderId);
            this.selectAllButton.setEnabled(true);
          }, this);
    },
    
    /**
     * Import the selected references into the datasource
     */
    importSelected : function()
    {
      var app = this.getApplication();

      /*
       * target folder
       */
      var targetFolderId = app.getFolderId();
      if (!targetFolderId)
      {
        dialog.Dialog.alert(this.tr("Please select a folder first."));
        return false;
      }
      var treeView = app.getWidgetById("mainFolderTree");
      var nodeId = treeView.getController().getClientNodeId(targetFolderId);
      var node = treeView.getTree().getDataModel().getData()[nodeId];
      if (!node)
      {
        dialog.Dialog.alert(this.tr("Cannot determine selected folder. Please reload the folders."));
        return false;
      }
      if (node.data.type != "folder")
      {
        dialog.Dialog.alert(this.tr("Invalid target folder. You can only import into normal folders."));
        return false;
      }

      /*
       * send to server
       */
      var targetDatasource = app.getDatasource();
      var ids = this.listView.getSelectedIds();
      this.showPopup(this.tr("Importing references..."));
      this.getApplication().getRpcManager().execute(
        "rssfolder.service", "import", 
        [ids, targetDatasource, targetFolderId], 
        function() {
          this.hidePopup();
        }, this
      );
    }
  }
});