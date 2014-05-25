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
 * Renderer for {@link qx.ui.form.Form}.
 */
qx.Class.define("bibliograph.ui.item.FormRenderer",
{
  extend : qx.ui.form.renderer.Single,
  implement : qx.ui.form.renderer.IFormRenderer,
  construct : function(form)
  {
    this.base(arguments, form);
    var layout = this.getLayout();

    /*
     * create 4-column layout with two fixed label
     * columns and two flexible columns
     */
    layout.setColumnWidth(0, 100);
    layout.setColumnFlex(0, 0);
    layout.setColumnFlex(1, 1);
    layout.setColumnWidth(2, 100);
    layout.setColumnAlign(2, "right", "top");
    layout.setColumnFlex(3, 1);
  },
  members : {
    /**
     * Add a group of form items with the corresponding names. The names are
     * displayed as label.
     * The title is optional and is used as grouping for the given form
     * items.
     *
     * @param items {qx.ui.core.Widget[]} An array of form items to render.
     * @param names {String[]} An array of names for the form items.
     * @param title {String?} A title of the group you are adding.
     */
    addItems : function(items, names, title)
    {
      /*
       * add the header
       */
      if (title != null)
      {
        this._add(this._createHeader(title),
        {
          row : this._row,
          column : 0,
          colSpan : 4
        });
        this._row++;
      }

      /*
       * add the items. if a field should take the full width
       * of the form, userData.fullWidth is set to true
       */
      var column = 0;
      for (var i = 0; i < items.length; i++)
      {
        var item = items[i];
        var label = this._createLabel(names[i], item);
        if (item.getUserData('fullWidth'))
        {
          if (column == 2)
          {
            this._row++;
            column = 0;
          }
          this._add(label,
          {
            row : this._row,
            column : 0
          });
          this._add(item,
          {
            row : this._row,
            column : 1,
            colSpan : 3
          });
          this._row++;
        } else
        {
          this._add(label,
          {
            row : this._row,
            column : column
          });
          this._add(item,
          {
            row : this._row,
            column : column + 1
          });
          column += 2;
          if (column == 4)
          {
            column = 0;
            this._row++;
          }
        }
      }
    }
  }
});
