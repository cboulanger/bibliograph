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

/*global qx qcl bibliograph dialog nnforum*/

/**
 * Plugin Initializer Class
 * @asset(bibliograph/icon/button-mail.png)
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
      
      var helpMenu = app.getWidgetById("bibliograph/helpMenu");
      var forumBtn = new qx.ui.menu.Button(this.tr('User Forum'));
      forumBtn.setVisibility("excluded");
      helpMenu.add(forumBtn);
      permMgr.create("nnforum.view").bind("state", forumBtn, "visibility", {
        converter : qcl.bool2visibility
      });
      forumBtn.addListener("execute", openForumFunc, this); 
      
      // function to open window with forum
      function openForumFunc(e) {
        var url = app.getRpcManager().getServerUrl() +
          "?sessionId=" + app.getSessionManager().getSessionId() +
          "&service=nnforum.service&method=getForumUrl&params=";        
        this.__forumWindow = window.open(url,"bibliograph-forum-window");
        if (!this.__forumWindow) {
          dialog.Dialog.alert(this.tr("Cannot open window. Please disable the popup-blocker of your browser for this website."));
        }
        this.__forumWindow.focus();
      }
      
      // view for display of unread posts
      var searchbox = app.getWidgetById("bibliograph/searchbox");
      var toolbar   = app.getWidgetById("bibliograph/toolbar");
      var unreadPostsView = new qx.ui.basic.Atom("...", "bibliograph/icon/button-mail.png" );
      unreadPostsView.setMarginRight(20);
      unreadPostsView.setMarginLeft(20);
      toolbar.addBefore(unreadPostsView,searchbox);
      unreadPostsView.addListener("click",openForumFunc,this);
      permMgr.create("nnforum.view").bind("state", unreadPostsView, "visibility", {
        converter : qcl.bool2visibility
      });      
      
      // periodically check for new messages and display them
      var checkFunc = function (){
        app.getRpcManager().execute(
          "nnforum.service", "getUnreadPosts", [], 
          function(unreadPosts) {
            if (! unreadPosts ) unreadPostsView.setVisibility( "excluded" );
            unreadPostsView.setLabel( "" + unreadPosts );
            setTimeout(checkFunc, 60000 );
          }, this
        );
      }.bind(this);
      checkFunc();
            

      /*
       * Overlays for preference window
       */
 try{
//       var prefsTabView = app.getWidgetById("bibliograph/preferences-tabView");
//       var pluginTab = new qx.ui.tabview.Page( this.tr('NN-Forum') );
//       //pluginTab.setVisibility("excluded");
      
      
//       //prefsTabView.add(pluginTab);
      
//       // ACL
//       permMgr.create("nnforum.view").bind("state", pluginTab, "visibility", {
//         converter : function(v){ return v ? "visible" : "excluded" }
//       });
//       var vboxlayout = new qx.ui.layout.VBox(5);
//       pluginTab.setLayout(vboxlayout);

//       // option grid
//       var gridlayout = new qx.ui.layout.Grid();
//       gridlayout.setSpacing(5);
//       pluginTab.setLayout(gridlayout);
//       gridlayout.setColumnWidth(0, 200);
//       gridlayout.setColumnFlex(1, 2);

//       var msg= this.tr("Domain/Site for Google Search");
//       var label1 = new qx.ui.basic.Label(msg);
//       label1.setRich(true);
//       pluginTab.add(label1,{row : 0, column : 0 });

//       var textField = new qx.ui.form.TextField();
//       pluginTab.add(textField,{ row : 0, column : 1 });

//       // bind to preference
//       confMgr.addListener("ready", function() {
//         confMgr.bindKey("nnforum.searchdomain", textField, "value", true);
//       });
      

 }catch(e){
   console.warn(e);
 }      
      
    }
  }
});

