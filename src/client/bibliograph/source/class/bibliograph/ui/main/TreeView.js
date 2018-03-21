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
 * this adds server actions
 *
 * @asset(bibliograph/icon/button-plus.png)
 * @asset(bibliograph/icon/button-reload.png)
 * @asset(bibliograph/icon/button-settings-up.png)
 * @asset(bibliograph/icon/button-minus.png)
 */
qx.Class.define("bibliograph.ui.main.TreeView",
{
  extend: qcl.ui.treevirtual.TreeView,
  include : [qcl.access.MPermissions],
  
  construct: function () {
    this.base(arguments);
    this.setServiceName("folder"); // @todo remove?
    this.setModelType("folder");

    // messages
    let bus = qx.event.message.Bus.getInstance();
    bus.subscribe(bibliograph.AccessManager.messages.LOGIN, () => this.reload());
    bus.subscribe(bibliograph.AccessManager.messages.LOGOUT, () => this.reload());
  
    // permissions
    this.setupPermissions();
    
    // context menu
    this.setupTreeCtxMenu();
    
    this.setEnableDragDrop(true);
  },
  
  members:
  {
    /**
     * The rpc proxy
     */
    rpc : rpc.Folder,
  
    /**
     * Permissions
     */
    permissions : {
      add_folder : {
        depends: "folder.add",
        granted : true,
        updateEvent : "changeSelectedNode",
        condition : tree => tree.getSelectedNode() !== null
      },
      remove_folder : {
        depends: "folder.remove",
        granted : true,
        updateEvent : "changeSelectedNode",
        condition : tree => tree.getSelectedNode() !== null
      },
      edit_folder : {
        depends : "folder.edit",
        granted : true,
        updateEvent : "changeSelectedNode",
        condition : tree => tree.getSelectedNode() !== null
      },
      move_folder : {
        depends : "folder.move",
        granted : true,
        updateEvent : "changeSelectedNode",
        condition : tree => tree.getSelectedNode() !== null
      },
      empty_trash : {
        depends : "trash.empty",
        granted : true,
        updateEvent : "changeSelectedNode",
        condition : tree => tree.getSelectedNode() && tree.getSelectedNode().data.type === "trash"
      },
    },
    
    // /**
    //  * Overridden. Called when a dragged element is dropped onto the tree widget
    //  * @param e {qx.event.type.Drag}
    //  */
    // _on_drop : function(e) {
    //   if (e.supportsType("qx/treevirtual-node"))
    //   {
    //     let tree = this.getTree();
    //     tree.addListenerOnce("changeNodePosition", function(e)
    //     {
    //       let data = e.getData();
    //       let nodeId = data.node.data.id;
    //       let position = data.position;
    //       this.getStore().execute("position-change", [this.getDatasource(), nodeId, position]);
    //     }, this);
    //     tree.moveNode(e);
    //   }
    // },
  
    //-------------------------------------------------------------------------
    //  USER INTERFACE
    //-------------------------------------------------------------------------
    
    createAddButton : function(menubar=false)
    {
      let button = menubar
        ? new qx.ui.menubar.Button(null, "bibliograph/icon/button-plus.png")
        : new qx.ui.menu.Button(this.tr("Add folder"));
      button.addListener("click", this._addFolderDialog, this);
      this.getPermission("folder.add").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      this.permissions.add_folder.bind("state", button, "enabled");
      return button;
    },
  
    createRemoveButton : function(menubar=false)
    {
      let button = menubar
        ? new qx.ui.menubar.Button(null, "bibliograph/icon/button-minus.png")
        : new qx.ui.menu.Button(this.tr("Remove folder"));
      button.addListener("click", this._removeFolderDialog, this);
      this.getPermission("folder.remove").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      this.permissions.add_folder.bind("state", button, "enabled");
      return button;
    },
  
    createReloadButton : function()
    {
      let button = new qx.ui.menubar.Button(null, "bibliograph/icon/button-reload.png");
      button.addListener("execute", () => this.reload());
      return button;
    },
  
    createEmptyTrashButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr("Empty trash..."));
      button.setLabel(this.tr("Empty trash..."));
      button.addListener("execute", this._emptyTrashDialog, this);
      this.permissions.empty_trash.bind("state", button, "enabled");
      this.getPermission("trash.empty").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      return button;
    },
    
    createMoveButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr("Move folder..."));
      button.addListener("execute", this._moveFolderDialog, this);
      this.permissions.move_folder.bind("state", button, "enabled");
      this.getPermission("folder.move").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      return button;
    },
  
    createEditButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr("Edit folder data"));
      button.addListener("execute", this._editFolder, this);
      this.permissions.edit_folder.bind("state", button, "enabled");
      this.getPermission("folder.edit").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      return button;
    },
    
    createVisibilityButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr("Change visibility"));
      button.setLabel(this.tr("Change visibility"));
      button.addListener("execute", this._changePublicState, this);
      this.permissions.edit_folder.bind("state", button, "enabled");
      this.getPermission("folder.edit").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      return button;
    },
  
    /**
     * Context menu for tree widget
     */
    setupTreeCtxMenu : function()
    {
      // context menu
      this.addListener("changeTree", e => {
        let tree = e.getData();
        if (! tree) return;
        tree.setContextMenuHandler(0, (col, row, table, dataModel, contextMenu) => {
          contextMenu.add(this.createAddButton());
          contextMenu.add(this.createRemoveButton());
          contextMenu.add(this.createEditButton());
          contextMenu.add(this.createMoveButton());
          contextMenu.add(this.createVisibilityButton());
          contextMenu.add(this.createEmptyTrashButton());
          return true;
        });
      });
    },
    
    //-------------------------------------------------------------------------
    //  SERVER ACTIONS
    //-------------------------------------------------------------------------
    
    /**
     * Triggers server dialog to edit folder properties
     */
    _editFolder: function ()
    {
      this.rpc.edit(this.getDatasource(), this.getSelectedNode().data.id);
    },

    /**
     * shows the visibility editor
     */
    _changePublicState: function ()
    {
      this.rpc.visibilityDialog(this.getDatasource(), this.getSelectedNode().data.id);
    },

    /**
     * Triggers server dialog to add a folder
     */
    _addFolderDialog: function ()
    {
     this.rpc.addDialog(this.getDatasource(), this.getNodeId());
    },

    /**
     * Triggers server dialog to remove a folder
     */
    _removeFolderDialog: function ()
    {
      this.rpc.removeDialog(this.getDatasource(), this.getNodeId());
    },

    /**
     * Shows dialog to confim a folder move
     */
    _moveFolderDialog : function ()
    {
      let app = this.getApplication();
      let win = app.getWidgetById("app/windows/folders");
      if( !win ){
        this.warn("Cannot open folder dialog!");
        return;
      }
      win.addListenerOnce("nodeSelected", e => {
        let node = e.getData();
        if (!node) {
          dialog.Dialog.alert(this.tr("No folder selected. Try again"));
          return;
        }
        let message = this.tr("Do your really want to move folder '%1' to '%2'?", this.getSelectedNode().label, node.label);
        dialog.Dialog.confirm(message).promise().then(result => {
          if (result === true) this.rpc.move(this.getDatasource(), this.getNodeId(), node.data.id);
        });
        win.show();
      });
    },

    /**
     * Shows dialog asking whether the user wants to empty the trash
     * folder.
     */
    _emptyTrashDialog : function()
    {
      let msg = this.tr("Do you really want to delete all references and folders in the trash folder? This cannot be undone.");
      dialog.Dialog.confirm(msg).promise()
      .then( answer => {
        if (answer === false) return;
        let app = this.getApplication();
        app.setModelId(0);
        app.showPopup(this.tr("Emptying the trash ..."));
        rpc.Trash.empty(this.getDatasource()).then(()=> app.hidePopup());
      });
    },

    /**
     * Shows a status message
     * @param msg {String}
     */
    showMessage: function (msg) {
      this._statusLabel.setValue(msg);
    }
  }
});
