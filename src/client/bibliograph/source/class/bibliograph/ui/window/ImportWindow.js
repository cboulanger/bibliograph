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
 * Imports references from text files
 */
qx.Class.define("bibliograph.ui.window.ImportWindow",
{
  extend : qx.ui.window.Window,
  include : [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */
  
  construct : function() {
    this.base(arguments);
    this.createPopup();
    this.createUi();
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, this.close, this);
    qx.lang.Function.delay(() => {
      this.listView.addListenerOnce("tableReady", () => {
        let controller = this.listView.getController();
        let enableButtons = () => {
          this.importButton.setEnabled(true);
          this.listView.setEnabled(true);
          this.hidePopup();
        };
        controller.addListener("blockLoaded", enableButtons, this);
        controller.addListener("statusMessage", e => {
          this.showPopup(e.getData());
          qx.lang.Function.delay(enableButtons, 1000, this);
        });
        qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, () => this.close());
      }, this);
    }, 100);
  },

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties : {
    filter :
    {
      check : "String",
      nullable : true,
      event : "changeFilter",
      apply : "_applyFilter"
    }
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
    uploadButton : null,
    listView : null,
    toolBar : null,
    importButton : null,


    /**
 * Apply filter property
 *
 * @param value
 * @param old
 */
    _applyFilter : function(value, old) {
      if (this.uploadButton) {
        this.uploadButton.setEnabled(value !== null && this.file.getFieldValue !== "");
      }
    },
  
    createUi : function() {
      this.setCaption(this.tr("Import from file"));
      this.setShowMinimize(false);
      this.setWidth(700);
      this.setHeight(500);
      
      qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, () => this.close());
      this.addListener("appear", () => this.center());
      
      let vbox1 = new qx.ui.layout.VBox(5, null, null);
      vbox1.setSpacing(5);
      this.setLayout(vbox1);
    
      // Toolbar
      let toolBar = new qx.ui.toolbar.ToolBar();
      this.toolBar = toolBar;
      toolBar.setSpacing(10);
      this.add(toolBar);
  
      // invisible upload form with visible button
      let form = this.createUploadForm();
      toolBar.add(form, { flex : 1 });
      
      // import filter
      let importFilterStore = new qcl.data.store.JsonRpcStore("import");
      qx.event.message.Bus.subscribe("bibliograph.setup.completed", () => {
        importFilterStore.setAutoLoadParams([true]);
        importFilterStore.setAutoLoadMethod("import-formats");
      });
      let importFilterSelectBox = new qx.ui.form.SelectBox();
      importFilterSelectBox.setWidth(200);
      importFilterSelectBox.setMaxHeight(25);
      toolBar.add(importFilterSelectBox, { flex : 1 });
      this.importFilterSelectBox=importFilterSelectBox;
      let selectboxController1 = new qx.data.controller.List(null, importFilterSelectBox, "label");
      importFilterStore.bind("model", selectboxController1, "model");
      importFilterSelectBox.bind("selection", this, "filter", {
        converter : s => (s.length ? s[0].getModel().getValue() : null)
      });
      
      // upload button
      let uploadButton = new qx.ui.form.Button(this.tr("3. Upload file"), null, null);
      this.uploadButton = uploadButton;
      uploadButton.setEnabled(false);
      uploadButton.setLabel(this.tr("3. Upload file"));
      toolBar.add(uploadButton, { flex : 1 });
      uploadButton.addListener("execute", () => {
        this.importButton.setEnabled(false);
        this.uploadButton.setEnabled(false);
        this.form.send();
      });
      
      // Label for details on the import filter
      let helpText = new qx.ui.basic.Label(this.tr("Select the file with data to import ..."));
      helpText.setHeight(30);
      this.helpText = helpText;
      this.add(helpText);
      importFilterSelectBox.addListener("changeSelection", e => {
        let s = e.getData();
        let description = s.length && s[0].getModel().getDescription();
        if (description) {
         helpText.setValue(description);
        }
      });
      
      // stack - don't remember what that was for
      let stack1 = new qx.ui.container.Stack();
      this.add(stack1, { flex : 1 });
    
      // Listview
      let tableview = new qcl.ui.table.TableView();
      this.listView = tableview;
      tableview.set({
        decorator: "main",
        /* @todo unhardcode this */
        serviceName:  "import",
        datasource:   "bibliograph_import",
        modelType:    "reference"
      });
      tableview.headerBar.setVisibility("excluded");
      tableview.menuBar.setVisibility("excluded");
      stack1.add(tableview);
    
      // Footer
      let hbox1 = new qx.ui.layout.HBox(5, null, null);
      let composite1 = new qx.ui.container.Composite();
      composite1.setLayout(hbox1);
      this.add(composite1);
      hbox1.setSpacing(5);
    
      // Status label
      let statusTextLabel = new qx.ui.basic.Label(null);
      this.listView._statusLabel = statusTextLabel; // todo this is a hack
      composite1.add(statusTextLabel);
      this.listView.bind("store.model.statusText", statusTextLabel, "value");
    
      let spacer1 = new qx.ui.core.Spacer(null, null);
      composite1.add(spacer1, { flex : 10 });
    
      // Select all button
      let selectAllButton = new qx.ui.form.Button();
      this.selectAllButton = selectAllButton;
      selectAllButton.setLabel(this.tr("Import all records"));
      composite1.add(selectAllButton);
      selectAllButton.addListener("execute", () => this.importReferences(true));
    
      // Import selected button
      let importButton = new qx.ui.form.Button();
      this.importButton = importButton;
      importButton.setEnabled(false);
      importButton.setLabel(this.tr("Import selected records"));
      composite1.add(importButton);
      importButton.bind("enabled", selectAllButton, "enabled");
      importButton.addListener("execute", () => this.importReferences(false));
    
      // Close button
      let button1 = new qx.ui.form.Button();
      button1.setLabel(this.tr("Close"));
      composite1.add(button1);
      button1.addListener("execute", () => this.close());
    },
    
    /**
     * Create upload widget
     * @return {uploadwidget.UploadButton}
     */
    createUploadForm : function() {
      let uploadwidget = window.uploadwidget;
      if (!uploadwidget) {
        let msg = "UploadWidget contrib not installed or not compiled.";
        this.getApplication().error(msg);
        throw new Error(msg);
      }

      let app = this.getApplication();
      let token = app.getAccessManager().getToken();
      let url = app.getServerUrl() + "upload?access-token=" + token;

      // form
      let form = new uploadwidget.UploadForm("uploadForm", url);
      this.form = form;
      form.setLayout(new qx.ui.layout.HBox());

      // upload button @todo grow button
      let uploadField = new uploadwidget.UploadField("file", this.tr("1. Choose file"));
      this.file = uploadField;
      form.add(uploadField);
      uploadField.getChildControl("textfield").set({ visibility : "excluded", width: 0 });
      uploadField._getLayout().setColumnFlex(0, 0);
      uploadField._getLayout().setColumnFlex(1, 1);
      
      // callback when file is selected
      uploadField.addListener("changeFileName", e => {
        let filename = e.getData() || "";
        this.uploadButton.setEnabled(filename !== "" && this.getFilter() !== null);
        if (filename) {
          let msg = this.tr("Choose import format of '%1'", filename.replace(/C:\\fakepath\\/, ""));
          this.helpText.setValue(msg);
        } else {
          this.helpText.setValue(this.tr("Please select the file with data to import..."));
        }
        form.setParameter("auth_token", this.getApplication().getAccessManager().getToken());
      });

      // callback when file is sent to server
      form.addListener("sending", function(e) {
        this.showPopup(this.tr("Uploading file..."));
      }, this);

      // callback when upload is completed
      form.addListener("completed", function(e) {
        this.hidePopup();
        uploadField.setFileName("");
        let response = form.getIframeHtmlContent().trim();
        // response is empty for success or an error message
        if (response.length) {
          this.getApplication().error(response.substr(0, 100));
          return;
        }
        this.processUpload();
      }, this);
      return form;
    },
    
    /**
     * Processing the uploaded file on the server
     */
    processUpload : async function() {
      this.showPopup(this.tr("Processing references..."));
      let data = await rpc.Import.parseUpload(this.getFilter());
      this.hidePopup();
      this.importFilterSelectBox.resetSelection();
      if (data && data.datasource && data.folderId) {
        this.listView.setDatasource(data.datasource);
        this.listView.setFolderId(data.folderId);
        this.listView.load();
      } else {
        this.warn(data);
      }
    },
    
    /**
     * Import the selected or all references into the datasource
     *
     * @param importAll
     */
    importReferences : async function(importAll) {
      let app = this.getApplication();
      if (importAll) {
        let table = this.listView.getTable();
        table.getSelectionModel().addSelectionInterval(0, table.getTableModel().getRowCount());
      }
      let ids = this.listView.getSelectedIds();
      if (!ids.length) {
        await this.getApplication().alert(this.tr("You have to select one or more reference to import."));
        return;
      }

      // target folder
      let targetFolderId = app.getFolderId();
      if (!targetFolderId) {
        await this.getApplication().alert(this.tr("Please select a folder first."));
        return;
      }
      let treeView = qx.core.Id.getQxObject("folder-tree-panel/tree-view");
      let nodeId = treeView.getController().getClientNodeId(targetFolderId);
      let node = treeView.getTree().getDataModel().getData()[nodeId];
      if (!node) {
        await this.getApplication().alert(this.tr("Cannot determine selected folder. Please reload the folders."));
        return;
      }
      if (node.data.type !== "folder") {
        await this.getApplication().alert(this.tr("Invalid target folder. You can only import into normal folders."));
        return;
      }

      // send to server
      let targetDatasource = app.getDatasource();
      this.showPopup(this.tr("Importing references..."));
      await rpc.Import.import(ids.join(","), targetDatasource, targetFolderId);
      this.importButton.setEnabled(true);
      this.hidePopup();
    }
  }
});
