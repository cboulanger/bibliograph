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
 * Z39.50 Plugin: Import window
 */
qx.Class.define("bibliograph.plugins.z3950.ImportWindow", {
  extend: qx.ui.window.Window,
  construct: function () {
    this.base(arguments);
    this.set({
      width: 700,
      height: 500,
      caption: this.tr("Import from library catalog"),
      showMinimize: false,
      visibility: "excluded",
      layout: new qx.ui.layout.Canvas()
    });
    this.addListener("appear", () => this.center());
    let view = new bibliograph.plugins.z3950.View();
    view.setWindow(this);
    this.add(view, {edge:0});
  }
});
