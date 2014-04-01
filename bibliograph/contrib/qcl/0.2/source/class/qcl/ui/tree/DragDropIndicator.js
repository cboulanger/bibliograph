/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2014 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/* ************************************************************************

************************************************************************ */

/**
 * An indicator for drag & drop operations
 */
qx.Class.define("qcl.ui.tree.DragDropIndicator",
{
  extend : qx.ui.core.Widget,
  
  construct : function()
  {
    this.base(arguments);
    this.setDecorator(new qx.ui.decoration.Single().set({
        top : [ 1, "solid", "#33508D" ]
    }));
    this.setHeight(0);
    this.setOpacity(0.5);
    this.setZIndex(1E7);
    this.setLayoutProperties({left: -1000, top: -1000});
    //this.setDroppable(true);
    qx.core.Init.getApplication().getRoot().add(this);    
  },
  members:
  {
    /**
     * Displays the indicator on the top of the current drop target. Takes
     * the drag event as argument.
     * 
     * @param e {qx.event.type.Drag}
     */
    display: function( e )
    {
      /*
       * don't show indicator when widget is not droppable
       */
      if ( ! e.getRelatedTarget() || ! e.getRelatedTarget().isDroppable() )
      {
        this.hide();
        return;
      }
      
      /*
       * get the current drop target
       */
      var target =  this.getDropTarget( e.getOriginalTarget() );
      if ( ! target ) 
      {
        //this.hide();
        return;
      }
      
      /*
       * show indicator 
       */
      var c = target.getContainerLocation();
      this.setWidth( target.getBounds().width );
      this.setDomPosition( c.left, c.top);
      this.show();
    },
    
    /**
     * Returns the real drop target or null if none can be determined.
     * @param target {qx.ui.core.Widget}
     * @return {qx.ui.core.Widget}
     */
    getDropTarget : function( target )
    {
      do 
      {
        var found = false;
        switch ( target.classname )
        {
          case "qx.ui.tree.TreeFile":
          case "qx.ui.tree.TreeFolder":
          case "qx.ui.form.ListItem":
            found = true;
            break;
            
          case "qcl.ui.DragDropIndicator":
            return null;
            
          // go up in the widget hierarchy to find real target 
          default:
            target = target.getLayoutParent();
            if ( ! target ) return null;
        }
      } 
      while ( ! found );
      return target;
    }
  }
});