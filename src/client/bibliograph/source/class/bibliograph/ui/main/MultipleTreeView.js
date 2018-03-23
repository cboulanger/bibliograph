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
 * The folder tree view. Most of the business logic is in {@see qcl.ui.treevirtual.MultipleTreeView},
 * this adds server actions
 *
 * @asset(bibliograph/icon/button-plus.png)
 * @asset(bibliograph/icon/button-reload.png)
 * @asset(bibliograph/icon/button-settings-up.png)
 * @asset(bibliograph/icon/button-minus.png)
 */
qx.Class.define("bibliograph.ui.main.MultipleTreeView",
{
  extend: qcl.ui.treevirtual.MultipleTreeView,
  include : [qcl.access.MPermissions],
  
  construct: function () {
    this.base(arguments);
    this.setServiceName("folder"); // @todo remove?
    this.setModelType("folder");

    // messages
    let bus = qx.event.message.Bus.getInstance();
    bus.subscribe(bibliograph.AccessManager.messages.LOGIN,  () => this.reload());
    bus.subscribe(bibliograph.AccessManager.messages.LOGOUT, () => this.reload());
  
    // permissions
    this.setupPermissions();
    
    // context menu
    this.setupTreeCtxMenu();
    this.set({
      enableDragDrop : true,
      debugDragSession : true
    });
    
    // configure new tree
    this.addListener("changeTree",e => {
      /** @var {qcl.ui.treevirtual.DragDropTree}  */
      let tree = e.getData();
      if( ! tree ) return;
      tree.setExcludeDragTypes( new qx.data.Array('trash','top'));
    })
    
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
      change_position : {
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
    
  
    //-------------------------------------------------------------------------
    //  USER INTERFACE
    //-------------------------------------------------------------------------
    
    createAddButton : function(menubar=false)
    {
      let button = menubar
        ? new qx.ui.menubar.Button(null, "bibliograph/icon/button-plus.png")
        : new qx.ui.menu.Button(this.tr("Add folder"));
      button.addListener("click", ()=> this._addFolderDialog());
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
      button.addListener("click", ()=> this._removeFolderDialog());
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
      button.addListener("execute", ()=>this._emptyTrashDialog());
      this.permissions.empty_trash.bind("state", button, "enabled");
      this.getPermission("trash.empty").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      return button;
    },
    
    createMoveButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr("Move folder..."));
      button.addListener("execute", ()=> this._moveFolderDialog() );
      this.permissions.move_folder.bind("state", button, "enabled");
      this.getPermission("folder.move").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      return button;
    },
  
    createEditButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr("Edit folder data"));
      button.addListener("execute", () => this._editFolder() );
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
      button.addListener("execute", ()=>this._changePublicState());
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
    //  TREE ACTIONS
    //-------------------------------------------------------------------------
  
    /**
     * Applies the `treeAction` property.
     * @param value {qcl.ui.treevirtual.TreeAction|null}
     * @param old {qcl.ui.treevirtual.TreeAction|null}
     */
    _applyTreeAction: function (value, old) {
      let action = value.getAction();
      switch (action){
        case "move":
          this._moveFolderDialog(
            value.getModel().getItem(0),
            value.getTargetModel()
          );
          break;
        default:
          this.warn(`Action ${action} not implemented.`);
      }
    },
    
    //-------------------------------------------------------------------------
    //  SERVER ACTIONS
    //-------------------------------------------------------------------------
  
    /**
     * Method to indicate that server action is happening
     * @param waiting
     * @private
     */
    _isWaiting : function(waiting){
      this.setEnabled(waiting);
      if( waiting ){
        // create a timer to re-enable even if an error occurred,
        // so that we don't get stuck in disabled mode.
        this.__timer = qx.event.Timer.once(()=>{
          this.setEnabled(true);
        },this,5000)
      } else {
        if( this.__timer ){
          this.__timer.stop();
          this.__timer = null;
        }
      }
    },
    
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
     * Shows dialog to confim a folder move.
     * If no model is passed, use the model of the currently selected node.
     * If no target model is passed, open a window with a tree from which
     * a node can be selected.
     * @param model {Object}
     * @param targetModel {Object}
     * @private
     */
    _moveFolderDialog : function (model=null,targetModel=null)
    {
      if( ! model ){
        model = this.getSelectedNode();
      }
      if( ! targetModel ){
        let app = this.getApplication();
        let win = app.getWidgetById("app/windows/folders");
        qx.core.Assert.assertInstance(win, qx.ui.window.Window);
        win.addListenerOnce("nodeSelected", e => {
          let targetModel = e.getData();
          if (!targetModel) {
            dialog.Dialog.alert(this.tr("No folder selected."));
          }
          this._moveFolderDialog(model,targetModel);
        });
        win.show();
        return;
      }
      console.warn([model,targetModel]);
      let message = this.tr(
        "Do your really want to move folder '%1' to '%2'?",
        model.label, targetModel.label
      );
      dialog.Dialog.confirm(message).promise().then(result => {
        if (result === true) {
          this._isWaiting(true);
          this.rpc
          .move(this.getDatasource(), model.data.id, targetModel.data.id)
          .then(() => this._isWaiting(false))
        }
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
