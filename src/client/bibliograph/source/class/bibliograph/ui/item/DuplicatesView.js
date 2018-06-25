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
qx.Class.define("bibliograph.ui.item.DuplicatesView",
{
  extend : qx.ui.container.Composite,

  /*
   *****************************************************************************
   PROPERTIES
   *****************************************************************************
   */
  properties :
  {
    /**
     * The number of potential duplicates of the current reference
     */
    numberOfDuplicates :
    {
      check : "Number",
      nullable : false,
      init : 0,
      event : "changeNumberOfDuplicates"
    }
  },

  /*
   *****************************************************************************
   CONSTRUCTOR
   *****************************************************************************
   */

  construct : function()
  {
    this.base(arguments);
    qx.event.message.Bus.subscribe("reference.changeData",this.reloadData, this);
  },

  /*
   *****************************************************************************
   MEMBERS
   *****************************************************************************
   */
  members :
  {
    duplicatesTable : null,


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
      var permMgr = app.getAccessManager().getPermissionManager();
      if ( ! app.getModelId() ||
           ! permMgr.getByName("reference.remove").isGranted() )  // todo permission name
      {
        return;
      }

      var id = app.getModelId();
      var timeoutId = qx.lang.Function.delay(function()
      {
        if (id != app.getModelId() || timeoutId != this.__timeoutId) return;
        this._reloadData();
      }, 500, this);
      this.__timeoutId = timeoutId;
    },

    /**
     * TODOC
     *
     * @return {void}
     */
    _reloadData : function()
    {
      var app = this.getApplication();
      this.duplicatesTable.getSelectionModel().resetSelection();
      this.duplicatesTable.getTableModel().setData([]);
      this.setEnabled(false);
      if( !app.getDatasource() || !app.getModelId() ) return;
      app.getRpcClient("reference").send(
          "duplicates-data",
          [app.getDatasource(), app.getModelId()],
          function(data) {
            this.setEnabled(true);
            this.duplicatesTable.getTableModel().setData(data);
            this.setNumberOfDuplicates(data.length);
          }, this);
    },


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
    _deleteDuplicate : function()
    {
      var app = this.getApplication();
      var selectedRefIds = this.getSelectedRefIds();
      if (!selectedRefIds.length)return;

      app.showPopup(this.tr("Processing request..."));
      this.duplicatesTable.getSelectionModel().resetSelection();
      app.getRpcClient("reference").send(
          "remove",
          [app.getDatasource(), null, null, selectedRefIds],
          function() {
            app.hidePopup();
            this.reloadData();
          }, this);
    },

    /**
     * Displays the selected duplicate
     * @private
     */
    _displayDuplicate : function()
    {
      var selectedRefIds = this.getSelectedRefIds();
      if (!selectedRefIds.length)return;
      var id= selectedRefIds[0];
      var app = this.getApplication();
      app.setQuery("id="+id); // todo: open in new window
      app.setModelId(id);
      this.duplicatesTable.getSelectionModel().resetSelection();
      qx.lang.Function.delay(function(){
        app.setItemView("referenceEditor-recordInfo");
      },100);

    },


    endOfFile : true
  }
});
