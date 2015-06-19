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
qx.Class.define("bibliograph.ui.window.ImportWindow",
{
  extend : qx.ui.window.Window,
  include : [qcl.ui.MLoadingPopup],

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  /**
   * @todo rewrite the cache stuff!
   */
  construct : function()
  {
    this.base(arguments);
    this.createPopup();
    qx.lang.Function.delay(function(){
      this.listView.addListenerOnce("tableReady", function() {
        var controller = this.listView.getController();
        function enableButtons (){
          this.importButton.setEnabled(true);
          this.listView.setEnabled(true);
          this.hidePopup();
        }
        controller.addListener("blockLoaded", enableButtons, this);
        controller.addListener("statusMessage", function(e){
          this.showPopup(e.getData());
          qx.lang.Function.delay( enableButtons, 1000, this);
        }, this);
      }, this);
    },100,this);
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
     */
    _applyFilter : function(value, old) {
      if (this.uploadButton)
      {
        this.uploadButton.setEnabled(value !== null && this.file.getFieldValue != '');
      }

    },
    
    /**
     * Create upload widget
     */
    createUploadWidget : function()
    {
      var url = this.getApplication().getRpcManager().getServerUrl();

      // form
      var form = new uploadwidget.UploadForm('uploadFrm', url);
      this.form = form;
      form.setParameter('application', 'bibliograph');
      form.setParameter('replace', true);
      form.setLayout(new qx.ui.layout.HBox());

      // upload button
      var uploadField = new uploadwidget.UploadField('uploadfile', this.tr('1. Choose file'));
      this.file = uploadField;
      form.add(uploadField, { flex : 1 });

      // callback when file is selected
      uploadField.addListener('changeFileName', function(e) {
        form.setParameter('sessionId', this.getApplication().getSessionManager().getSessionId());
        this.uploadButton.setEnabled(e.getData() != '' && this.getFilter() !== null);
      }, this);

      // callback when file is sent to server
      form.addListener('sending', function(e) {
        this.showPopup(this.tr('Uploading file...'));
      }, this);

      // callback when upload is completed
      form.addListener('completed', function(e) {
        this.hidePopup();
        uploadField.setFileName('');
        var response = form.getIframeHtmlContent();
        if (response.search(/qcl_file/) == -1) {
          dialog.Dialog.alert(response);
        } else {
          this.processUpload(response.match(/qcl_file\=\"([^']+)\"/)[1]);
        }
      }, this);
      return form;
    },

    /**
     * converter for import filter selection
     * @param s {Object}
     * @returns {int|null}
     * @private
     */
    _convertImportFilterSelection : function(s) {
      return (s.length ? s[0].getModel().getValue() : null);
    },

    /**
     * called when the upload button is clicked
     * @private
     */
    _on_uploadButton_execute : function()
    {
      this.importButton.setEnabled(false);
      this.uploadButton.setEnabled(false);
      this.form.send();
    },

    /**
     * Processing the uploaded file on the server
     * @param file {String}
     */
    processUpload : function(file)
    {
      this.showPopup(this.tr("Processing references..."));
      var app = this.getApplication();
      app.getRpcManager().execute(
          "bibliograph.import", "processUpload",
          [file, this.getFilter()],
          this.uploadFinished, this
      );
    },

    /**
     * Called when the upload is finished
     * @param data {Object}
     */
    uploadFinished : function(data)
    {
      this.listView.setFolderId(data.folderId);
      this.showPopup("Loading imported references ...");
      this.listView.load();
    },

    /**
     * Import the selected references into the datasource
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
        dialog.Dialog.alert(this.tr("Cannot determine selected folder. Please reload the folders."));
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
      var sourceDatasource = "bibliograph_import";
      var targetDatasource = app.getDatasource();
      this.showPopup(this.tr("Importing references..."));
      this.getApplication().getRpcManager().execute("bibliograph.import", "importReferences", [sourceDatasource, ids, targetDatasource, targetFolderId], function()
      {
        this.importButton.setEnabled(true);
        this.hidePopup();
      }, this);
    }
  }
});