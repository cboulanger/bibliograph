/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2015 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */


/**
 * A mixin for to qx.core.Object that provides central access to widgets by
 * a global id stored at the application instance. Interacts with 
 * qcl.application.MGetApplication and qcl.application.MAppManagerProvider
 */
qx.Mixin.define("qcl.application.MWidgetId",
{
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */  
  properties : 
  {
    widgetId :
    {
      check : "String",
      nullable : true,
      apply : "_applyWidgetId"
    }
  },

  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  members :
  {
    _applyWidgetId : function(value,oldValue)
    {
      this.getApplication().setWidgetById(value,this);
    }
  }
});