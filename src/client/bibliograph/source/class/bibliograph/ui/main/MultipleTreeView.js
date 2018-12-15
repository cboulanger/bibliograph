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
 * @asset(icon/16/emblems/emblem-important.png)
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
    
    // context menu
    this.setupTreeCtxMenu();
  
    // drag & drop
    this.addListener("changeTree",e => {
      /** @var {qcl.ui.treevirtual.DragDropTree}  */
      let tree = e.getData();
      if( ! tree ) return;
      tree.setExcludeDragTypes( new qx.data.Array('trash','top'));
      tree.setAllowDropTypes([
        ['folder','*'],
        ['search','*'],
        [qcl.ui.table.TableView.types.ROWDATA,'folder']
      ]);
    });
    this.addListener(qcl.access.MPermissions.events.permissionsReady, e => {
      this.bindState(this.permissions.move_any_folder,this,"enableDragDrop");
      this.setDebugDragSession(qx.core.Environment.get("qx.debug"));
    });
    
    this.addListener("loading", () => this._isWaiting(true) );
    this.addListener("loaded", () => this._isWaiting(false) );
  
    // @todo get constants from generated file
    bus.subscribe( "folder.node.update", this._updateNode, this );
    bus.subscribe( "folder.node.add", this._addNode, this);
    bus.subscribe( "folder.node.delete", this._deleteNode, this );
    bus.subscribe( "folder.node.move", this._moveNode, this);
    bus.subscribe( "folder.node.reorder", this._reorderNodeChildren, this);
    bus.subscribe( "folder.node.select", this._selectNode, this);
  
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
      add_any_folder : {
        aliasOf : "folder.add"
      },
      add_child_folder : {
        depends: "folder.add",
        updateEvent : "changeSelectedNode",
        condition : tree =>
          tree.getSelectedNode() !== null &&
          tree.getSelectedNode().data.type !== "virtual"
      },
      save_search : {
        depends: "folder.add",
        updateEvent : "app:changeQuery",
        condition : app => !! app.getQuery()
      },
      add_top_folder : {
        depends: "folder.add"
      },
      remove_folder : {
        depends: "folder.remove",
        updateEvent : "changeSelectedNode",
        condition : tree =>
          tree.getSelectedNode() !== null &&
          tree.getSelectedNode().data.type !== "virtual" &&
          tree.getSelectedNode().data.type !== "trash"
      },
      edit_folder : {
        depends : "folder.edit",
        updateEvent : "changeSelectedNode",
        condition : tree =>
          tree.getSelectedNode() !== null &&
          tree.getSelectedNode().data.type !== "virtual"
      },
      move_any_folder: {
        aliasOf : "folder.move"
      },
      move_selected_folder : {
        depends : "folder.move",
        updateEvent : "changeSelectedNode",
        condition : tree =>
          tree.getSelectedNode() !== null &&
          tree.getSelectedNode().data.type !== "virtual" &&
          tree.getSelectedNode().data.type !== "trash"
      },
      copy_any_folder: {
        aliasOf : "folder.copy" // TODO create new permission folder.copy
      },
      copy_selected_folder : {
        depends : "folder.copy", // TODO create new permission folder.copy
        updateEvent : "changeSelectedNode",
        condition : tree =>
          tree.getSelectedNode() !== null &&
          tree.getSelectedNode().data.type !== "virtual"
      },
      paste_folder : {
        depends : "folder.copy",
        updateEvent : ["changeSelectedNode","app/clipboard:changeData"],
        condition : [
          tree =>
            tree.getSelectedNode() !== null &&
            tree.getSelectedNode().data.type !== "virtual" &&
            qx.core.Init.getApplication,
          clipboard => !! clipboard.getData( bibliograph.Application.mime_types.folder )
        ]
      },
      change_position : {
        depends : "folder.move",
        updateEvent : "changeSelectedNode",
        condition : tree =>
          tree.getSelectedNode() !== null &&
          tree.getSelectedNode().data.type !== "virtual"
      },
      empty_trash : {
        depends : "trash.empty",
        updateEvent : "changeSelectedNode",
        condition : tree =>
          tree.getSelectedNode() &&
          tree.getSelectedNode().data.type === "trash"
      },
    },
    
    //-------------------------------------------------------------------------
    //  USER INTERFACE
    //-------------------------------------------------------------------------
    
    createAddFolderButton : function(menubar =false)
    {
      let button = menubar
        ? new qx.ui.menubar.Button(null, "bibliograph/icon/button-plus.png")
        : new qx.ui.menu.Button(this.tr("Add folder"), "bibliograph/icon/button-plus.png");
      button.addListener("click", ()=> this._addFolderDialog());
      this.bindVisibility(this.permissions.add_any_folder,button);
      this.bindEnabled(this.permissions.add_child_folder, button);
      return button;
    },
  
    createAddTopFolderButton : function(menubar=false)
    {
      let button = menubar
        ? new qx.ui.menubar.Button(null, "bibliograph/icon/button-plus.png")
        : new qx.ui.menu.Button(this.tr("Add top level folder"), "bibliograph/icon/button-plus.png");
      button.addListener("click", ()=> this._addTopFolderDialog());
      this.bindVisibility( this.permissions.add_top_folder, button);
      return button;
    },
  
    createSaveSearchFolderButton : function(menubar=false)
    {
      let button = menubar
        ? new qx.ui.menubar.Button(null, "bibliograph/icon/16/search.png")
        : new qx.ui.menu.Button(this.tr("Save current search query as top folder"),"bibliograph/icon/16/search.png");
      button.addListener("click", ()=> this._saveSearchQuery());
      this.bindVisibility( this.permissions.add_any_folder, button);
      this.bindEnabled(this.permissions.save_search,button);
      return button;
    },
  
    createRemoveButton : function(menubar=false)
    {
      let button = menubar
        ? new qx.ui.menubar.Button(null, "bibliograph/icon/button-minus.png")
        : new qx.ui.menu.Button(this.tr("Remove folder"), "bibliograph/icon/button-minus.png");
      button.addListener("click", ()=> this._removeFolderDialog());
      this.getPermission("folder.remove").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      this.permissions.add_child_folder.bind("state", button, "enabled");
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
      this.permissions.move_selected_folder.bind("state", button, "enabled");
      this.getPermission("folder.move").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      return button;
    },
  
    createCopyButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr("Copy folder to Clipboard..."));
      button.addListener("execute", ()=> this._copyFolderToClipboard() );
      this.bindVisibility(this.permissions.copy_any_folder, button);
      this.bindEnabled(this.permissions.copy_selected_folder, button)
      return button;
    },
  
    createPasteButton : function()
    {
      let button = new qx.ui.menu.Button(this.tr("Insert folder from Clipboard..."));
      button.addListener("execute", ()=> this._insertFolderFromClipboard() );
      this.bindVisibility(this.permissions.copy_any_folder, button);
      this.bindEnabled(this.permissions.paste_folder, button)
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
      button.addListener("execute", ()=>this._changePublicState());
      this.permissions.edit_folder.bind("state", button, "enabled");
      this.getPermission("folder.edit").bind("state", button, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
      return button;
    },
    
    createReportButton: function()
    {
      let button = new qx.ui.menu.Button(this.tr("Create report"));
      button.addListener("execute", ()=>this._createReport());
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
          contextMenu.add(this.createAddFolderButton());
          contextMenu.add(this.createAddTopFolderButton());
          contextMenu.add(this.createRemoveButton());
          contextMenu.add(this.createEditButton());
          contextMenu.add(this.createMoveButton());
          contextMenu.add(this.createCopyButton());
          contextMenu.add(this.createPasteButton());
          contextMenu.add(this.createVisibilityButton());
          contextMenu.add(this.createEmptyTrashButton());
          contextMenu.add(this.createReportButton());
          return true;
        });
      });
    },
  
    //-------------------------------------------------------------------------
    //  DRAG & DROP
    //-------------------------------------------------------------------------
    
    /**
     * Called when a successful drop has happened in the current treee.
     *
     * @param e {qx.event.type.Drag}
     * @private
     */
    _onDropImpl : function(e){
      let action = e.getCurrentAction();
      if( e.supportsType(qcl.ui.treevirtual.DragDropTree.types.TREEVIRTUAL)){
        let data = e.getData(qcl.ui.treevirtual.DragDropTree.types.TREEVIRTUAL);
        switch (action){
          case "move":
            this._moveFolderDialog( data[0], this.getTree().getDropModel() );
            break;
          default:
            this.warn(`Action ${action} not implemented.`);
        }
      }
      if( e.supportsType(qcl.ui.table.TableView.types.ROWDATA)){
        let rowData = e.getData(qcl.ui.table.TableView.types.ROWDATA);
        // @todo This should really be calculated differently (async from server)
        let id = this.getController().getClientNodeId(this.getNodeId());
        let sourceFolderModel = this.getTree().nodeGet(id);
        let targetFolderModel = this.getTree().getDropModel();
        switch (action){
          case "move":
            this._moveReferencesDialog(sourceFolderModel,targetFolderModel,rowData);
            break;
          case "copy":
            this._copyReferencesDialog(targetFolderModel,rowData);
            break;
          default:
            this.warn(`Action ${action} not implemented.`);
        }
      }
      return true;
    },
    
    //-------------------------------------------------------------------------
    //  SERVER ACTIONS
    //-------------------------------------------------------------------------
  
    /**
     * Method to indicate that server action is happening
     * @param waiting {Boolean}
     * @private
     */
    _isWaiting : function(waiting){
      this.setEnabled(waiting);
      this.getApplication().showPopup(this.tr("Loading folders. Please wait..."));
      if( waiting ){
        // create a timer to re-enable even if an error occurred,
        // so that we don't get stuck in disabled mode.
        this.__timer = qx.event.Timer.once(()=>{
          this.setEnabled(true);
          this.getApplication().hidePopup();
        },this,5000)
      } else {
        this.getApplication().hidePopup();
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
     * Triggers server dialog to add a top folder
     */
    _addTopFolderDialog: function ()
    {
      this.rpc.addDialog(this.getDatasource(), 0 ).then(()=>this.reload());
    },
  
    /**
     * Triggers server dialog to add a top folder
     */
    _saveSearchQuery: function ()
    {
      let app = this.getApplication();
      app.getRpcClient("folder").send("save-search", [this.getDatasource(), 0, app.getQuery()]);
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
          win.close();
          if (targetModel) {
            this._moveFolderDialog(model,targetModel);
          }
        });
        win.show();
        return;
      }
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
     * Shows dialog to confim moving references.
     * @param sourceModel {Object}
     * @param targetModel {Object}
     * @param rowData {Array}
     * @private
     */
    _moveReferencesDialog : function (sourceModel, targetModel, rowData)
    {
      qx.core.Assert.assertObject(sourceModel);
      qx.core.Assert.assertObject(targetModel);
      qx.core.Assert.assertArray(rowData);
      let ids = rowData.map(row => row.id );
      let message = this.tr( "Do your really want to move %1 references to '%2'?", ids.length, targetModel.label );
      dialog.Dialog.confirm(message).promise().then(result => {
        if (result === true) {
          this._isWaiting(true);
          rpc.Reference
          .move(this.getDatasource(), sourceModel.data.id, targetModel.data.id, ids.join(","))
          .then(() => this._isWaiting(false))
        }
      });
    },
  
    /**
     * Shows dialog to confim copying references.
     * @param targetModel {Object}
     * @param rowData {Array}
     * @private
     */
    _copyReferencesDialog : function (targetModel, rowData)
    {
      qx.core.Assert.assertObject(targetModel);
      qx.core.Assert.assertArray(rowData);
      let ids = rowData.map(row => row.id );
      let message = this.tr( "Do your really want to copy %1 references to '%2'?", ids.length, targetModel.label );
      dialog.Dialog.confirm(message).promise().then(result => {
        if (result === true) {
          this._isWaiting(true);
          rpc.Reference
          .copy(this.getDatasource(), targetModel.data.id, ids.join(","))
          .then(() => this._isWaiting(false))
        }
      });
    },
  
    /**
     * Copies the currently selected folder's model to the server clipboard
     * @private
     */
    _copyFolderToClipboard : function()
    {
      this._isWaiting(true);
      rpc.Clipboard.add(
        bibliograph.Application.mime_types.folder,
        JSON.stringify(this.getSelectedNode())
      ).then(message => {
        this.showMessage(message)
        this._isWaiting(false);
      });
    },
  
    /**
     * Inserts the folder and content from the clipboard as a subfolder of the currently selected folder
     * @return {Promise<void>}
     * @private
     */
    _insertFolderFromClipboard : async function()
    {
      let clipboard = this.getApplication().getClipboardManager();
      let clipboardData = clipboard.getData( bibliograph.Application.mime_types.folder);
      if( ! clipboardData){
        dialog.Dialog.warning("No folder data on clipboard!");
        return;
      }
      let folderdata = JSON.parse(clipboardData);
      let message;
      if( this.getDatasource() !== folderdata.data.datasource ){
        message = this.tr(
          "Do you want to insert the folder '%1' from datasource '%2'?",
          folderdata.label, folderdata.data.datasource
        );
      } else {
        message = this.tr( "Do you want to insert the folder '%1'?", folderdata.label);
      }
      let result = await dialog.Dialog.confirm(message).promise();
      if (result === true) {
        this._isWaiting(true);
        await rpc.Folder.copy(folderdata.data.datasource , folderdata.data.id, this.getDatasource(), this.getNodeId() );
        this._isWaiting(false);
      }
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
  
    _createReport: function () {
      let app = qx.core.Init.getApplication();
      let params = {};
      params.datasource = this.getDatasource();
      params.nodeId = this.getNodeId();
      params.auth_token = app.getAccessManager().getToken();
      let url = app.getServerUrl() + "/report/create&";
      url += qx.util.Uri.toParameter(params) + "&nocache=" + Math.random();
      window.open(url);
    }
  }
});
