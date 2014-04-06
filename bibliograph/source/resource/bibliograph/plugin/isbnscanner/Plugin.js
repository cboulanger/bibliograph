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
 * ISBN scanner plugin
 *
 */
qx.Class.define("bibliograph.plugin.isbnscanner.Plugin",
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
       * add a new menu button
       */
      var app = qx.core.Init.getApplication();
      var importMenu = app.getWidgetById("importMenu");
      var menuButton = new qx.ui.menu.Button(this.tr("Scan ISBN barcode with iOS device"));
      menuButton.addListener("execute", function() {
        app.showPopup(this.tr("Please wait ..."));
        app.getRpcManager().execute(
            "bibliograph.plugin.isbnscanner.Service", "confirmEmailAddress", [app.getDatasource()],
        function(data) {
          app.hidePopup();
        }, this);
      });
      importMenu.add(menuButton);
    }
  }
});

/*
 * initialize plugin
 */
bibliograph.plugin.isbnscanner.Plugin.getInstance().init();
