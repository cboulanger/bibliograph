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
 * Window with a list of object ids. When clicking on a list item,
 * the widget with that id is highlighted if it is visible.
 */
qx.Class.define("qcl.ui.tool.ObjectIds",
{
  extend : qx.ui.window.Window,
  type: "singleton",
  construct : function() {
    this.base(arguments);
    this.set({
      caption: "Object Ids",
      layout: new qx.ui.layout.Grow(),
      showMaximize: false,
      showMinimize: false,
      width: 300,
      height: 600
    });
    // position
    this.addListenerOnce("appear", () => {
      this.reset();
      this.setLayoutProperties({right: 20, top: 50});
    }, this);
    qx.event.message.Bus.getInstance().subscribe("logout", this.close, this);
    
    let vbox = new qx.ui.container.Composite(new qx.ui.layout.VBox(5));
    let header = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
    // search box
    let searchbox = this.searchbox = new qx.ui.form.TextField();
    searchbox.set({
      placeholder: "Type to filter",
      liveUpdate: true
    });
    searchbox.addListener("changeValue", evt => {
      let input = evt.getData();
      let delegate = Object.assign({}, list.getDelegate());
      if (input && input.length > 2) {
        delegate.filter = model => model.getLabel() && model.getLabel().toLowerCase().includes(input);
      } else {
        delete delegate.filter;
      }
      list.setDelegate(delegate);
    });
    header.add(searchbox, {flex:1});
    // record button
    let recordButton = this.recordButton = new qx.ui.form.ToggleButton("▶️");
    recordButton.addListener("changeValue", evt => {
      let isRecording = evt.getData();
      recordButton.setLabel(isRecording ? "⏸": "▶️");
    });
    header.add(recordButton);
    // reset button
    let resetButton = new qx.ui.form.Button("🔄");
    resetButton.addListener("execute", this.reset, this);
    header.add(resetButton);
    vbox.add(header);
    // list
    let list = this.list = new qx.ui.list.List();
    list.setDelegate({
      bindItem : function(controller, item, id) {
        // bind label
        controller.bindProperty("label", "label", {}, item, id);
        // bind progress if any
        controller.bindProperty("widget.visibility", "enabled", {
          converter: value => value === "visible"
        }, item, id);
      }
    });
    list.setModel(this.createListModel());
    // handle selection change
    let handler = () => {
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
    };
    list.getSelection().addListener("change", handler);
    list.addListener("dblclick", handler);
    // tab view
    let tabview = new qx.ui.tabview.TabView();
    let listPage = new qx.ui.tabview.Page("Object ids");
    listPage.setLayout(new qx.ui.layout.Grow());
    listPage.add(list);
    tabview.add(listPage);
    let textArea = this.textArea = new qx.ui.form.TextArea("");
    let textAreaPage = new qx.ui.tabview.Page("Recorded playwright script");
    textAreaPage.setLayout(new qx.ui.layout.Grow());
    textAreaPage.add(textArea);
    tabview.add(textAreaPage);
    vbox.add(tabview, {flex:1});
    // command
    let cmd = this.__cmd = new qx.ui.command.Command("Ctrl+O");
    cmd.addListener("execute", () => {
      this.isVisible() ? this.close() : this.open();
    });
    // add to window
    this.add(vbox);
  },
  members: {
    
    /** @var qx.ui.command.Command */
    __cmd : null,
    
    reset() {
      this.searchbox.setValue("");
      this.textArea.setValue("");
      this.list.setModel(this.createListModel());
    },
    
    createListModel() {
      let objectIds = [];
      let that = this;
      (function traverseObjects(arr) {
        arr.forEach(obj => {
          let id = qx.core.Id.getAbsoluteIdOf(obj);
          if (id) {
            objectIds.push({
              widget: obj,
              label: id
            });
            if (obj instanceof qx.ui.core.Widget && !obj.__listenersAdded) {
              obj.addListener("changeValue", evt => that.__onChangeTextInput(evt, id));
              obj.addListener("pointerdown", evt => that.__onPointerDown(evt, id));
              obj.__listenersAdded = true;
            }
          } else {
            console.warn("Cannot find id for " + obj);
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
    
    /**
     * Returns the command for this window
     * @return {qx.ui.command.Command}
     */
    getCommand() {
      return this.__cmd;
    },
    
    __getSelector(id) {
      return `[data-qx-object-id="${id}"]`;
    },
    
    __addToScript(line) {
      if (this.recordButton.getValue()) {
        this.textArea.setValue(this.textArea.getValue() + "\n" + line);
      }
    },
    
    __onPointerDown(evt, id) {
      evt.stopPropagation();
      console.log(evt.getOriginalTarget());
      this.__addToScript(`await page.clickByQxId("${id}")`);
    },
  
    __onChangeTextInput(evt, id) {
      if (this.__timerId) {
        clearTimeout(this.__timerId);
      }
      let value = evt.getData();
      this.__timerId = setTimeout(() => {
        this.__addToScript(`await page.fillByQxId("${id}", "${value}");`);
      }, 500);
    }
  }
});
