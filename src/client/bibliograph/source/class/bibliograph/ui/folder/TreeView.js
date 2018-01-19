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
 * The folder tree view. Most of the business logic is in {@see qcl.ui.treevirtual.TreeView},
 * here we only add confirm dialogs.
 */
qx.Class.define("bibliograph.ui.folder.TreeView",
{
  extend : qcl.ui.treevirtual.TreeView,
  construct : function()
  {
    this.base(arguments);
    this.setServiceName("folder");
    this.setModelType("folder");
  },
  members :
  {
    // /**
    //  * Overridden. Called when a dragged element is dropped onto the tree widget
    //  * @param e {qx.event.type.Drag}
    //  */
    // _on_drop : function(e) {
    //   if (e.supportsType("qx/treevirtual-node"))
    //   {
    //     var tree = this.getTree();
    //     tree.addListenerOnce("changeNodePosition", function(e)
    //     {
    //       var data = e.getData();
    //       var nodeId = data.node.data.id;
    //       var position = data.position;
    //       this.getStore().execute("changeFolderPosition", [this.getDatasource(), nodeId, position]);
    //     }, this);
    //     tree.moveNode(e);
    //   }
    // },

    /**
     * TODOC
     *
     * @return {void}
     */
    _editFolder : function()
    {
      this.setEnabled(false);
      this.getApplication().getRpcClient("folder").send("edit", [this.getDatasource(), this.getSelectedNode().data.id], function() {
        this.setEnabled(true);
      }, this);
    },

    /**
     * TODOC
     *
     * @return {void}
     */
    _changePublicState : function()
    {
      this.setEnabled(false);
      this.getApplication().getRpcClient("folder").send( "changePublicStateDialog", [this.getDatasource(), this.getSelectedNode().data.id], function() {
        this.setEnabled(true);
      }, this);
    },

    /**
     * TODOC
     *
     * @return {void}
     */
    _addFolderDialog : function()
    {
      this.setEnabled(false);
      this.getApplication().getRpcClient("folder").send( "addFolderDialog", [this.getDatasource(), this.getNodeId()], function() {
        this.setEnabled(true);
      }, this);
    },

    /**
     * TODOC
     *
     * @return {void}
     */
    _removeFolderDialog : function()
    {
      this.setEnabled(false);
      this.getApplication().getRpcClient("folder").send( "removeFolderDialog", [this.getDatasource(), this.getNodeId()], function() {
        this.setEnabled(true);
      }, this);
    },


    /**
     * Dialog to confim a folder move
     */
    _moveFolderDialog : function()
    {
      var app = this.getApplication();
      var win = app.getWidgetById("bibliograph/folderTreeWindow");
      win.addListenerOnce("nodeSelected", function(e)
      {
        var node = e.getData();
        if (!node)
        {
          dialog.Dialog.alert(this.tr("No folder selected. Try again"));
          return;
        }
        var message = this.tr("Do your really want to move folder '%1' to '%2'?", this.getSelectedNode().label, node.label);
        var handler = qx.lang.Function.bind(function(result) {
          if (result === true)
          {
            this.setEnabled(false);
            this.getApplication().getRpcClient("folder").send( "moveFolder", [this.getDatasource(), this.getNodeId(), node.data.id], function() {
              this.setEnabled(true);
            }, this);
          }
        }, this);
        dialog.Dialog.confirm(message, handler);
      }, this);
      win.show();
    },

    /**
     * Shows dialog asking whether the user wants to empty the trash
     * folder.
     */
    _emptyTrashDialog : function() {
      dialog.Dialog.confirm(this.tr("Do you really want to delete all references and folders in the trash folder? This cannot be undone."), qx.lang.Function.bind(function(answer) {
        if (answer == true)
        {
          let app = this.getApplication()
          app.setModelId(0);
          app.showPopup(this.tr("Purging deleted folders ..."))
          app.getRpcClient("folder").send( "purge", [this.getDatasource()], function()
          {
            app.showPopup(this.tr("Purging deleted references ..."))
            app.getRpcClient("reference").send( "purge", [this.getDatasource()], function() {
              app.hidePopup();
            }, this);
          }, this);
        }
      }, this));
    },

    /**
     * Shows a status message
     * @param msg {String}
     */
    showMessage : function(msg)
    {
      this._statusLabel.setValue(msg);
    },

    endOfClass : true
  }
});
