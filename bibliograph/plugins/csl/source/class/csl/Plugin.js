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

/*global qx csl*/

/**
 * CSL Plugin:
 *
 *    This plugin allows to display the currently selected reference(s)
 *    formatted according to a citation style, based on the CSL formatting
 *    language and a compatible citation processor.
 * 
 * @require(csl.*)
 */
qx.Class.define("csl.Plugin",
{
  extend : qx.core.Object,
  include : [qx.locale.MTranslation],
  type : "singleton",
  members : {
    init : function()
    {
      /*
       * get the item view widget
       */
      var app = qx.core.Init.getApplication();
      var itemView = app.getWidgetById("itemView");

      /*
       * add new view to view stack
       */
      var formattedView = new csl.FormattedViewUi();
      itemView.addView("formattedView", formattedView);
      var button = new qx.ui.menubar.Button(this.tr("Formatted View"));
      button.addListener("click", function() {
        itemView.setView("formattedView");
      });
      itemView.getViewByName("tableView").menuBar.add(button);
      var button = new qx.ui.menubar.Button(this.tr("Formatted View"));
      button.addListener("click", function() {
        itemView.setView("formattedView");
      });
      itemView.getViewByName("referenceEditor").menuBar.add(button);
    }
  }
});

