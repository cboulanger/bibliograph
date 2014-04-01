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
 * Z39.50 Plugin:
 *
 *    This plugin allows to import references from Z39.50 datasources
 *
 */
qx.Class.define("bibliograph.plugin.z3950.Plugin",
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
             * get the item view widget
             */
      var app = qx.core.Init.getApplication();
      var importMenu = app.getWidgetById("importMenu");

      /*
             * add window
             */
      var importWindow = new bibliograph.plugin.z3950.ImportWindowUi();
      app.getRoot().add(importWindow);

      /*
             * add a new menu button
             */
      var menuButton = new qx.ui.menu.Button(this.tr("Import from library catalogue"));
      menuButton.addListener("execute", function() {
        importWindow.show();
      });
      importMenu.add(menuButton);
    }
  }
});

/*
 * initialize plugin
 */
bibliograph.plugin.z3950.Plugin.getInstance().init();
