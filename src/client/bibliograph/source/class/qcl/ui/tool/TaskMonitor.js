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
 * Window with a task monitor
 */
qx.Class.define("qcl.ui.tool.TaskMonitor",
{
  extend : qx.ui.window.Window,
  type: "singleton",
  construct : function() {
    this.base(arguments);
    this.set({
      caption: "Task Monitor",
      layout: new qx.ui.layout.Grow(),
      showMaximize: false,
      showMinimize: false,
      width: 350,
      height: 150
    });
    // position when first shown
    this.addListenerOnce("appear", () => {
      this.set();
      this.setLayoutProperties({right: 50, top: 150});
    }, this);
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, this.close, this);
    let list = new qx.ui.list.List();
    var delegate = {
      // create a list item
      createItem : function() {
        let container = new qx.ui.container.Composite(new qx.ui.layout.HBox(5));
        container.add(new qx.ui.basic.Atom(), {flex:1});
        container.add(new qx.ui.indicator.ProgressBar().set({width:50}));
        return container;
      },
      bindItem : function(controller, item, id) {
        // bind label
        controller.bindProperty("name", "label", {}, item.getChildren()[0], id);
        // bind progress if any
        controller.bindProperty("progress", "visibility", {
          converter: value => value === null ? "excluded" : "visible"
        }, item.getChildren()[1], id);
        controller.bindProperty("progress", "value", {
          converter: value => Number(value)
        }, item.getChildren()[1], id);
        // show inactive tasks as disabled
        controller.bindProperty("active", "enabled", {}, item, id);
      }
    };
    list.setDelegate(delegate);
    const tm = this.getApplication().getTaskMonitor();
    list.setModel(tm.getTasks());
    this.add(list);
    
    // command
    let cmd = this.__cmd = new qx.ui.command.Command("Ctrl+M");
    cmd.addListener("execute", () => {
      this.isVisible() ? this.close() : this.open();
    });
  },
  members: {
    
    /** @var qx.ui.command.Command */
    __cmd : null,
    
    /**
     * Returns the command for this window
     * @return {qx.ui.command.Command}
     */
    getCommand() {
      return this.__cmd;
    }
  }
});
