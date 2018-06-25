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

/*global qx csl*/

/**
 * The UI for displaying formatted references
 */
qx.Class.define("bibliograph.ui.item.FormattedViewUi",
{
  extend : bibliograph.ui.item.FormattedView,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
      /*
       * View
       */
      var qxVbox1 = new qx.ui.layout.VBox(null, null, null);
      var qxComposite1 = this;
      this.setLayout(qxVbox1)
      var viewPane = new qx.ui.embed.Html(null);
      this.viewPane = viewPane;
      viewPane.setPadding(5);
      viewPane.setOverflowY("auto");
      viewPane.setSelectable(true);
      qxComposite1.add(viewPane, {
        flex : 1
      });

      /*
       * Menu bar
       */
      var menuBar = new qx.ui.menubar.MenuBar();
      this.menuBar = menuBar;
      menuBar.setHeight(18);
      qxComposite1.add(menuBar);
      
      // Stil
      var qxMenuBarButton1 = new qx.ui.menubar.Button(this.tr('Style'), null, null);
      menuBar.add(qxMenuBarButton1);
      var styleMenu = new qx.ui.menu.Menu();
      this.styleMenu = styleMenu;
      qxMenuBarButton1.setMenu(styleMenu);
      
      // All Results
      var qxMenuBarButton2 = new qx.ui.menubar.Button(this.tr('All Results'), null, null);
      menuBar.add(qxMenuBarButton2);
      qxMenuBarButton2.addListener("click", function(e) {
        this.loadFolder();
      }, this);
      
      // Print
      var qxMenuBarButton3 = new qx.ui.menubar.Button(this.tr('Print'), null, null);
      menuBar.add(qxMenuBarButton3);
      qxMenuBarButton3.addListener("click", function(e) {
        this.getApplication().print(viewPane.getContentElement().getDomElement());
      }, this);
      
      // Tabular View
      var qxMenuBarButton4 = new qx.ui.menubar.Button(this.tr('Tabular View'), null, null);
      menuBar.add(qxMenuBarButton4);
      qxMenuBarButton4.addListener("click", function(e) {
        this.getApplication().getWidgetById("app/item/view").showTabularView();
      }, this);
    }
  }
});
