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
 * A wizard-type widget that constructs the wizard pages on-the-fly, using 
 * functionality from qcl.ui.dialog.Form. In contrast to qcl.ui.dialog.Wizard,
 * this wizard sends each page result back to the server and gets new page data
 */
qx.Class.define("qcl.ui.dialog.RemoteWizard",
{
  extend : dialog.Wizard,
  
  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */     
  properties :
  {
    serviceName : 
    {
      check : "String",
      nullable : false
    },
    
    serviceMethod : 
    {
      check : "String",
      nullable : false
    }
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
       API METHODS
    ---------------------------------------------------------------------------
    */
    
    /**
     * Goes to the previous wizard button
     */
    goBack : function()
    {
      var page = this.getPage(); 
      if ( page == 0 )
      {
        this.error("Cannot go back!");
      }
      this.getApplication().executeService(
        this.getServiceName(),
        this.getServiceMethod(),
        [ page-1, qx.util.Serializer.toJson( this.getModel() ) ],
        function ( data ){
          this.set( data );
        },
        this  
      );
    },

    /**
     * Goes to the next wizard page
     */
    goForward : function()
    {
      var page = this.getPage(); 
      if ( page > this.getPageData().length -2  )
      {
        this.error("Cannot go forward!");
      }
      this.getApplication().executeService(
        this.getServiceName(),
        this.getServiceMethod(),
        [ page+1, qx.util.Serializer.toJson( this.getModel() ) ],
        function ( data ){
          this.set( data );
        },
        this  
      );
    },    
    
    /** 
     * Finishes the wizard. Calls callback with the result data map
     * @return {Object}
     */
    finish : function()
    {
      this.hide();
      this.getApplication().executeService(
        this.getServiceName(),
        this.getServiceMethod(),
        [ null, qx.util.Serializer.toJson( this.getModel() ) ]
      );
    }
  }    
});