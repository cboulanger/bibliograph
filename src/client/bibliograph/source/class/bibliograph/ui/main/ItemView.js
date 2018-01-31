/*******************************************************************************
 *
 * Bibliograph: Online Collaborative Reference Management
 *
 * Copyright: 2007-2015 Christian Boulanger
 *
 * License: LGPL: http://www.gnu.org/licenses/lgpl.html EPL:
 * http://www.eclipse.org/org/documents/epl-v10.php See the LICENSE file in the
 * project's top-level directory for details.
 *
 * Authors: Christian Boulanger (cboulanger)
 *
 ******************************************************************************/
/*global qx qcl*/
/**
 * Base class for Table widgets
 */
qx.Class.define("bibliograph.ui.main.ItemView",
{
  extend : qx.ui.container.Composite,

  /*
   *****************************************************************************
     PROPERTIES
   *****************************************************************************
   */
  properties :
  {
    /**
     * The currently active item view
     */
    view :
    {
      check : "String",
      nullable : true,
      event : "changeView",
      apply : "_applyView"
    },

    /**
     * The datasource of the items displayed
     */
    datasource :
    {
      check : "String",
      nullable : true,
      event : "changeDatasource",
      apply : "_applyDatasource"
    }
  },

  /*
  *****************************************************************************
      CONSTRUCTOR
  *****************************************************************************
  */
  construct : function()
  {
    this.base(arguments);
    this._itemViews = {};
  },

  /*
  *****************************************************************************
    MEMBERS
  *****************************************************************************
  */
  members :
  {
    /*
    ---------------------------------------------------------------------------
       WIDGETS
    ---------------------------------------------------------------------------
    */
    itemViewStack : null,

    /**
     * Getter for view stack
     * @return {qx.ui.container.Stack}
     */
    getItemViewStack : function() {
      return this.itemViewStack;
    },

    /*
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */
    _applyDatasource : function(value, old) {
    },

    /**
     * Displays the item view of the given name. If the name contains a plus sign,
     * use the part before the plus sign as item view name, the part after as
     * the name of a subview.
     *
     * @param value
     *            {String}
     * @param old
     *            {String|null}
     * @return {void}
     */
    _applyView : function(value, old)
    {
      if (value) {
        var subView;
        if( value.indexOf("-") !== -1 ) {
          var parts = value.split("-");
          value = parts[0];
          subView = parts[1];
        }
        var itemViewWidget = this.getViewByName(value);
        if (itemViewWidget) {
          this.itemViewStack.setSelection([itemViewWidget]);
          if( subView ) {
            if( typeof itemViewWidget.setPage == "function" ) {
              itemViewWidget.setPage(subView);
            }
          }
        } else {
          this.warn("Invalid item view name " + value);
          return; 
        }
      } else  {
        this.getItemViewStack().setSelection([]);
      }
    },

    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * Programmatically adds a view widget with the given name.
     * @param name {String}
     * @param widget {qx.ui.core.Widget}
     */
    addView : function(name, widget)
    {
      widget.setUserData("name", name);
      this.getItemViewStack().add(widget);
    },

    /**
     * Returns the currently visible item view widget
     */
    getCurrentView : function() {
      return this.getItemViewStack().getSelection()[0];
    },

    /**
     * Returns the view identified by the userdata "name" value
     */
    getViewByName : function(name)
    {
      var children = this.getItemViewStack().getChildren();
      for (var i = 0; i < children.length; i++) {
        if (children[i].getUserData("name") == name) {
          return children[i];
        }
      }
      return null;
    },

    /**
     * Toggles the reference view according to the givne
     * permissions. Switches only if tabular view is
     * already showing
     */
    toggleReferenceView : function() {
      if (!this.getView() || this.getView() == "referenceEditor" || this.getView() == "tableView") {
        this.showTabularView();
      }
    },

    /**
     * Shows the tabular view according to the given permissions
     *
     */
    showTabularView : function()
    {
      var type = this.getApplication().getModelType();
      switch (type)
      {
        default:
          var allowEditReference = qcl.access.PermissionManager.getInstance().create("reference.edit").getState();
          this.setView(allowEditReference ? "referenceEditor" : "tableView");
      }
    },

    /**
     * Prints the current item view
     */
    print : function() {
      
      // try {
      //   switch (this.getItemView())
      //   {
      //     case "noteEditor":
      //       // if ( this.getVisibility()!='visible' ) return;
      //       var win = window.open();
      //       var itemView = this.getCurrentView();
      //       var content = itemView.view.getVisibility() == "visible" ? itemView.view.getHtml() : itemView.editor.getValue();
      //       var title = itemView.store.getModel().getTitle();
      //       win.document.write("<h1>" + title + "</h1>");
      //       win.document.write(content);
      //       var defaultFont = qx.theme.manager.Font.getInstance().resolve("default");
      //       var styleString = "body { " + qx.bom.element.Style.compile(defaultFont.getStyles()) + "; padding-left: 100px; padding-right: 100px;" + "}";
      //       var styleNode = win.document.createElement('style');
      //       var titleNode = win.document.createElement('title');
      //       styleNode.setAttribute("type", "text/css");
      //       if (styleNode.styleSheet)
      //       {  // IE
      //         styleNode.styleSheet.cssText = def;
      //         titleNode.text = title
      //       } else
      //       {  // the world
      //         styleNode.appendChild(document.createTextNode(styleString));
      //         titleNode.appendChild(document.createTextNode(title));
      //       }
      //       win.document.getElementsByTagName('head')[0].appendChild(styleNode);
      //       win.document.getElementsByTagName('head')[0].appendChild(titleNode);
      //       win.document.title = title;
      //       win.stop();
      //       win.print();
      //       break;
      //   }
      // }catch (e) {
      //   alert(e);
      // }
    }
  }
});
