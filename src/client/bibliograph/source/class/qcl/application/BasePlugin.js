/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2010 Christian Boulanger
  
   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.
  
   Authors:
   *  Christian Boulanger (cboulanger)
  
************************************************************************ */

/*global bibliograph qx qcl dialog*/

/**
 * Plugin Base Class
 */
qx.Class.define("qcl.application.BasePlugin",
{
  extend : qx.core.Object,

  properties :
  {
    enabled :
    {
      check : "Boolean",
      init  : "true"
    }
  },
  
  construct : function()
  {  
    this.base(arguments); 
  },

  members :
  {

    /**
     * Returns the name of the plugin
     * @returns {string}
     */
    getName : function()
    {
      throw new Error("Plugin must provide a name");
    }

  }
});