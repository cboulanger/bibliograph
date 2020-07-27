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
 * Mixin to create a form from user data
 */
qx.Mixin.define("bibliograph.ui.item.MCreateForm",
{
  construct : function() {
    this._stores = {};
    this._controllers = {};
    this.elements = {};
  },
  
  members :
  {
    _stores : null,
    _controllers : null,

    /**
     * Creates form from data
     *
     * @param formData
     */
    createForm : function(formData) {
      // create new form and form controller
      var form = new qx.ui.form.Form();
      form._elements = [];
      form.elements = {};

      // loop through form data array
      for (let [key, fieldData] of Object.entries(formData)) {
        // Form element
        var formElement = null;
        switch (fieldData.type.toLowerCase()) {
          case "groupheader":
            form.addGroupHeader(fieldData.value);
            break;
          case "textarea":
            formElement = new qx.ui.form.TextArea();
            formElement.setHeight(22 * (fieldData.lines || 3)); //@todo
            if (fieldData.liveUpdate) {
              formElement.setLiveUpdate(fieldData.liveUpdate);
            }
            break;
          case "textfield":
            formElement = new qx.ui.form.TextField();
            if (fieldData.liveUpdate) {
              formElement.setLiveUpdate(fieldData.liveUpdate || false);
            }
            break;
          case "combobox":
            formElement = new qx.ui.form.ComboBox();
            this.createSelectionData(key, formElement, fieldData);
            break;
          case "selectbox":
            formElement = new qx.ui.form.SelectBox();
            this.createSelectionData(key, formElement, fieldData);
            break;
          case "radiogroup":
            formElement = new qx.ui.form.RadioGroup();
            if (fieldData.orientation) {
              formElement.setUserData("orientation", fieldData.orientation);
            }

            // populate from form data
            if (fieldData.options instanceof Array) {
              fieldData.options.forEach(function(item) {
                var radioButton = new qx.ui.form.RadioButton(item.label);
                radioButton.setModel(item.value !== undefined ? item.value : item.label);
                formElement.add(radioButton);
              }, this);
            }
            break;
          case "datefield":
            formElement = new qx.ui.form.DateField();
            formElement.setHeight(18);
            break;
          default:
            this.error("Invalid form field type:" + fieldData.type);
            continue;
        }

        // form element validation
        let validator = null;
        if (fieldData.validation) {
          // is field required?
          if (fieldData.validation.required) {
            formElement.setRequired(true);
          }

          // is there a validator?
          if (fieldData.validation.validator) {
            validator = fieldData.validation.validator;

            // if validator is a string ...
            if (typeof validator == "string") {
              if (qx.util.Validate[validator]) {
                // if a validation factory exists, use this
                validator = qx.util.Validate[validator]();
              } else if (validator.charAt(0) === "/") {
                // regex?
                validator = qx.util.Validate.regExp(new RegExp(validator.substr(1, validator.length - 2)));
              } else {
                this.error("Invalid string validator.");
              }
            } else if (!(validator instanceof qx.ui.form.validation.AsyncValidator) && typeof validator != "function") {
              // in all other cases, it must be a function or an async validation object
              this.error("Invalid validator.");
            }
          }

          // Server validation?
          if (fieldData.validation.service) {
            validator = new qx.ui.form.validation.AsyncValidator(async function(validatorObj, value) {
              let service = fieldData.validation.service;
              let client = qx.core.Init.getApplication().getRpcClient(service.name);
              if (!validatorObj.__asyncInProgress) {
                validatorObj.__asyncInProgress = true;
                let response = client.request(service.method, [value]);
                try {
                  var valid = (response && typeof response == "object" && response.data) ? response.data : response; // FIXME needed?
                  validatorObj.setValid(valid);
                  validatorObj.__asyncInProgress = false;
                } catch (e) {
                  console.warn(e);
                }
              }
            });
          }
        }

        // form element width
        if (fieldData.fullWidth) {
          formElement.setUserData("fullWidth", true);
        }

        // autocomplete
        var ac = fieldData.autocomplete;
        if (qx.lang.Type.isObject(ac) && ac.enabled) {
          if (!ac.service || !ac.method || !qx.lang.Type.isArray(ac.params)) {
            this.warn("Invalid autocomplete service data for " + key);
          } else {
            var controller = new qcl.data.controller.AutoComplete(null, formElement, ac.separator);
            var acStoreId = ac.service + ac.method;
            if (!this._stores[acStoreId]) {
              this._stores[acStoreId] = new qcl.data.store.JsonRpcStore(ac.service);
              this._stores[acStoreId].setAutoLoadMethod(ac.method);
            }
            var store = this._stores[acStoreId];
            controller.__params = ac.params.join(",");
            controller.bind("input", store, "autoLoadParams", {
              "converter" : qx.lang.Function.bind(function(input) {
                return input ? (this.__params + "," + input) : null;
              }, controller)
            });
            store.bind("model", controller, "model");
          }
        }

        // add label and form element to form
        var label = fieldData.label;
        form.add(formElement, label, validator, key);

        // save a reference
        form._elements.push(formElement);
        // old style
        form.elements[key] = formElement;
        // new style
        form.addOwnedQxObject(formElement, key);
      }
      return form;
    },
  
    /**
     *
     * @param key
     * @param formElement
     * @param fieldData
     */
    createSelectionData : function(key, formElement, fieldData) {
      // bind a datastore
      if (qx.lang.Type.isObject(fieldData.bindStore)) {
        var data = fieldData.bindStore;
        var serviceName = data.serviceName;
        var serviceMethod = data.serviceMethod;

        // store
        var storeId = key + serviceName + serviceMethod;
        if (this._stores[storeId] === undefined) {
          this._stores[storeId] = new qcl.data.store.JsonRpcStore(data.serviceName);
        }
        var store = this._stores[storeId];
        formElement.setUserData("store", store);

        // controller
        var controller = new qx.data.controller.List(null, formElement, "label");
        controller.setIconPath("icon");
        formElement.setUserData("controller", controller);

        // load store with child data if not already loaded
        store.bind("model", controller, "model");
        if (store.getModel() === null) {
          store.load(data.serviceMethod, data.params);
        }
      } else if (qx.lang.Type.isArray(fieldData.options)) {
        let model = qx.data.marshal.Json.createModel(fieldData.options);
        // eslint-disable-next-line no-new
        new qx.data.controller.List(model, formElement, "label");
      } else {
        this.warn("Cannot create list: need either bindModel or options data");
      }
    }
  }
});
