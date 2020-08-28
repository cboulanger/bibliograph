/* ************************************************************************

  Bibliograph: Online Collaborative Reference Management

   Copyright:
     2007-2018 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Christian Boulanger (cboulanger)

************************************************************************ */

/**
 * Plugin Initializer Class
 *
 */
qx.Class.define("bibliograph.plugins.backup.Plugin", {
  extend: qcl.application.BasePlugin,
  include: [
    qx.locale.MTranslation,
    qcl.access.MPermissions
  ],
  type: "singleton",
  members: {
    permissions: {
      create_backup: {
        depends: "backup.create",
        granted: true,
        updateEvent: "app:changeDatasource",
        condition: app => app.getDatasource() !== null
      }
    },
    
    /**
     * Returns the name of the plugin
     * @returns {string}
     */
    getName() {
      return "Backup plugin";
    },
  
    init() {
      let menu = qx.core.Id.getQxObject("toolbar/system-menu");
      let button = this.getQxObject("backup-button");
      menu.add(button);
      menu.addOwnedQxObject(button);
      this.progress = new qcl.ui.dialog.ServerProgress("plugin-backup-progress").set({
        hideWhenCompleted : true
      });
    },
    
    _createQxObjectImpl(id) {
      let control;
      let app = this.getApplication();
      let menu = qx.core.Id.getQxObject("toolbar/system-menu");
      switch (id) {
        case "backup-button":
          control = new qx.ui.menu.Button(this.tr("Backup"));
          this.bindVisibility("backup.create", control);
          control.setMenu(this._createQxObject("backup-menu", menu));
          break;
        case "backup-menu":
          control = new qx.ui.menu.Menu();
          control.add(this._createQxObject("create-button", control));
          control.add(this._createQxObject("restore-button", control));
          //control.add(this.getQxObject("delete-button", control));
          //control.add(this.getQxObject("download-button", control));
          break;
        case "create-button":
          control = new qx.ui.menu.Button(this.tr("Create Backup"));
          this.bindEnabled(this.permissions.create_backup, control);
          control.addListener("execute", this.createBackup, this);
          break;
        case "restore-button":
          control = new qx.ui.menu.Button(this.tr("Restore Backup"));
          control.addListener("execute", this.restoreBackup, this);
          qx.event.message.Bus.getInstance().subscribe("backup.restore", this.__onBackupRestore, this);
          qx.event.message.Bus.getInstance().subscribe("backup.restored", this.__onBackupRestored, this);
          break;
        case "delete-button":
          control = new qx.ui.menu.Button(this.tr("Delete old backups"));
          this.bindVisibility("backup.delete", control);
          control.addListener("execute", () => {
            app.getRpcClient("backup.ui").request("choose-delete", [app.getDatasource()]);
          });
          break;
        case "download-button":
          control = new qx.ui.menu.Button(this.tr("Download backup"));
          this.bindVisibility("backup.download", control);
          control.addListener("execute", () => {
            app.getRpcClient("backup.ui").request("choose-download", [app.getDatasource()]);
          });
          break;
      }
      return control || this.base(arguments, id);
    },
    
    async createBackup() {
      let comment = await this.getApplication().prompt(this.tr("You can enter a description for this backup (optional)"));
      if (comment === undefined) {
        return;
      }
      this.progress.set({
        message: this.tr("Saving backup..."),
        route: "backup/progress/create"
      }).start({
        datasource: this.getApplication().getDatasource(),
        comment
      });
    },
    
    restoreBackup() {
      let app = this.getApplication();
      let token = Math.random().toString().substring(2);
      this.__token= token;
      app.getRpcClient("backup.ui")
        .request("confirm-restore", [app.getDatasource(), token]);
    },
    
    __onBackupRestore(e) {
      let data = e.getData();
      if (data.token !== this.__token) {
        this.warn("Invalid message token.");
        return;
      }
      this.progress.set({
        message: this.tr("Restoring backup..."),
        route: "backup/progress/restore"
      }).start({
        datasource: data.datasource,
        file: data.file
      });
    },
    
    async __onBackupRestored(e) {
      let app = this.getApplication();
      let data = e.getData();
      if (data.datasource !== app.getDatasource()) {
        return;
      }
      let msg = this.tr("The datasource has just been restored to a previous state and will now be reloaded.");
      await this.getApplication().alert(msg);
      qx.core.Id.getQxObject("folder-tree-panel/tree-view").reload();
      qx.core.Id.getQxObject("table-view").reload();
      app.setModelId(0);
    }
  }
});

