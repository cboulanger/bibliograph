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

/**
 *
 */
qx.Class.define("bibliograph.plugin.z3950.ImportWindow",
{
  extend : qx.ui.window.Window,
  include : [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  /**
   * Constructor
   */
  construct : function()
  {
    this.base(arguments);
    this.createPopup();
    qx.lang.Function.delay(function(){
      this.listView.addListenerOnce("tableReady", function() {
        var controller = this.listView.getController();
        controller.addListener("blockLoaded", function(){
          this.importButton.setEnabled(true);
          this.searchButton.setEnabled(true);
          this.listView.setEnabled(true);
          this.hidePopup();
        }, this);
        // show status messages
        controller.addListener("statusMessage", function(e){
          this.showPopup(e.getData());
        }, this);
      }, this);
    },100,this);
  },

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  members :
  {
    listView : null,
    datasourceSelectBox : null,
    searchBox : null,
    searchButton : null,
    statusTextLabel : null,

    /**
     * Called when the user presses a key in the search box
     * @param e {qx.event.type.Data}
     */
    _on_keypress : function(e) {
      if (e.getKeyIdentifier() == "Enter")
      {
        this.startSearch();
      }
    },

    /**
     * Starts the search
     */
    startSearch : function()
    {
      this.listView.clearTable();
      this.importButton.setEnabled(false);
      this.searchButton.setEnabled(false);
      this.listView.setEnabled(false);
      this.listView.setQuery(null);
      this.listView.setQuery(this.searchBox.getValue());
    },

    /**
     * Imports the selected references
     */
    importSelected : function()
    {
      var app = this.getApplication();

      /*
       * ids to import
       */
      var ids = this.listView.getSelectedIds();
      if (!ids.length)
      {
        dialog.Dialog.alert(this.tr("You have to select one or more reference to import."));
        return false;
      }

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
        dialog.Dialog.alert(this.tr("Cannot determine selected folder."));
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
      var sourceDatasource = "z3950_gbv";  //this.datasourceSelectBox.getSelection()[0];
      var targetDatasource = app.getDatasource();
      this.showPopup(this.tr("Importing references..."));
      this.getApplication().getRpcManager().execute("bibliograph.plugin.z3950.Service", "importReferences", [sourceDatasource, ids, targetDatasource, targetFolderId], function()
      {
        this.importButton.setEnabled(true);
        this.hidePopup();
      }, this);
    },
    markForTranslation : function() {
      this.tr("Import from library catalogue");
    }
  }
});
