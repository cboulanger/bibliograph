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
 * The UI for displaying formatted references
 */
qx.Class.define("bibliograph.plugin.csl.FormattedViewUi",
{
  extend : bibliograph.plugin.csl.FormattedView,
  construct : function()
  {
    this.base(arguments);
    this.__qxtCreateUI();
  },
  members : {
    __qxtCreateUI : function()
    {
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

      this.getApplication().addListener("changeSelectedIds", this.loadHtml, this);

      viewPane.addListener("appear", this._on_appear, this);
      var menuBar = new qx.ui.menubar.MenuBar();
      this.menuBar = menuBar;
      menuBar.setHeight(18);
      qxComposite1.add(menuBar);
      var qxMenuBarButton1 = new qx.ui.menubar.Button(this.tr('Style'), null, null);
      qxMenuBarButton1.setLabel(this.tr('Style'));
      menuBar.add(qxMenuBarButton1);
      var styleMenu = new qx.ui.menu.Menu();
      this.styleMenu = styleMenu;
      qxMenuBarButton1.setMenu(styleMenu);
      var qxMenuBarButton2 = new qx.ui.menubar.Button(this.tr('All in folder'), null, null);
      qxMenuBarButton2.setLabel(this.tr('All in folder'));
      menuBar.add(qxMenuBarButton2);
      qxMenuBarButton2.addListener("click", function(e) {
        this.loadFolder();
      }, this);
      var qxMenuBarButton3 = new qx.ui.menubar.Button(this.tr('Print'), null, null);
      qxMenuBarButton3.setLabel(this.tr('Print'));
      menuBar.add(qxMenuBarButton3);
      qxMenuBarButton3.addListener("click", function(e) {
        this.getApplication().print(viewPane.getContentElement().getDomElement());
      }, this);
      var qxMenuBarButton4 = new qx.ui.menubar.Button(this.tr('Tabular View'), null, null);
      qxMenuBarButton4.setLabel(this.tr('Tabular View'));
      menuBar.add(qxMenuBarButton4);
      qxMenuBarButton4.addListener("click", function(e) {
        this.getApplication().getWidgetById("itemView").showTabularView();
      }, this);
    }
  }
});
