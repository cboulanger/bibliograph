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
/*global qx qcl dialog bibliograph virtualdata*/


qx.Class.define("bibliograph.ui.main.TableView",
{
  extend: qcl.ui.table.TableView,
  include: [qcl.access.MPermissions],
  
  construct: function () {
    this.base(arguments);
    this.createNewReferenceWindow();
    this.addListenerOnce("permissionsReady",()=>{
      this.createMenuEntries();
    });

    let bus = qx.event.message.Bus.getInstance();
    bus.subscribe("folder.reload", this._on_reloadFolder, this);
    bus.subscribe("reference.changeData", this._on_changeReferenceData, this);
    bus.subscribe("reference.removeRows", this._on_removeRows, this);
    bus.subscribe(bibliograph.AccessManager.messages.LOGOUT, ()=> this.clearTable());
    
    // create reference type list
    this.addListener("tableReady",e =>{
      let data = e.getData();
      if (data.addItems && data.addItems.length) {
        this.setAddItems(qx.data.marshal.Json.createModel(data.addItems));
      }
    });
    
    this.setDebugDragSession(true);
    this.setEnableDragDrop(true);
  },

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties:
  {
    /**
     * The model of the list of items that can be added
     */
    addItems:
    {
      check: "qx.data.Array",
      nullable: true,
      event: "changeAddItems"
    }
  },

  members:
  {
  
    /**
     * The rpc proxy
     */
    rpc : rpc.Reference,
  
    /*
    ---------------------------------------------------------------------------
       PERMISSIONS
    ---------------------------------------------------------------------------
    */
    
    permissions : {
      add_reference : {
        depends: "reference.add",
      },
      add_reference_to_folder : {
        depends: "reference.add",
        updateEvent: [
          "changeAddItems",
          "app/treeview:changeSelectedNode"
        ],
        condition : [
          self => self.getAddItems() !== null,
          treeview => {
            if ( ! treeview.getTree() ) return false;
            let selection = treeview.getTree().getSelectedNodes();
            return (selection.length && ['folder','top'].includes(selection[0].data.type) )
          }
        ]
      },
      remove_reference : {
        aliasOf : "reference.remove"
      },
      remove_selected_references : {
        depends: "reference.remove",
        granted : true,
        updateEvent : "changeSelectedIds",
        condition : self => self.getSelectedIds().length > 0
      },
      move_reference : {
        aliasOf : "reference.move"
      },
      move_selected_references :{
        depends: "reference.move",
        granted : true,
        updateEvent : "changeSelectedIds",
        condition : self => self.getSelectedIds().length > 0
      },
      edit_reference :{
        depends: "reference.edit",
        granted : true,
        updateEvent : "changeSelectedIds",
        condition : self => self.getSelectedIds().length > 0
      },
      batch_edit_reference :{
        aliasOf: "reference.batchedit",
      },
      export_references:{
        aliasOf : "reference.export"
      },
      export_selected_references :{
        depends: "reference.export",
        granted : false,
        updateEvent : "changeSelectedIds",
        condition : self => self.getSelectedIds().length > 0
      },
      export_folder :{
        depends: "reference.export",
        granted : false,
        updateEvent: "app/treeview:changeSelectedNode",
        condition: treeview => treeview.getSelectedNode() !== null
      },
    },
  

    /*
    ---------------------------------------------------------------------------
      CREATE UI
    ---------------------------------------------------------------------------
    */

    addMenuBarButton : function(button){
      this.menuBar.addBefore(button,this._statusLabel );
    },

    createMenuEntries: function(){
      let app = qx.core.Init.getApplication();

      // "Add Reference" menubar button
      let addButton = new qx.ui.menubar.Button();
      addButton.set({
        width: 16,
        height: 16,
        icon: "bibliograph/icon/button-plus.png",
        enabled : false
      });
      this.addMenuBarButton(addButton);
      this.bindVisibility(this.permissions.add_reference, addButton);
      this.bindEnabled(this.permissions.add_reference_to_folder, addButton);
      addButton.addListener("execute", ()=>{
        app.getWidgetById("app/windows/new-reference").open();
      });

      // Remove button
      let removeButton = new qx.ui.menubar.Button();
      removeButton.set({
        width: 16,
        height: 16,
        enabled : false,
        icon : "bibliograph/icon/button-minus.png"
      });
      removeButton.addListener("click", this.removeSelectedReferences, this);
      this.bindVisibility(this.permissions.remove_reference, removeButton);
      this.bindEnabled(this.permissions.remove_selected_references, removeButton);
      this.addMenuBarButton(removeButton);

      // Reload Button
      let reloadButton = new qx.ui.menubar.Button(null, "bibliograph/icon/button-reload.png", null, null);
      this.addMenuBarButton(reloadButton);
      reloadButton.addListener("execute", ()=> this.reload());

      // Options
      let optionsButton = new qx.ui.menubar.Button();
      optionsButton.set({
        width:16,
        height:16,
        icon: "bibliograph/icon/button-settings-up.png"
      });
      this.addMenuBarButton(optionsButton);
      let optionsMenu = new qx.ui.menu.Menu();
      optionsMenu.setPosition("top-left");
      optionsButton.setMenu(optionsMenu);

      // Move references
      let moveButton = new qx.ui.menu.Button(this.tr('Move reference(s)...'));
      optionsMenu.add(moveButton);
      moveButton.addListener("execute", ()=>this.moveSelectedReferences());
      this.bindEnabled(this.permissions.move_selected_references, moveButton);
      this.bindVisibility(this.permissions.move_reference, moveButton);

      // Copy references
      let copyButton = new qx.ui.menu.Button(this.tr('Copy reference(s)...'));
      optionsMenu.add(copyButton);
      copyButton.addListener("execute", ()=>this.copySelectedReferences());
      this.bindEnabled(this.permissions.move_selected_references, copyButton);
      this.bindVisibility(this.permissions.move_reference, copyButton);

      // Export menu
      let exportButton = new qx.ui.menu.Button(this.tr('Export references'));
      optionsMenu.add(exportButton);
      this.bindVisibility(this.permissions.export_references, copyButton);

      let exportMenu = new qx.ui.menu.Menu();
      exportButton.setMenu(exportMenu);

      // Export selected references
      let menuButton4 = new qx.ui.menu.Button(this.tr('Export selected references'));
      exportMenu.add(menuButton4);
      menuButton4.addListener("execute", () => this.exportSelected());
      this.bindEnabled(this.permissions.export_selected_references, menuButton4);

      // Export folder
      let menuButton5 = new qx.ui.menu.Button(this.tr('Export folder'));
      exportMenu.add(menuButton5);
      menuButton5.addListener("execute", () => this.exportFolder() );
      this.bindEnabled(this.permissions.export_folder, menuButton5);

      // Edit menu
      let editButton = new qx.ui.menu.Button();
      editButton.setLabel(this.tr('Edit references'));
      optionsMenu.add(editButton);
      this.bindVisibility(this.permissions.edit_reference, editButton);
      let editMenu = new qx.ui.menu.Menu();
      editButton.setMenu(editMenu);

      // Find/Replace Button
      let findReplBtn = new qx.ui.menu.Button();
      findReplBtn.setLabel(this.tr('Find/Replace'));
      editMenu.add(findReplBtn);
      findReplBtn.addListener("execute", () => this.findReplace());
      this.bindVisibility(this.permissions.batch_edit_reference,findReplBtn);

      // Empty folder Button
      let emptyFldContBtn = new qx.ui.menu.Button();
      emptyFldContBtn.setLabel(this.tr('Make folder empty'));
      editMenu.add(emptyFldContBtn);
      emptyFldContBtn.addListener("execute", () => this.emptyFolder());
      this.bindVisibility(this.permissions.batch_edit_reference,emptyFldContBtn);
    },

    /**
     * Window to create new references
     * @returns {qx.ui.window.Window}
     */
    createNewReferenceWindow : function(){
      let app = this.getApplication();
      let win = new qx.ui.window.Window(this.tr("Create new reference type"));
      win.setLayout(new qx.ui.layout.VBox(5));
      win.set({
        height: 300, width: 200,
        showMinimize: false, showMaximize: false,
        modal: true
      });

      // blocker
      win.addListener("appear", ()=>{
        win.center();
        app.getBlocker().blockContent(win.getZIndex() - 1);
      });
      win.addListener("disappear", ()=>app.getBlocker().unblock());

      // List widget, will be populated later
      let list = new qx.ui.list.List();
      list.set({
        iconPath: "icon",
        labelPath: "label"
      });
      win.add(list, {flex: 1});
      this.bind("addItems",list, "model");

      // OK button
      let okButton = new qx.ui.form.Button(this.tr("Create"));
      okButton.addListener("execute", function () {
        let type = list.getSelection().getItem(0).getValue();
        qx.lang.Function.delay(function () {
          win.close();
          app.setItemView("referenceEditor-main");
          this.createReference(type);
        }, 100, this);
      }, this);
      win.add(okButton);

      // Cancel button
      let cancelButton = new qx.ui.form.Button(this.tr("Cancel"));
      cancelButton.addListener("execute", () => win.close());
      win.add(cancelButton);

      app.getRoot().add(win);
      win.setWidgetId("app/windows/new-reference");
    },


    /*
    ---------------------------------------------------------------------------
       EVENT HANDLERS
    ---------------------------------------------------------------------------
    */
    

    /**
     * Called when a menu item in the "Add item" menu is clicked
     * @param e {qx.event.type.Event}
     */
    _on_addItemMenu_execute: function (e) {
      qx.core.Init.getApplication().setItemView("referenceEditor-main");
      this.createReference(e.getTarget().getUserData("type"));
    },
    
    /**
     * Called when the server sends the "reloadFolder" message
     * @param e {qx.event.type.Data}
     */
    _on_reloadFolder: function (e) {
      let data = e.getData();
      if (data.datasource === this.getDatasource() && data.folderId === this.getFolderId()) {
        this.reload();
      }
    },
    
    /**
     * Called when the server sends the "reference.changeData" message
     * @param e {qx.event.type.Data}
     */
    _on_changeReferenceData: function (e) {
      let data = e.getData();
      let table = this.getTable();
      if (!table) return;
      let tableModel = table.getTableModel();
      let column = tableModel.getColumnIndexById(data.name);
      if (column === undefined) return;
      let row = tableModel.getRowById(data.referenceId);
      if (row === undefined) return;
      tableModel.setValue(column, row, data.value.replace(/\n/, "; "));
    },
    
    /**
     * Called when the server sends the "removeRows" message
     * @param e {qx.event.type.Data}
     */
    _on_removeRows: function (e) {
      let data = e.getData();
      
      // is this message really for me?
      let notForMe =
        (data.datasource !== this.getDatasource())
        || (data.folderId && data.folderId !== this.getFolderId())
        || (data.query && data.query !== this.getQuery());
      if( notForMe ) {
        this.debug("Ignoring message...");
      }
      
      let table = this.getTable();
      let tableModel = table.getTableModel();
      if (!qx.lang.Type.isArray(data.ids)) {
        this.error("Invalid id data.")
      }
      this.resetSelection();
      
      // get row indexes from ids
      let row, rows = [];
      data.ids.forEach(function (id) {
        row = tableModel.getRowById(id);
        if (row !== undefined) rows.push(row); // FIXME this is a bug
      });
      
      // sort row indexes descending and remove them
      rows.sort(function (a, b) {
        return b - a
      });
      
      if (rows.length) {
        rows.forEach(function (row) {
          tableModel.removeRow(row);
        });
      }  else {
        this.reload();
      }
      
      // rebuild the row-id index because now rows are missing
      tableModel.rebuildIndex();
      
    },
  
    /*
    ---------------------------------------------------------------------------
       API
    ---------------------------------------------------------------------------
    */

    /**
     * Creates a new reference
     * @param reftype
     */
    createReference: async function (reftype) {
      let app = this.getApplication();
      let folderId = this.getFolderId();
      if (!folderId) {
        dialog.Dialog.error(this.tr("You cannot create an item outside a folder"));
        return;
      }
      app.showPopup(this.tr("Creating reference..."));
      await this.rpc.create( this.getDatasource(), this.getFolderId(), reftype );
      app.hidePopup();
    },
    
    /**
     * Removes the currently selected references from a folder
     * or moves it to trash if not in a folder
     */
    removeSelectedReferences: async function () {
      let app = this.getApplication();
      if( this.getFolderId()){
        let msg = this.tr("Do your really want to remove the selected references?");
        if( ! await dialog.Dialog.confirm(msg).promise() ) return;
        app.showPopup(this.tr("Removing references..."));
        await this.rpc.remove( this.getDatasource(), this.getFolderId(), this.getSelectedIds().join(",") );
        app.hidePopup();
      } else {
        dialog.Dialog.error("Funktion noch nicht implementiert...");
      }
    },
  
    /**
     * Shows a window with the folder tree from which to choose a target folder
     * @return {Promise<any>}
     * @private
     */
    _showFolderDialog : function(){
      return new Promise((resolve)=>{
        let app = this.getApplication();
        let win = app.getWidgetById("app/windows/folders");
        win.addListenerOnce("nodeSelected", (e) => resolve(e.getData()) );
        win.show();
      });
    },
    
    /**
     * Move selected references from one folder to the other
     * @param node {Object|null}
     *    Tree node model data or null to open a dialog
     * @return {Promise}
     */
    moveSelectedReferences: async function (node=null) {
      let app = this.getApplication();
      if( ! node){
        node = await this._showFolderDialog();
        if( ! node ) return;
      }
      let message = this.tr(
        "Do your really want to move the selected references to '%1'?",
        [node.label]
      );
      await dialog.Dialog.confirm(message).promise();
      let targetFolderId = parseInt(node.data.id);
      app.showPopup(this.tr("Moving references..."));
      this.rpc.move(this.getDatasource(), this.getFolderId(), targetFolderId, this.getSelectedIds().join(","));
      app.hidePopup();
    },
    
    /**
     * Copy the selected references to a folder
     * @param node {Object|null}
     *    Tree node model data or null to open a dialog
     * @return {Promise}
     */
    copySelectedReferences: async function ( node=null ) {
      let app = this.getApplication();
      if( ! node){
        node = await this._showFolderDialog();
        if( ! node ) return;
      }
      let message = this.tr("Do your really want to copy the selected references to '%1'?", [node.label]);
      await dialog.Dialog.confirm(message).promise();
      let targetFolderId = parseInt(node.data.id);
      app.showPopup(this.tr("Copying references..."));
      this.rpc.copy(this.getDatasource(), targetFolderId, this.getSelectedIds().join(","));
      app.hidePopup();
    },
    
    /**
     * Exports the selected references via jsonrpc service
     */
    exportSelected: function () {
      return dialog.Dialog.error("Funktion noch nicht implementiert...");
      let datasource = this.getDatasource();
      let selectedIds = this.getSelectedIds();
      let app = this.getApplication();
      app.showPopup(this.tr("Processing request..."));
      app.getRpcClient("export").send(
      "exportReferencesDialog",
      [datasource, selectedIds],
      function () {
        app.hidePopup();
      }, this
      );
    },
    
    /**
     * Exports the whole folder or query
     */
    exportFolder: function () {
      return dialog.Dialog.error("Funktion noch nicht implementiert...");
      let app = this.getApplication();
      app.showPopup(this.tr("Processing request..."));
      app.getRpcClient("export").send(
      "exportReferencesDialog",
      [this.getDatasource(), this.getFolderId() || this.getQuery()],
      function () {
        app.hidePopup();
      }, this
      );
    },
    
    /**
     * Finds and replaces text in the database using a service
     */
    findReplace: function () {
      return dialog.Dialog.error("Funktion noch nicht implementiert...");
      let datasource = this.getDatasource();
      let folderId = this.getFolderId();
      let selectedIds = this.getSelectedIds();
      let app = this.getApplication();
      app.showPopup(this.tr("Processing request..."));
      app.getRpcClient("reference").send("findReplaceDialog", [datasource, folderId, selectedIds], function () {
        app.hidePopup();
      }, this);
    },
    
    /**
     * Empties the current folder
     */
    emptyFolder: function () {
      return dialog.Dialog.error("Funktion noch nicht implementiert...");
      let datasource = this.getDatasource();
      let folderId = this.getFolderId();
      let app = this.getApplication();
      let msg = this.tr("Do you really want to make the folder empty, moving all references to the trash that are not in other folders?");
      dialog.Dialog.confirm(msg, function (yes) {
        if (!yes) return;
        app.showPopup(this.tr("Emptying the folder ..."));
        app.getRpcClient("reference").send("removeAllFromFolder", [datasource, folderId], function () {
          app.hidePopup();
        }, this);
      }, this);
    }
  }
});
