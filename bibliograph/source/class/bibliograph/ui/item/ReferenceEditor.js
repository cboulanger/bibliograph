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

/*global qx qcl bibliograph*/

/**
 * The editor for individual entries
 */
qx.Class.define("bibliograph.ui.item.ReferenceEditor",
{
  extend : qx.ui.container.Composite,
  include : [bibliograph.ui.item.MCreateForm ],

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties :
  {
    /**
     * The datasource of the items displayed
     */
    datasource :
    {
      check : "String",
      nullable : true,
      event : "changeDatasource",
      apply : "_applyDatasource"
    },

    /**
     * The type of the model of the item
     */
    modelType :
    {
      check : "String",
      nullable : true,
      event : "changeModelType"
    },

    /**
     * The id of the currently displayed item.
     * @todo rename to modelId
     */
    referenceId :
    {
      check : "Integer",
      nullable : true,
      event : "changeReferenceId",
      apply : "_applyReferenceId"
    },

    /**
     * The reference type of the record displayed
     */
    referenceType :
    {
      check : "String",
      nullable : true,
      event : "changeReferenceType",
      apply : "_applyReferenceType"
    },

    /**
     * The store providing data
     */
    store :
    {
      check : "qcl.data.store.JsonRpc",
      event : "changeStore",
      nullable : true
    },

    /**
     * The model data as sent from the server
     */
    data :
    {
      check : "Object",
      nullable : true
    },

    /**
     * The data model of the form data
     */
    model :
    {
      check : "qx.core.Object",
      nullable : true,
      event : "changeModel"
    }
  },

  /*
  *****************************************************************************
      CONSTRUCTOR
  *****************************************************************************
  */
  construct : function()
  {
    this.base(arguments);
    this._forms = {

    };

    /*
     * create store and bind the application datasource model's
     * tableModelService to the serviceName property
     */
    var store = new qcl.data.store.JsonRpc(null, "");
    this.getApplication().bind("datasourceModel.tableModelService", store, "serviceName");
    this.setStore(store);

    /*
     * listen for server messages to update single form fields
     * todo: I cannot remember what this is for - remove?
     */
    qx.event.message.Bus.getInstance().subscribe("bibliograph/fieldeditor/update", function(e)
    {
      var data = e.getData();
      var messageIsForMe = data.datasource == this.getDatasource() && data.modelType == this.getModelType() && data.modelId == this.getReferenceId();
      if (messageIsForMe)
      {
        var filter = [];
        for (var key in data.data)
        {
          filter.push(key);
          var value = data.data[key];
          qx.event.message.Bus.dispatch(new qx.event.message.Message("reference.changeData",
          {  // FIXME use channel notation
            'referenceId' : data.modelId,
            'name' : key,
            'value' : value,
            'old' : this.getData()[key]
          }));
          this.getData()[key] = value;
        }
        this._syncFormWithModel(this.getReferenceType(), filter);
      }
    }, this);
  },

  /*
  *****************************************************************************
      MEMBERS
  *****************************************************************************
  */
  members :
  {
    /*
    ---------------------------------------------------------------------------
       WIDGETS
    ---------------------------------------------------------------------------
    */
    formStack : null,
    stackView : null,
    annotationPage : null,
    editor : null,

    /*
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */
    _forms : null,
    _lastRequest : null,
    __preventDefault : false,
    __deferReattachBubbleEvent : 0,

    /*
    ---------------------------------------------------------------------------
       GETTERS & SETTERS
    ---------------------------------------------------------------------------
    */

    /**
     * Returns the form data for the given reference type
     * @param reftype {String}
     * @return {Object} a map with objects that belong to the form
     */
    getFormData : function(reftype)
    {
      var id = this.getDatasource() + "-" + this.getModelType() + "-" + reftype;
      return this._forms[id];
    },

    /**
     * Caches the form data for the given reference type
     * @param reftype {String}
     * @param formData {Object} a map with objects that belong to the form
     * @return {void}
     */
    setFormData : function(reftype, formData)
    {
      var id = this.getDatasource() + "-" + this.getModelType() + "-" + reftype;
      this._forms[id] = formData;
    },

    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */


    _applyDatasource : function(value, old) {
      if (old) {
        this.formStack.setSelection([]);
      }
    },

    /**
     * Initiates the display of the form with data
     * @param referenceId {Number} The current reference id
     * @param old {Number} The previous reference id
     */
    _applyReferenceId : function(referenceId, old)
    {
      if (!referenceId || !this.isVisible())return;

      /*
       * wait 200 ms before dispatching a request, so that
       * we're not sending to many
       */
      qx.util.TimerManager.getInstance().start(function() {
        if (referenceId == this.getReferenceId()) {
          this._load(referenceId);
        }
      }, null, this, null, 500);
    },

    /**
     * Applying the reference type shows the necessary form
     * @param reftype {String} The current reference type
     * @param old {String} The previous reference type
     */
    _applyReferenceType : function(reftype, old)
    {
      /*
       * hide old form
       */
      if (old) {
        this.formStack.setSelection([]);
      }

      /*
       * get form data
       */
      var formData = this.getFormData(reftype);
      if (!formData) {
        this.error("No form data exists for reference type " + reftype);
      }

      /*
       * show appropriate form in the stack
       */
      this.formStack.setSelection([formData.view]);
      this.setEnabled(true);
      this.setVisibility("visible");
    },

    /*
    ---------------------------------------------------------------------------
       INTERNAL METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * Load the reference data and create form if it doesn't exist
     * @param referenceId {Integer}
     */
    _load : function(referenceId)
    {
      // debug
      if (this.__isLoading) {
        return;
      }

      //debug
      if (!referenceId) {
        this.warn("reference editor: no reference id.");
      }

      /*
       * we still have to wait for the service name to be set
       */
      if (this.getStore().getServiceName() === "")
      {
        this.getStore().addListenerOnce("changeServiceName", function() {
          this._load(referenceId);
        }, this);
        return;
      }

      /*
       * disable the current form
       */
      if (this.getReferenceType()) {
        this.setEnabled(false);
      }

      /*
       * load data
       */
      this.showMessage(this.tr("Loading item data..."));
      this.__isLoading = true;
      var datasource = this.getDatasource();
      this.getStore().load("getData", [datasource, referenceId], function(data)
      {
        this.__isLoading = false;
        this.showMessage(null);
        this.setData(data);
        this._populateForm();
      }, this);
    },

    /**
     * Populate the form, creating it if it doesn't exist yet.
     */
    _populateForm : function()
    {
      var data = this.getData();

      /*
       * reference type determines form type
       */
      var reftype = data.reftype;
      if (!reftype) {
        this.error("Invalid data: no reference type");
      }

      /*
       * if we have the form data already, we can display the
       * form right away
       */
      if (this.getFormData(reftype))
      {
        /*
         * update form
         */
        this._syncFormWithModel(reftype);

        /*
         * show form
         */
        this.setReferenceType(reftype);

        /*
         * enable the form
         */
        this.setEnabled(true);
      }/*
       * else, we need to get the form data first
       */
       else
      {
         //todo cache the form data in a persistent storage

        /*
         * load
         */
        this.showMessage(this.tr("Loading form data..."));
        this.menuBar.setEnabled(false);

        /*
         * load form data first
         */
        this.getStore().execute("getFormLayout", [this.getDatasource(), reftype], function(formData) {
          this.showMessage(null);
          this._createForm(reftype, formData);
        }, this);
      }
    },

    /**
     * Creates the form from json data
     * @param reftype {String} The type for which the form is created
     * @param data {Object} The form data
     */
    _createForm : function(reftype, data)
    {
      this.showMessage(this.tr("Creating form, please wait ..."));

      /*
       * form object
       */
      var formObj = this.createForm(data);

      /*
       * form view
       */
      var formView = new bibliograph.ui.item.FormRenderer(formObj);

      /*
       * form controller
       */
      var controller = new qx.data.controller.Form();
      controller.setTarget(formObj);

      /*
       * save in cache
       */
      this.setFormData(reftype,
      {
        'form' : formObj,
        'view' : formView,
        'controller' : controller
      });

      /*
       * add to view
       */
      this.formStack.add(formView);

      /*
       * update form
       */
      this._syncFormWithModel(reftype);

      /*
       * show form
       */
      this.setReferenceType(reftype);

      /*
       * we're done
       */
      this.showMessage(null);
      this.menuBar.setEnabled(true);
    },

    /**
     * Apply the loaded model to the form controller
     * @param reftype {String} The reference type
     * @param filter {Array|undefined}
     *    Optional array of field names to restrict the update to
     * @todo this needs to be rewritten from scratch, maybe preventing changes while form is disabled
     */
    _syncFormWithModel : function(reftype, filter)
    {
      var formData = this.getFormData(reftype);
      var form = formData.form;
      var controller = formData.controller;
      var data = this.getData();

      /*
       * setup form model if not yet created
       */
      if (!controller.getModel())
      {
        var formModel = controller.createModel(true);
        controller.setModel(formModel);
      }

      /*
       * else, remove change listener while form is populated
       */
       else
      {
        var formModel = controller.getModel();
        formModel.removeListener("changeBubble", this._on_changeBubble, this);
      }

      /*
       * copy the data model properties to the form model
       */
      var timer = qx.util.TimerManager.getInstance();
      for (var property in data)
      {
        var value       = data[property];


        var formElement = form.getItems()[property];

        //console.log("Setting field " + property + " to "+value + " in " + formElement + " with filter '" + filter + "'.");

        /*
         * skip if filter is given and property is not in filter
         */
        if ( qx.lang.Type.isArray(filter) && filter.indexOf(property) == -1) {
          continue;
        }

        /*
         * skip if no matching form element
         */
        if (!formElement) {
          continue;
        }

        /*
         * is it a single selection widget?
         */
        if (qx.Class.implementsInterface(formElement, qx.ui.core.ISingleSelection))
        {
          var children = formElement.getChildren();

          /*
           * the child elements have already been loaded
           */
          if (children.length) {
            children.forEach(function(child) {
              if (child.getModel().getValue() == value)
              {
                this.__preventDefault = true;
                formElement.setSelection([child]);
                this.__preventDefault = false;
              }
            }, this);
          }

          /*
           * no, we have to wait for them to load
           */
           else
          {
            this.__deferReattachBubbleEvent++;
            var selectionHandler = qx.lang.Function.bind(function(property)
            {
              var formElement = form.getItems()[property];

              /*
               * select with a small timeout
               */
              timer.start(function()
              {
                formElement.getChildren().forEach(function(child) {
                  if (child.getModel().getValue() == value)
                  {
                    this.__preventDefault = true;
                    formElement.setSelection([child]);
                    this.__preventDefault = false;
                  }
                }, this);
                this.__deferReattachBubbleEvent--;
                if (!this.__deferReattachBubbleEvent)
                {
                  formModel.addListener("changeBubble", this._on_changeBubble, this);
                  this.setEnabled(true);
                }
              }, null, this, null, 50);
            }, this, property);

            /*
             * call selection handler when model has changed
             */
            formElement.getUserData("controller").addListenerOnce("changeModel", selectionHandler);
          }
        }

        /*
         * Are we dealing with a date field?
         */
        else if (formElement instanceof qx.ui.form.DateField)
        {
          if (value !== null) {
            if (!value.match(/[0-9]{4}\-[0-9]{2}\-[0-9]{2}/))
            {
              this.warn("Invalid date value, must be YYYY-MM-DD: " + value);
              value = null;
            } else
            {
              var d = value.split("-");
              value = new Date(d[0], d[1], d[2]);
            }
          }
          controller.getModel().set(property, value);
        }

        /*
         * no, simple copy value
         */
         else
        {
          controller.getModel().set(property, value);
        }
      }

      /*
       * (re-) attach listener
       */
      if (!this.__deferReattachBubbleEvent) {
        formModel.addListener("changeBubble", this._on_changeBubble, this);
      }


      bibliograph.ui.item.ReferenceEditor.__preventSave = false;  // @todo
    },

    /*
    ---------------------------------------------------------------------------
       EVENT HANDLERS
    ---------------------------------------------------------------------------
    */

    /**
     * Called when the view becomes visible
     */
    _on_appear : function()
    {
      var app = this.getApplication();
      var refId = this.getReferenceId();
      var ds = this.getDatasource();
      if (refId && ds) {
        this.reload();
      }
    },

    /**
     * Called when form field changed
     */
    _on_changeBubble : function(event)
    {

      /*
       * data
       */
      var eventData = event.getData();
      var value = eventData.value;
      var old = eventData.old;
      var name = eventData.name;
      var data = {};

      /*
       * get value from selection models
       */
      if (value instanceof qx.core.Object) {
        value = value.getValue();
      }

      /*
       * dispatch message
       */
      qx.event.message.Bus.dispatch(new qx.event.message.Message("reference.changeData",
      {
        'referenceId' : this.getReferenceId(),
        'name' : name,
        'value' : value,
        'old' : old
      }));

      /*
       * if changed, save change to server and update listview
       */
      if (this.getData()[name] !== value) {
        /*
         * wait some time before dispatching a request, so that
         * we're not sending to many
         */
        qx.util.TimerManager.getInstance().start(function()
        {
          var target = this.getFormData(this.getReferenceType()).form.elements[name];

          /*
           * if value has changed in the meantime, don't save
           */
          if (target.getValue && value != target.getValue() && !value instanceof Date) {
            //console.warn("Not saving field '"+ name + "' value has changed");
            return;
          }

          /*
           * if value is a Date object, serialize it
           */
          if (value instanceof Date) {
            value = value.getFullYear() + "-" + value.getMonth() + "-" + value.getDate();
          }

          /*
           * create map
           */
          data[name] = value;

          /*
           * disable form if reftype changed
           */
          if (name == "reftype" && !this.__preventDefault) {
            this.setEnabled(false);
          }

          /*
           * save to server
           */
          this.showMessage(this.tr("Saving..."));
          this.getStore().execute("saveData", [this.getData().datasource, this.getData().referenceId, data], function()
          {
            this.showMessage(null);

            /*
             * update data
             */
            this.getData()[name] = value;

            /*
             * reload data if reftype changed
             */
            if (name == "reftype" && !this.__preventDefault) {
              this.reload();
            }
          }, this);
        }, null, this, null, 500);
      }
    },

    /**
     * Save changes in the metadata fields (abstract, notes, etc...)
     * @param name {String}
     * @param e {qx.event.type.Data}
     */
    _on_metadata_changeValue : function(name, e)
    {
      if (!name) {
        this.error("No property name given");
      }
      var target = e.getTarget();
      if (!target) {
        //console.warn("No valid target!");
        return;
      }
      if (bibliograph.ui.item.ReferenceEditor.__preventSave === true)
      {
        //console.warn("Prevent save flag is on!");
        bibliograph.ui.item.ReferenceEditor.__preventSave = false;
        return;
      }
      var value = e.getData();
      if (value === null) {
        return;
      }

      /*
       * wait some time before dispatching a request, so that
       * we're not sending to many
       */
      qx.util.TimerManager.getInstance().start(function() {
        if (value == target.getValue())
        {
          this.showMessage(this.tr("Saving..."));
          var data = {

          };
          data[name] = value;
          this.getStore().execute("saveData", [this.getDatasource(), this.getReferenceId(), data], function()
          {
            this.showMessage(null);
            this.getData()[name] = value;
          }, this);
        } else
        {
          //console.warn("Not saving field '"+ name + "' value has changed");
        }
      }, null, this, null, 1000);
    },

    /**
     * Used as converter to prevent saving when setting new values
     */
    _metadata_observer_converter : function(value)
    {
      bibliograph.ui.item.ReferenceEditor.__preventSave = true;
      return value;
    },

    /**
     * Called when the annotation editor becomes visible
     */
    _on_annote_appear : function()
    {
      if (!this.getReferenceId()) {
        return;
      }
      this._on_annote_changeReferenceId();
    },

    /**
     * Called for the annotation editor when the record id changes
     */
    _on_annote_changeReferenceId : function()
    {
      /*
       * do nothing if editor is not visible
       */
      if (!this.annotationPage.isSeeable()) {
        return;
      }

      /*
       * check if content has changed
       */
      var value = this.editor.getValue();
      var oldValue = this.editor.getUserData("oldValue");
      var oldId = this.editor.getUserData("oldId");
      var oldDatasource = this.editor.getUserData("oldDatasource");
      if (oldId && oldValue != value)
      {
        this.getStore().execute("saveData", [oldDatasource, oldId, {
          "annote" : value
        }]);
        this._loadAnnotationEditor();
      } else
      {
        this._loadAnnotationEditor();
      }
    },

    /**
     * Load content of annotation
     */
    _loadAnnotationEditor : function()
    {
      var datasource = this.getDatasource();
      var referenceId = this.getReferenceId();
      this.getApplication().getRpcManager().execute("bibliograph.reference", "getData", [datasource, referenceId, ["annote"]], function(data)
      {
        this.editor.setValue(data.annote);
        this.editor.setUserData("oldValue", data.annote);
        this.editor.setUserData("oldId", referenceId);
        this.editor.setUserData("oldDatasource", datasource);
      }, this);
    },

    /**
     * Called when user clicks on "save button"
     */
    _on_annote_save : function()
    {
      var value = this.editor.getValue();
      this.getStore().execute("saveData", [this.getDatasource(), this.getReferenceId(), {
        "annote" : value
      }], function() {
        this.editor.setUserData("oldValue", value);
      }, this);
    },
    _on_annote_exit : function() {
      this._on_annote_save();
    },

    /*
    ---------------------------------------------------------------------------
       INTERNAL METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * Shows the selected stack view
     */
    _showStackViewPage : function(page, button)
    {
      this.stackView.setSelection([page]);
      if (this.__button) {
        this.__button.setLabel(this.__button.getUserData("label"));
      }
      button.setUserData("label", button.getLabel());
      button.setLabel("<u>" + button.getLabel() + "<u>");
      this.__button = button;
    },

    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * Loads a reference
     */
    load : function(referenceId) {
      //qx.event.message.Bus.dispatch(qx.event.message.Message(""))
      this.setReferenceId(referenceId);
    },

    /**
     * Reloads the current id
     */
    reload : function() {
      this._load(this.getReferenceId());
    },

    /**
     * Shows a status message
     * @param msg
     */
    showMessage : function(msg)
    {
      this._statusLabel.setValue(msg);
    }
  }
});
