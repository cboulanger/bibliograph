/* ************************************************************************

   qooxdoo - the new era of web development

   http://qooxdoo.org

   Copyright:
     2008 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
     * Chritian Boulanger (cboulanger)

************************************************************************ */

/* ************************************************************************

#module(ui_table)

************************************************************************ */

/**
 * A template class for cell renderers, which display links.
 */
qx.Class.define("qcl.ui.table.cellrenderer.Link",
{
  extend : qx.ui.table.cellrenderer.Abstract,


  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  construct : function()
  {
    this.base(arguments);
    this.IMG_LINK = qx.io.Alias.getInstance().resolve("icon/16/places/folder-remote.png");
 
     /**   
    // the following doesn't work
    var clazz = this.self(arguments);

    if (!clazz.stylesheet)
    {
      clazz.stylesheet = qx.html.StyleSheet.createElement(
        ".qooxdoo-table-cell-link {" +
        "  padding-left: 20px;" +
        "  background: url(" + this.IMG_LINK + ") top left no-repeat;" +
        "}"
      );
    }**/
  },

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */

  members :
  {
 
    // overridden
    _getCellClass : function(cellInfo) {
      return this.base(arguments) + " qooxdoo-table-cell-link";
    },


    // overridden
    _getContentHtml : function(cellInfo)
    {
      /**
      var content = [];
      if ( cellInfo.value )
      {
        
        // because the stylesheet/class stuff doesn't work, do it manually
        content.push('<div style="padding-left: 20px; background: url(' );  
        content.push(this.IMG_LINK);
        content.push(') top left no-repeat;">');
        
        // however, clicking on the link won't trigger the window.open command
        content.push('<a href="javascript:void()" ');
        content.push('onclick="window.open(\'' + cellInfo.value + '\');";');
        content.push(">");
        content.push(cellInfo.value)
        content.push("</a>");  
        
        content.push("</div>");
       
       
      }
      return content.join("");
      **/
      content = cellInfo.value || "";
      return content.replace(/(http:\/\/[\w\-\.]+)([^\s;]*)/g, '<a target="_blank" href="$1$2">$1/...<\/a>');
   
    }
  }
});
