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

/**
 * A mixin for to qx.core.Object that provides a shortcut
 * to the application instance
 */
qx.Mixin.define("qcl.application.MGetApplication",
{
  members :
  {
    getApplication : function()
    {
      return qx.core.Init.getApplication();
    }
  }
});