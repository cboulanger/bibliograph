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
qx.Class.define("bibliograph.ui.item.DuplicatesView",
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
    duplicatesTable : null,

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

    /**
     * TODOC
     *
     * @return {void}
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

    /**
     * TODOC
     *
     * @return {void}
     */
    reloadData : function()
    {
      var app = this.getApplication();
      if (!this.isVisible() || !app.getModelId()) {
        return;
      }
      var id = app.getModelId();
      qx.event.Timer.once(function()
      {
        if (id != app.getModelId())return;

        this._reloadData();
      }, this, 100);
    },

    /**
     * TODOC
     *
     * @return {void}
     */
    _reloadData : function()
    {
      var app = this.getApplication();
      this.duplicatesTable.getTableModel().setData([]);
      this.showPopup(this.tr("Searching for duplicates..."));
      app.getRpcManager().execute("bibliograph.reference", "getDuplicatesData", [app.getDatasource(), app.getModelId()], function(data)
      {
        this.hidePopup();
        this.duplicatesTable.getTableModel().setData(data);
      }, this);
    },

    /**
     * TODOC
     *
     * @return {void}
     */
    inspectDuplicate : function() {
    },

    // @todo

    /**
     * TODOC
     *
     * @return {var} TODOC
     */
    getSelectedRefIds : function()
    {
      var selectedRefIds = [];
      var selectionModel = this.duplicatesTable.getSelectionModel();
      var tableModel = this.duplicatesTable.getTableModel();
      selectionModel.iterateSelection(function(index) {
        selectedRefIds.push(tableModel.getValue(0, index));
      });
      return selectedRefIds;
    },

    /**
     * TODOC
     *
     * @return {void}
     */
    deleteDuplicate : function()
    {
      var app = this.getApplication();
      var selectedRefIds = this.getSelectedRefIds();
      if (!selectedRefIds.length)return;

      var message = this.tr("Do your really want to move the selected duplicates to the trash?");
      var handler = qx.lang.Function.bind(function(result) {
        if (result === true)
        {
          this.showPopup(this.tr("Processing request..."));
          app.getRpcManager().execute("bibliograph.reference", "moveToTrash", [app.getDatasource(), selectedRefIds], function()
          {
            this.hidePopup();
            this.reloadData();
          }, this);
        }
      }, this);
      dialog.confirm(message, handler);
    },
    endOfFile : true
  }
});
