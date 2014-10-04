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

/*global qx qcl backup*/

/**
 * Plugin Initializer Class
 * 
 */
qx.Class.define("backup.Plugin",
{
  extend : qx.core.Object,
  include : [qx.locale.MTranslation],
  type : "singleton",
  members : {
    init : function()
    {
      // vars
      var app         = this.getApplication();
      var systemMenu  = app.getWidgetById("bibliograph-menu-system");
      var permMgr     = app.getAccessManager().getPermissionManager();
      var rpcMgr      = app.getRpcManager();
      
      // add backup menu
      var backupMenuButton = new qx.ui.menu.Button();
      backupMenuButton.setLabel(this.tr('Backup'));
      permMgr.create("backup.create").bind("state", backupMenuButton, "visibility", {
        converter : function(v) {return v ? "visible" : "excluded" }
      });
      var backupMenu = new qx.ui.menu.Menu();
      backupMenuButton.setMenu(backupMenu);
      systemMenu.add(backupMenuButton);
      
      // backup progress widget
      var backupProgress = new qcl.ui.dialog.ServerProgress(
        "backupProgress","backup.backup"
      );
      backupProgress.set({
        hideWhenCompleted : true
      });      
      
      // create backup button
      var createBackupButton = new qx.ui.menu.Button();
      createBackupButton.setLabel(this.tr('Create Backup'));
      backupMenu.add(createBackupButton);
      
      createBackupButton.addListener("execute", function(e) {
        var params = [this.getApplication().getDatasource(),"backupProgress"].join(",");
        backupProgress.setMessage(this.tr("Saving backup..."));
        backupProgress.setMethod("createBackup");
        backupProgress.start(params);
      }, this);
      
      // restore Backup
      var restoreBackupButton = new qx.ui.menu.Button();
      restoreBackupButton.setLabel(this.tr('Restore Backup'));
      backupMenu.add(restoreBackupButton);
      restoreBackupButton.addListener("execute", function(e) {
        var token = Math.random().toString().substring(2);
        this.__token= token;
        rpcMgr.execute("backup.backup", "dialogRestoreBackup", 
          [this.getApplication().getDatasource(),token]
        );
      }, this);
      qx.event.message.Bus.getInstance().subscribe("backup.restore", function(e){
        var data = e.getData();
        if ( data.token != this.__token ) 
        {
          this.warn("Invalid message token.");
          return;
        }
        var params = [data.datasource, data.file,"backupProgress"].join(",")
        backupProgress.setMessage(this.tr("Restoring backup..."));
        backupProgress.setMethod("restoreBackup");
        backupProgress.start(params);
      },this);
      
      // delete Backup
      var deleteBackupButton = new qx.ui.menu.Button();
      deleteBackupButton.setLabel(this.tr('Delete old backups'));
      permMgr.create("backup.delete").bind("state", deleteBackupButton, "visibility", {
        converter : function(v) {return v ? "visible" : "excluded" }
      });      
      backupMenu.add(deleteBackupButton);
      deleteBackupButton.addListener("execute", function(e) {
        rpcMgr.execute("backup.backup", "dialogDeleteBackups", 
          [this.getApplication().getDatasource()]
        );
      }, this);      
      
      // download Backup
      var downloadBackupButton = new qx.ui.menu.Button();
      downloadBackupButton.setLabel(this.tr('Download backup'));
      permMgr.create("backup.download").bind("state", downloadBackupButton, "visibility", {
        converter : function(v) {return v ? "visible" : "excluded" }
      });      
      backupMenu.add(downloadBackupButton);
      downloadBackupButton.addListener("execute", function(e) {
        rpcMgr.execute("backup.backup", "dialogDownloadBackup", 
          [this.getApplication().getDatasource()]
        );
      }, this);

      // called after a backup has been restored
      qx.event.message.Bus.getInstance().subscribe("backup.restored", function(e)
      {
        var data = e.getData();
        if (data.datasource !== app.getDatasource())return;

        var msg = this.tr("The datasource has just been restored to a previous state and will now be reloaded.");
        dialog.Dialog.alert(msg, function()
        {
          app.getWidgetById("mainFolderTree").reload();
          app.getWidgetById("mainListView").reload();
          app.setModelId(0);
        }, this);
      }, this);
    }
  }
});

