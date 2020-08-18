qx.Mixin.define("qcl.ui.dialog.MDialog", {
  members: {
    /**
     * Returns a promise for a (cached) dialog
     * @param {String} type
     * @param {Object} config
     * @return {Promise<Boolean>}
     */
    createDialog(type, config) {
      if (!this.__dialogs) {
        this.__dialogs = {};
      }
      let dialog = this.__dialogs[type];
      if (dialog === undefined) {
        switch (type) {
          case "error":
          case "warning":
            dialog = new qxl.dialog.Alert(config);
            if (!config.image) {
              dialog.setImage("qxl.dialog.icon." + type);
            }
            break;
          default: {
            let ucType = type[0].toUpperCase() + type.slice(1);
            let Clazz = qxl.dialog[ucType];
            if (!Clazz) {
              throw new Error(`No dialog of type "${type}" exists.`);
            }
            dialog = this.__dialogs[type] = new Clazz(config);
          }
        }
        this.addOwnedQxObject(dialog, type);
      }
      dialog.open();
      return dialog.promise();
    },
  
    /**
     * Return the promise for a (cached) alert dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    alert(msg, config= {}) {
      config.message = String(msg);
      return this.createDialog("alert", config);
    },
  
    /**
     * Return the promise for a (cached) warning dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    warning(msg, config= {}) {
      config.message = String(msg);
      return this.createDialog("warning", config);
    },
  
    /**
     * Return the promise for a (cached) error dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    error(msg, config= {}) {
      config.message = String(msg);
      return this.createDialog("error", config);
    },
  
    /**
     * Return the promise for a (cached) confirm dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    confirm(msg, config= {}) {
      config.message = String(msg);
      return this.createDialog("confirm", config);
    },
  
    /**
     * Return the promise for a (cached) prompt dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    prompt(msg, config= {}) {
      config.message = String(msg);
      return this.createDialog("prompt", config);
    }
  }
});
