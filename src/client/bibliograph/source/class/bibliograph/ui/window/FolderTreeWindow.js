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

/**
 *
 */
qx.Class.define("bibliograph.ui.window.FolderTreeWindow",
{
  extend : qx.ui.window.Window,

  /*
    *****************************************************************************
       EVENTS
    *****************************************************************************
    */
  events : {
    "nodeSelected" : "qx.event.type.Data"
  },

  /*
    *****************************************************************************
       CONSTRUCTOR
    *****************************************************************************
    */

  /**
   * @todo rewrite the cache stuff!
   */
  construct : function() {
    this.base(arguments);
  },

  /*
    *****************************************************************************
       MEMBERS
    *****************************************************************************
    */
  members : {

  }
});
