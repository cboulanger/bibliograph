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

/**
 *
 */
qx.Class.define("bibliograph.ui.item.RecordInfo",
{
  extend : qx.ui.container.Composite,
  include : [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties : {

  },

  /*
  *****************************************************************************
      CONSTRUCTOR
  *****************************************************************************
  */
  construct : function()
  {
    this.base(arguments);
    this.createPopup();
  },

  /*
  *****************************************************************************
      MEMBERS
  *****************************************************************************
  */
  members :
  {
    /*
    ---------------------------------------------------------------------------
       WIDGETS
    ---------------------------------------------------------------------------
    */
    recordInfoHtml : null,
    containingFoldersTable : null,

    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */

    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */

    /*
    ---------------------------------------------------------------------------
       EVENT HANDLERS
    ---------------------------------------------------------------------------
    */
    _on_appear : function()
    {
      var app = this.getApplication();
      if (app.getModelId() !== this.__modelId)
      {
        this.__modelId = app.getModelId();
        this.reloadData();
      }
    },

    /*
    ---------------------------------------------------------------------------
       INTERNAL METHODS
    ---------------------------------------------------------------------------
    */

    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */
    reloadData : function()
    {
      var app = this.getApplication();
      if (!this.isVisible()) {
        return;
      }
      var id = app.getModelId();
      qx.event.Timer.once(function()
      {
        if (!id || id != app.getModelId())return;

        this.recordInfoHtml.setHtml("");
        this.containingFoldersTable.getTableModel().setData([]);
        this.reloadRecordInfo();
        this.reloadFolderData();
      }, this, 100);
    },
    reloadRecordInfo : function()
    {
      var app = this.getApplication();
      var modelId = app.getModelId();
      if (!modelId)return;

      app.getRpcClient("reference").send( "getRecordInfoHtml", [app.getDatasource(), modelId], function(data) {
        this.recordInfoHtml.setHtml(data.html);
      }, this);
    },
    reloadFolderData : function()
    {
      var app = this.getApplication();
      var modelId = app.getModelId();
      if (!modelId)return;

      app.getRpcClient("reference").send( "getContainingFolderData", [app.getDatasource(), modelId], function(data) {
        this.containingFoldersTable.getTableModel().setData(data);
      }, this);
    },
    getSelectedFolders : function()
    {
      var selectedFolders = [];
      var selectionModel = this.containingFoldersTable.getSelectionModel();
      var tableModel = this.containingFoldersTable.getTableModel();
      selectionModel.iterateSelection(function(index) {
        selectedFolders.push(
        {
          id : tableModel.getValue(0, index),
          label : tableModel.getValue(2, index)
        });
      });
      return selectedFolders;
    },
    openFolder : function()
    {
      var selectedFolders = this.getSelectedFolders();
      if (selectedFolders.length) {
        this.getApplication().setFolderId(selectedFolders[0].id);
      }
    },
    removeFromFolder : function()
    {
      var app = this.getApplication();
      var selectedFolders = this.getSelectedFolders();
      if (!selectedFolders.length)return;

      var message = this.tr("Do you really want to remove the reference from folder '%1'?", selectedFolders[0].label);
      var handler = qx.lang.Function.bind(function(result) {
        if (result === true)
        {
          this.showPopup(this.tr("Processing request..."));
          app.getRpcClient("reference").send( "removeReferences", [app.getDatasource(), selectedFolders[0].id, null, [app.getModelId()]], function()
          {
            this.hidePopup();
            this.reloadFolderData();
          }, this);
        }
      }, this);
      dialog.Dialog.confirm(message, handler);
    },
    endOfFile : true
  }
});
