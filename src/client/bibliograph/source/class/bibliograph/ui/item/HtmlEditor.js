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

/*global qx qcl bibliograph htmleditor*/

qx.Theme.include(qx.theme.modern.Appearance, htmleditor.htmlarea.theme.Appearance);

/**
 * A WYSIWYG editor view. Currently not functional and not used.
 * @asset(qx/icon/Tango/16/actions/document-save.png)
 * @asset(qx/icon/Tango/16/actions/application-exit.png)
 */
qx.Class.define("bibliograph.ui.item.HtmlEditor",
{
  extend : htmleditor.HtmlEditor,

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */
  properties :
  {
    /**
     * Function called when the user presses the "save" button
     */
    saveFunction :
    {
      check : "Function",
      nullable : false
    },
    exitFunction :
    {
      check : "Function",
      nullable : false
    }
  },

  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */
  construct : function()
  {
    /*
     * use qooxdoo styles
     */
    var defaultFont = qx.theme.manager.Font.getInstance().resolve("default");
    var styleString = "body { " + qx.bom.element.Style.compile(defaultFont.getStyles()) + "}";
    this.base(arguments, null, styleString);

    /*
     * additional buttons
     */
    var _this = this;
    this.createButton("save",
    {
      caption : "Save the editor content",
      icon : "icon/16/actions/document-save.png",
      handler : function() {
        _this.getSaveFunction()();
      }
    });
    this.createButton("exit",
    {
      caption : "Exit the editor",
      icon : "icon/16/actions/application-exit.png",
      handler : function() {
        _this.getExitFunction()();
      }
    });

    /*
     * create toolbar
     */
    this.createToolbar(["save", "|", "bold", "italic", "underline", "para-styles", "|"//      ,"indent", "outdent", "list-ordered", "list-unordered", "justify", "|"

    //      ,"insert-link", "horiz-ruler","|"

    //      ,"undo", "redo"
    ]);
  },

  /*
  *****************************************************************************
      MEMBERS
  *****************************************************************************
  */
  members : {

  }
});
