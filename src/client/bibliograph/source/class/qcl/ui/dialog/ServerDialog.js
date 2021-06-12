/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2015 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/**
 * Provides server-generated dialogs and popups
 * to do: convert to use _createQxObjectImpl
 */
qx.Class.define("qcl.ui.dialog.ServerDialog", {
  extend: qx.core.Object,
  type: "singleton",
  construct: function() {
    this.base(arguments);
    this.setQxObjectId("server-dialogs");
    qx.core.Id.getInstance().register(this);
  },
  properties: {
    enabled: {
      check: "Boolean",
      apply: "_applyEnabled",
      event: "changeEnabled"
    }
  },
  members: {

    /**
     * The currently visible widget. There can only be one at the time
     */
    __current: null,
    
    /**
     * Turns remote server control on or off. If turned on, you can trigger the
     * display of dialogs using messages which can come from the server.
     * @see #__onServerDialog
     * @param {boolean} value
     */
    _applyEnabled: function (value) {
      let messageName = "dialog";
      if (value) {
        qx.event.message.Bus.subscribe(messageName, this.__onServerDialog, this);
      } else {
        qx.event.message.Bus.unsubscribe(messageName, this.__onServerDialog, this);
      }
    },

    /**
     * Hide all server dialogs
     */
    hideServerDialogs: function() {
      for (let instance of this.getOwnedQxObjects()) {
        instance.hide();
      }
    },
    
    _createQxObjectImpl(id) {
      let control;
      let clazz = qx.lang.String.firstUp(id);
      if (qx.lang.Type.isFunction(qxl.dialog[clazz])) {
        // use class from qxl.dialog package
        control = new qxl.dialog[clazz]();
      } else if (qx.lang.Type.isFunction(qcl.ui.dialog[clazz])) {
        // use class from qcl.ui.dialog
        control = new qcl.ui.dialog[clazz]();
      }
      return control || this.base(arguments, id);
    },
    
    /**
     * Handles the dialog request from the server. The message data has to be a
     * map with of the following structure:
     * <code>
     * {
     *   type : "(alert|confirm|form|login|select|wizard)",
     *   properties : { the dialog properties WITHOUT a callback },
     *   service : "the.name.of.the.rpc.service",
     *   method : "serviceMethod",
     *   params : [ the, parameters, passed, to, the, service, method ]
     * }
     * @param {Object} message
     * </code>
     */
    __onServerDialog(message) {
      if (!this.getEnabled()) {
        console.warn("Server dialogs disabled!");
        return;
      }
      let app = qx.core.Init.getApplication();
      let data = message.getData();
      data.properties.callback = null;
      if (data.service) {
        data.properties.callback = result => {
          // push the result to the beginning of the parameter array
          if (!qx.lang.Type.isArray(data.params)) {
            data.params = [];
          }
          data.params.unshift(result);
          // send request back to server
          app.getRpcClient(data.service).request(data.method, data.params);
        };
      }
      
      // turn popup on or off
      if (data.type === "popup") {
        if (app.showPopup === undefined) {
          app.warn("Cannot show application popup.");
          data.properties.callback(false);
          return;
        }
        let msg = data.properties.message;
        if (msg) {
          app.showPopup(msg);
        } else {
          app.hidePopup();
        }
        if (typeof data.properties.callback === "function") {
          data.properties.callback(true);
        }
        return;
      }
      app.hidePopup();
      
      // create dialog according to type, if exists
      let widget = this.getQxObject(data.type, true);
      let isNew = !widget;
      
      // reusing forms doesn't work
      if (widget && data.type === "form") {
        this.removeOwnedQxObject(widget);
        widget.dispose();
        widget = null;
      }
      
      // create it if we don't have it already
      if (!widget) {
        widget = this.getQxObject(data.type);
        if (!widget) {
          let msg = `Server dialog type '${data.type}' is invalid.`;
          this.getApplication().error(msg);
          throw new Error(msg);
        }
      }

      // hide the previously shown widget if there was one
      if (this.__current && widget !== this.__current) {
        this.__current.hide();
      }
      this.__current = widget;
      
      // show/hide widget
      if (data.show !== undefined) {
        if (!data.show) {
          widget.hide();
          return;
        }
        widget.show();
      }
      
      // marshal special datefield values
      if (data.type === "form") {
        if (!qx.lang.Type.isObject(data.properties.formData)) {
          app.error("No form data in json response.");
          return;
        }
        for (let fieldName of Object.getOwnPropertyNames(data.properties.formData)) {
          let fieldData = data.properties.formData[fieldName];
          if (fieldData.type === "datefield") {
            if (fieldData.dateFormat) {
              fieldData.dateFormat = new qx.util.format.DateFormat(fieldData.dateFormat);
            }
            fieldData.value = new Date(fieldData.value);
          }
        }
      }
      
      /*
       * auto-submit the dialog input after the given
       * timout in seconds
       */
      // function to call after timeout with closure vars
      let type = data.type;
      let autoSubmitTimeout = data.properties.autoSubmitTimeout;
      let requireInput = data.properties.requireInput;
      
      function checkAutoSubmit () {
        switch (type) {
          /*
           * prompt dialog will periodically check for input and submit it
           * if it hasn't changed for the duration of the timeout
           */
          case "prompt":
            if (requireInput) {
              let newValue = widget._textField.getValue();
              let oldValue = widget._textField.getUserData("oldValue");
              
              //console.log("old: '" + oldValue + "', new: '"+newValue+"'.");
              
              if (newValue && newValue === oldValue) {
                widget._handleOk();
              } else if (widget.getVisibility() === "visible") {
                widget._textField.setUserData("oldValue", newValue);
                qx.event.Timer.once(checkAutoSubmit, this, autoSubmitTimeout * 1000);
              }
              return;
            }
        }
        widget._handleOk();
      }
      
      // start timeout
      if (qx.lang.Type.isNumber(autoSubmitTimeout) && autoSubmitTimeout > 0) {
        qx.event.Timer.once(checkAutoSubmit, this, autoSubmitTimeout * 1000);
      }
      
      // remove the properties
      delete data.properties.autoSubmitTimeout;
      delete data.properties.requireInput;
      
      // set all properties
      
      widget.set(data.properties);
      
      //todo: show() must not create a new blocker.
      // this must be solved in the dialog contrib itself
      if (isNew) {
        widget.show();
      } else if (data.properties.show !== false) {
        widget.setVisibility("visible");
      }
      
      /*
       * Progress widget executes callback immediately, unless it is at 100% and
       * the OK Button has been activated
       */
      if (data.type === "progress" &&
        qx.lang.Type.isFunction(widget.getCallback()) &&
        (widget.getProgress() !== 100 || widget.getOkButtonText() === null)
      ) {
        widget.getCallback()(true);
      }
      
      /*
       * focus, doesn't work yet
       */
      qx.lang.Function.delay(function () {
        switch (type) {
          case "alert":
          case "confirm":
            try {
              widget._okButton.focus();
            } catch (e) {}
        }
      }, 1000, this);
    }
  }
});
