/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright:
    2003-2020 Christian Boulanger

  License:
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors:
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * Tool to work with object ids and to record simple playwright script
 * fragments. It provides a) a list of object ids; when clicking on a list item,
 * the widget with that id is highlighted if it is visible. b) a script recorder
 * which, when turned on, records clicks and text input that can be inserted into
 * Playwright tests.
 *
 */
qx.Class.define("qcl.ui.tool.ObjectIdsView",
  {
    extend : qx.ui.container.Composite,
    statics: {
      ATTRIBUTE_QX_OBJECT_ID: "data-qx-object-id"
    },
    construct : function() {
      this.base(arguments);
      this.setLayout(new qx.ui.layout.VBox(5));
      // ui
      this.add(this.getQxObject("header"));
      this.add(this.getQxObject("tabview"), {flex:1});
      // listen for clicks
      document.addEventListener("click", this._onClick.bind(this));
      this.addListenerOnce("appear", () => {
        this.resetWidget();
      }, this);
    },
    members: {
      _createQxObjectImpl(id) {
        let control;
        switch (id) {
          case "header":
            control = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
            control.add(this.getQxObject("searchbox"), {flex:1});
            control.add(this.getQxObject("record-button"));
            control.add(this.getQxObject("reset-button"));
            break;
          case "searchbox":
            control = this.searchbox = new qx.ui.form.TextField();
            control.set({
              placeholder: "Type to filter",
              liveUpdate: true
            });
            control.addListener("changeValue", evt => {
              let input = evt.getData();
              let list = this.getQxObject("list");
              let delegate = Object.assign({}, list.getDelegate());
              if (input && input.length > 2) {
                delegate.filter = model => model.getLabel() && model.getLabel().toLowerCase().includes(input);
              } else {
                delete delegate.filter;
              }
              list.setDelegate(delegate);
            });
            break;
          case "record-button":
            control = this.recordButton = new qx.ui.form.ToggleButton("⏺");
            control.addListener("changeValue", evt => {
              let isRecording = evt.getData();
              control.setLabel(isRecording ? "⏸": "⏺️");
              this.getQxObject("tabview").setSelection([this.getQxObject("tab-editor")]);
            });
            break;
          case "reset-button":
            control = new qx.ui.form.Button("🔄");
            control.addListener("execute", this.resetWidget, this);
            this.getQxObject("tabview").setSelection([this.getQxObject("tab-editor")]);
            break;
          case "tabview":
            control = new qx.ui.tabview.TabView();
            control.add(this.getQxObject("tab-list"));
            control.add(this.getQxObject("tab-editor"));
            break;
          case "tab-list":
            control = new qx.ui.tabview.Page("Object ids");
            control.setLayout(new qx.ui.layout.Grow());
            control.add(this.getQxObject("list"));
            break;
          case "list": {
            control = this.list = new qx.ui.list.List();
            control.setDelegate({
              bindItem : function(controller, item, id) {
                // bind label
                controller.bindProperty("label", "label", {}, item, id);
                // bind progress if any
                controller.bindProperty("widget.visibility", "enabled", {
                  converter: value => value === "visible"
                }, item, id);
              }
            });
            control.setModel(this.createListModel());
            control.getSelection().addListener("change", this.__handleChangeListSelection, this);
            control.addListener("dblclick", this.__handleChangeListSelection, this);
            break;
          }
          case "tab-editor": {
            control = new qx.ui.tabview.Page("Recorded playwright script");
            let textArea = this.textArea = new qx.ui.form.TextArea("");
            control.setLayout(new qx.ui.layout.Grow());
            control.add(textArea);
          }
        }
        return control || this.base(arguments, id);
      },
      
      __handleChangeListSelection() {
        let list = this.list;
        if (list.getSelection().getLength()) {
          let widget = list.getSelection().getItem(0).getWidget();
          let domElem = widget.getContentElement && widget.getContentElement() && widget.getContentElement().getDomElement();
          if (!widget.__highlighted && domElem) {
            widget.__highlighted = true;
            let style = domElem.style;
            let border = String(style.border);
            style.border = "5px dotted yellow";
            qx.event.Timer.once(() => {
              style.border = border;
              widget.__highlighted = false;
            }, null, 1000);
          }
        }
      },
  
      /**
       * Reset list, searchbox and editor
       */
      resetWidget() {
        this.searchbox.setValue("");
        this.textArea.setValue("");
        this.updateList();
      },
  
      /**
       * Update the list
       */
      updateList() {
        this.list.setModel(this.createListModel());
      },
  
      /**
       * Creates the model for the list of qx ids
       * @return {qx.core.Object}
       */
      createListModel() {
        let objectIds = [];
        let that = this;
        (function traverseObjects(arr) {
          arr.forEach(obj => {
            if (!(obj instanceof qx.core.Object)) {
              return;
            }
            let id = qx.core.Id.getAbsoluteIdOf(obj);
            if (id) {
              objectIds.push({
                widget: obj,
                label: id
              });
              if (obj instanceof qx.ui.core.Widget && !obj.__listenersAdded) {
                if (obj instanceof qx.ui.form.AbstractField) {
                  obj.addListener("changeValue", evt => that._onChangeTextInput(evt, id));
                }
                obj.__listenersAdded = true;
              }
            } else {
              this.warn("Cannot find id for " + obj);
            }
            let arr = obj.getOwnedQxObjects();
            if (Array.isArray(arr)) {
              traverseObjects(arr);
            }
          });
        })(Object.values(qx.core.Id.getInstance().getRegisteredObjects()));
        objectIds.sort((a, b) => a.label < b.label ? -1 : 1);
        return qx.data.marshal.Json.createModel(objectIds);
      },
      
      _getQxObjectIdSelector(id) {
        return `[data-qx-object-id="${id}"]`;
      },
      
      /**
       * Checks if the given element has a qx object id and returns it if it exists.
       * Otherwise returns false
       * @param {Element} elem The DOM element to check
       * @param {Boolean} ignoreAnonymous Whether to skip elements that have a "qxanonymous" attribute. Defaults to true.
       * @return {string|boolean}
       * @private
       */
      _checkQxObjectId(elem, ignoreAnonymous= true) {
        let qxObjectId = elem.getAttribute("data-qx-object-id");
        if (qxObjectId) {
          return qxObjectId;
        }
        if (ignoreAnonymous && elem.getAttribute("qxanonymous")) {
          return this._checkQxObjectId(elem.parentElement);
        }
        return false;
      },
      
      /**
       * Given a DOM Element, return a unique css selector that is suitable
       * to identify a qooxdoo widget or one of its components. This will
       * prefer qx object ids if available.
       * @param {Element} elem The DOM node to get the selector for
       * @param {Element?} rootElem If given, the root element relative to which
       * the selector is calculated, unless a qx object id is found first
       * @return {String|null}
       * @private
       */
      _getCssSelector(elem, rootElem) {
        if (rootElem) {
          if (!this._checkQxObjectId(rootElem, false)) {
            throw new Error("Root element must have an object id.");
          }
        } else {
          rootElem = document.body;
        }
        let qxObjectId = this._checkQxObjectId(elem);
        let nodePath = [];
        let node = elem;
        while (!qxObjectId && node !== rootElem) {
          nodePath.unshift(node);
          node = node.parentElement;
          qxObjectId = this._checkQxObjectId(node, false);
        }
        let selector = qxObjectId ? this._getQxObjectIdSelector(qxObjectId) : "body";
        if (nodePath.length) {
          nodePath = nodePath
            .map(node => {
              let siblings = [...node.parentElement.children];
              return siblings.findIndex(n => n === node) + 1;
            });
          nodePath = "${\"" + nodePath.join(">") + "\".replace(/(\\d)/g,\":nth-child(\$1)\")}";
        }
        return selector + ">" + nodePath;
      },
      _getCheckApplicationIdleCode() {
        return `await app.waitForIdle();`;
      },
      
      /**
       * Adds a script line
       * @param {String} line
       * @private
       */
      _addToScript(line) {
        if (this.recordButton.getValue()) {
          this.textArea.setValue(this.textArea.getValue() + "\n" + line);
        }
      },
      
      /**
       * This checks if the user action has initiated some async (network) activity,
       * records this in the script and updates the id list once the
       * activities have finished (since they might have created new widgets).
       */
      _checkApplicationIdle() {
        const taskMonitor = qx.core.Init.getApplication().getTaskMonitor();
        if (taskMonitor && taskMonitor.getBusy()) {
          this._addToScript(this._getCheckApplicationIdleCode());
          taskMonitor.addListenerOnce("changeBusy", this.updateList, this);
        }
      },
      
      /**
       * Handles a click on an element
       * @param evt
       * @private
       */
      _onClick(evt) {
        evt.stopPropagation();
        let elem = evt.target;
        // ignore if not recording or if the click was on this window
        if (!this.recordButton.getValue() || this.getContentElement().getDomElement().contains(elem)) {
          return;
        }
        // prefer execute event if we have a qx object id since click() doesn't always work
        let qxObjectId = this._checkQxObjectId(elem);
        if (qxObjectId) {
          if (qx.core.Id.getQxObject(qxObjectId).hasListener("execute")) {
            this._addToScript(`await app.fireEvent("${qxObjectId}"), "execute");`);
          } else {
            this._addToScript(`await app.click(\`${qxObjectId}\`);`);
          }
        } else {
          let selector = this._getCssSelector(elem);
          this._addToScript(`await app.page.click(\`${selector}\`);`);
        }
        // if the click has initiated i/o or other asynchronous logic, wait for it to finish
        qx.event.Timer.once(() => this._checkApplicationIdle(), this, 500);
      },
      
      /**
       * Handles a text input
       * @param evt
       * @param id
       * @private
       */
      _onChangeTextInput(evt, id) {
        // ignore if not recording
        if (!this.recordButton.getValue()) {
          return;
        }
        if (this.__timerId) {
          clearTimeout(this.__timerId);
        }
        let value = evt.getData();
        this.__timerId = setTimeout(() => {
          this._addToScript(`await app.fill(\`${id}\`, "${value}");`);
          this._checkApplicationIdle();
        }, 500);
      }
    }
  });
