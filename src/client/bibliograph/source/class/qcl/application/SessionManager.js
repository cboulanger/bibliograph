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

/**
 * 
 */
qx.Class.define("qcl.application.SessionManager",
{
  extend : qx.core.Object,
  
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties :
  {
    /** 
     * The current session id, unique for each browser window in which an application
     * instance exists.
     */
    sessionId :
    {
      check : "String",
      nullable : true,
      event : "changeSessionId",
      apply : "_applySessionId"
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

    /*
     * subscribe to a message that usually comes from the server to set
     * a new session id
     */
    qx.event.message.Bus.subscribe( "setSessionId", function( e ){
      this.setSessionId( e.getData() );
    }, this);
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

    
    /* 
    ---------------------------------------------------------------------------
       PRIVATE MEMBERS
    ---------------------------------------------------------------------------
    */       

    
    /* 
    ---------------------------------------------------------------------------
       APPLY METHODS
    ---------------------------------------------------------------------------
    */   
    _applySessionId : function( sessionId, old )
    {
      this.info("Session id changed from " + old + " to " + sessionId);
    }
  }
});