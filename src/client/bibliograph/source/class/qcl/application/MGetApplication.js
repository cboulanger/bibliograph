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
 * A mixin for to qx.core.Object that provides a shortcut
 * to the application instance and its message bus
 */
qx.Mixin.define("qcl.application.MGetApplication",
{
  members :
  {
    /**
     * Return the main application instance. In dependend popup-windows with separately loaded
     * applications, this will return the application instance in the main window.
     * @return {qx.application.Standalone}
     */
    getApplication: function()
    {
      if( window.opener ){
        return  window.opener.qx.core.Init.getApplication();
      }
      return qx.core.Init.getApplication();
    },
  
    /**
     * Return the main application's message bus. In dependend popup-windows with separately loaded
     * applications, this will return the message bus of the main window.
     * @return {qx.event.message.Bus}
     */
    getMessageBus:  function(){
      if( window.opener ){
        return  window.opener.qx.event.message.Bus.getInstance();
      }
      return qx.event.message.Bus.getInstance();
    }
  }
});