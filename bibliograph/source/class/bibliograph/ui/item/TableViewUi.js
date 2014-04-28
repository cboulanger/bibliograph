/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2014 Christian Boulanger
 *
 * License: LGPL: http://www.gnu.org/licenses/lgpl.html EPL:
 * http://www.eclipse.org/org/documents/epl-v10.php See the LICENSE file in the
 * project's top-level directory for details.
 *
 * Authors: Christian Boulanger (cboulanger)
 *
 ******************************************************************************/

/*global qx qcl bibliograph*/

/**
 * The table view of the reference
 */
qx.Class.define("bibliograph.ui.item.TableViewUi",
{
  extend : bibliograph.ui.item.TableView,
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
      this.getApplication().addListener("changeModelId", this.loadHtml, this);
      viewPane.addListener("appear", this._on_appear, this);
      var menuBar = new qx.ui.menubar.MenuBar();
      this.menuBar = menuBar;
      menuBar.setHeight(18);
      qxComposite1.add(menuBar);
      var qxMenuBarButton1 = new qx.ui.menubar.Button(this.tr('Print'), null, null);
      qxMenuBarButton1.setEnabled(false);
      qxMenuBarButton1.setLabel(this.tr('Print'));
      menuBar.add(qxMenuBarButton1);
      qxMenuBarButton1.addListener("click", function(e) {
        //
      }, this);
    }
  }
});
