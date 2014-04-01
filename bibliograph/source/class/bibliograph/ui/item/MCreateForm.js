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
qx.Mixin.define("bibliograph.ui.item.MCreateForm",
{
  construct : function()
  {
    this._stores = {

    };
    this._controllers = {

    };
    this.elements = {

    };
  },

  /*
  *****************************************************************************
      MEMBERS
  *****************************************************************************
  */
  members :
  {
    _stores : null,
    _controllers : null,

    /**
     * Creates form from form data
     */
    createForm : function(formData)
    {
      /*
       * create new form and form controller
       */
      var form = new qx.ui.form.Form();
      form._elements = [];
      form.elements = {

      };

      /*
       * loop through form data array
       */
      for (var key in formData)
      {
        var fieldData = formData[key];

        /*
         * Form element
         */
        var formElement = null;
        switch (fieldData.type.toLowerCase())
        {
          case "groupheader":
            form.addGroupHeader(fieldData.value);
            break;
          case "textarea":
            formElement = new qx.ui.form.TextArea();
            formElement.setHeight(22 * (fieldData.lines || 3));  //@todo
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
            var selected = null;

            /*
             * populate from form data
             */
            if (fieldData.options instanceof Array) {
              fieldData.options.forEach(function(item)
              {
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
        }

        /*
         * form element validation
         */
        var validator = null;
        if (formElement && fieldData.validation)
        {
          /*
           * is field required?
           */
          if (fieldData.validation.required) {
            formElement.setRequired(true);
          }

          /*
           * is there a validator?
           */
          if (fieldData.validation.validator)
          {
            var validator = fieldData.validation.validator;

            /*
             * if validator is a string ...
             */
            if (typeof validator == "string") {
              /*
               * if a validation factory exists, use this
               */
              if (qx.util.Validate[validator]) {
                validator = qx.util.Validate[validator]();
              }/*
               * else, is it a regular expression?
               */
               else if (validator.charAt(0) == "/") {
                validator = qx.util.Validate.regExp(new RegExp(validator.substr(1, validator.length - 2)));
              }/*
               * error
               */
               else {
                this.error("Invalid string validator.");
              }

            }/*
             * in all other cases, it must be a function or an async validation
             * object
             */
             else if (!(validator instanceof qx.ui.form.validation.AsyncValidator) && typeof validator != "function") {
              this.error("Invalid validator.");
            }

          }

          /*
           * Server validation?
           */
          if (fieldData.validation.service)
          {
            var service = fieldData.validation.service;
            var _this = this;
            validator = new qx.ui.form.validation.AsyncValidator(function(validatorObj, value) {
              if (!validatorObj.__asyncInProgress)
              {
                validatorObj.__asyncInProgress = true;
                qx.core.Init.getApplication().getRpcManager().execute(service.name, service.method, [value], function(response) {
                  try
                  {
                    var valid = (response && typeof response == "object" && response.data) ? response.data : response;  // FIXME needed?
                    validatorObj.setValid(valid);
                    validatorObj.__asyncInProgress = false;
                  }catch (e) {
                    console.warn(e)
                  }
                });
              }
            });
          }
        }

        /*
         * form element width
         */
        if (fieldData.fullWidth) {
          formElement.setUserData("fullWidth", true);
        }

        /*
         * autocomplete
         */
        var ac = fieldData.autocomplete;
        if (ac !== undefined && ac.enabled) {
          if (!ac.service || !ac.method || !qx.lang.Type.isArray(ac.params)) {
            this.warn("Invalid autocomplete service data for " + key);
          } else {
            var controller = new qcl.data.controller.AutoComplete(null, formElement, ac.separator);
            var acStoreId = ac.service + ac.method;
            if (!this._stores[acStoreId])
            {
              this._stores[acStoreId] = new qcl.data.store.JsonRpc(null, ac.service);
              this._stores[acStoreId].setAutoLoadMethod(ac.method);
            }
            var store = this._stores[acStoreId];
            controller.__params = ac.params.join(",");
            controller.bind("input", store, "autoLoadParams", {
              'converter' : qx.lang.Function.bind(function(input) {
                return input ? (this.__params + "," + input) : null
              }, controller)
            });
            store.bind("model", controller, "model");
          }
        }

        /*
         * add label and form element to form
         */
        var label = fieldData.label;
        form.add(formElement, label, validator, key);

        /*
         * form element is disabled by default
         */
        formElement.setEnabled(false);

        /*
         * save a reference
         */
        form._elements.push(formElement);
        form.elements[key] = formElement;
      }
      return form;
    },
    createSelectionData : function(key, formElement, fieldData) {
      /*
       * bind a datastore
       */
      if (qx.lang.Type.isObject(fieldData.bindStore))
      {
        var data = fieldData.bindStore;
        var serviceName = data.serviceName;
        var serviceMethod = data.serviceMethod;

        /*
         * store
         */
        var storeId = key + serviceName + serviceMethod
        if (this._stores[storeId] == undefined) {
          this._stores[storeId] = new qcl.data.store.JsonRpc(null, data.serviceName);
        }
        var store = this._stores[storeId];
        formElement.setUserData("store", store);

        /*
         * controller
         */
        var controller = new qx.data.controller.List(null, formElement, "label");
        controller.setIconPath("icon");
        formElement.setUserData("controller", controller);

        /*
         * load store with child data if not already loaded
         */
        store.bind("model", controller, "model");
        if (store.getModel() == null) {
          store.load(data.serviceMethod, data.params);
        }
      } else if (qx.lang.Type.isArray(fieldData.options))
      {
        var model = qx.data.marshal.Json.createModel(fieldData.options);
        new qx.data.controller.List(model, formElement, "label");
      } else
      {
        this.warn("Cannot create list: need either bindModel or options data");
      }

    }
  }
});
