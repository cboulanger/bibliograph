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

/*global qx z3950*/

/**
 * Z39.50 Plugin:
 *    This plugin allows to import references from Z39.50 datasources
 * 
 * @use(z3950.*)
 */
qx.Class.define("z3950.Plugin",
{
  extend : qx.core.Object,
  include : [qx.locale.MTranslation],
  type : "singleton",
  members : {
    /**
     * TODOC
     *
     * @return {void}
     */
    init : function()
    {
      /*
       * add window
       */
      var app = qx.core.Init.getApplication();
      var importWindow = new z3950.ImportWindowUi();
      app.getRoot().add(importWindow);

      /*
       * add a new menu button
       */
      var importMenu = app.getWidgetById("importMenu");
      var menuButton = new qx.ui.menu.Button(this.tr("Import from library catalog"));
      menuButton.addListener("execute", function() {
        importWindow.show();
      });
      importMenu.add(menuButton);
    }
  }
});