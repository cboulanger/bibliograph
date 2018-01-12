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
     * Christian Boulanger (cboulanger)

************************************************************************ */

/* ************************************************************************

************************************************************************ */

/**
 * Manager for permissions
 */
qx.Class.define("qcl.access.PermissionManager",
{
  
  extend : qcl.access.AbstractManager,
  type : "singleton",
  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */
  
  construct : function()
  {
    this.base(arguments);
    this._type = "Permission";
  },  

  
  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */
  
  
  members :
  {
//    /**
//     * Creates or returns already created permission with the given named id
//     * @param namedId {String}
//     * @return {qcl.access.Permission}
//     */
//    create : function( namedId )
//    {
//      return this.base(arguments, namedId );
//    }
  }
});
