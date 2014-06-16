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
    _applyFilter : function(value, old) {
      if (this.uploadButton)this.uploadButton.setEnabled(value !== null && this.file.getFieldValue != '');

    },
    
    /**
     * Create upload widget
     */
    createUploadWidget : function()
    {
      var url = this.getApplication().getRpcManager().getServerUrl();

      /*
       * form
       */
      var form = new uploadwidget.UploadForm('uploadFrm', url);
      this.form = form;
      form.setParameter('application', 'bibliograph');
      form.setParameter('sessionId', this.getApplication().getSessionManager().getSessionId());
      form.setParameter('replace', true);
      form.setLayout(new qx.ui.layout.HBox());

      /*
       * upload button
       */
      var file = new uploadwidget.UploadField('uploadfile', this.tr('1. Choose file'));
      this.file = file;
      form.add(file, {
        flex : 1
      });

      /*
       * callback when file is selected
       */
      file.addListener('changeFieldValue', function(e) {
        this.uploadButton.setEnabled(e.getData() != '' && this.getFilter() !== null);
      }, this);

      /*
       * callback when file is sent to server
       */
      form.addListener('sending', function(e) {
        this.showPopup(this.tr('Uploading file...'));
      }, this);

      /*
       * callback when upload is completed
       */
      form.addListener('completed', function(e)
      {
        this.hidePopup();
        file.setFieldValue('');
        var response = form.getIframeHtmlContent();
        if (response.search(/qcl_file/) == -1) {
          dialog.Dialog.alert(response);
        } else {
          this.processUpload(response.match(/qcl_file\=\"([^']+)\"/)[1]);
        }
      }, this);
      return form;
    },
    
    
    _convertImportFilterSelection : function(s) {
      return (s.length ? s[0].getModel().getValue() : null);
    },
    
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
      this.importButton.setEnabled(false);
      var app = this.getApplication();
      app.getRpcManager().execute("bibliograph.import", "processUpload", [file, this.getFilter()], function(data)
      {
        this.hidePopup();
        this.importButton.setEnabled(true);
        this.uploadButton.setEnabled(true);
        var sessionId = app.getSessionManager().getSessionId();
        this.listView.setFolderId(data.folderId);
        this.listView.load();
      }, this);
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
        dialog.Dialog.alert(this.tr("Cannot determine selected folder."));
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