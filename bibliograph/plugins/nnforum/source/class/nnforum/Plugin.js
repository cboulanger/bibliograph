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

/*global qx qcl bibliograph dialog nnforum*/

/**
 * Plugin Initializer Class
 * 
 */
qx.Class.define("nnforum.Plugin",
{
  extend : qx.core.Object,
  include : [qx.locale.MTranslation],
  type : "singleton",
  members : {
    init : function()
    {
      // Manager shortcuts
      var app = qx.core.Init.getApplication();
      var permMgr = app.getAccessManager().getPermissionManager();
      var confMgr = app.getConfigManager();
      
      var helpMenu = app.getWidgetById("application.helpMenu");
      var forumBtn = new qx.ui.menu.Button(this.tr('User Forum'));
      forumBtn.setVisibility("excluded");
      helpMenu.add(forumBtn);
      permMgr.create("nnforum.view").bind("state", forumBtn, "visibility", {
        converter : qcl.bool2visibility
      });      
      forumBtn.addListener("execute", function(e) {
        window.open("../plugins/nnforum/services/www/Forum");
      }, this);    
    }
  }
});

