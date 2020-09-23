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
      this.setLayoutProperties({right: 20, top: 50});
    }, this);
    qx.event.message.Bus.getInstance().subscribe(bibliograph.AccessManager.messages.AFTER_LOGOUT, this.close, this);
    // ui
    this.add(new qcl.ui.tool.ObjectIdsView());
    // command
    let cmd = new qx.ui.command.Command("Ctrl+O");
    cmd.addListener("execute", () => {
      this.isVisible() ? this.close() : this.open();
    });
  }
});
