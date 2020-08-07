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
        dialog = this.__dialogs[type] = qxl.dialog.Dialog[type]();
        this.addOwnedQxObject(dialog, type);
      }
      if (qx.lang.Type.isObject(config)) {
        dialog.set(config);
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
      config.message = msg;
      return this.createDialog("alert", config);
    },
  
    /**
     * Return the promise for a (cached) warning dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    warning(msg, config= {}) {
      config.message = msg;
      return this.createDialog("warning", config);
    },
  
    /**
     * Return the promise for a (cached) error dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    error(msg, config= {}) {
      config.message = msg;
      return this.createDialog("error", config);
    },
  
    /**
     * Return the promise for a (cached) confirm dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    confirm(msg, config= {}) {
      config.message = msg;
      return this.createDialog("confirm", config);
    },
  
    /**
     * Return the promise for a (cached) prompt dialog
     * @param {String} msg The message for the user
     * @param {Object} config Additional properties to set
     * @return {Promise}
     */
    prompt(msg, config= {}) {
      config.message = msg;
      return this.createDialog("prompt", config);
    }
  }
});
