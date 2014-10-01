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
/*global qx qcl dialog*/

/**
 * A dialog for monitoring the progress of a task that runs on the server and 
 * outputs a chunked http response with script tags of like this:
 * <pre>
 * <script type="text/javascript">
 *    top.qx.core.Init.getApplication()
 *     .getWidgetById("(widget id)").set({
 *        progress:(progress in percent, integer),
 *        message:"(optional message)"
 *      });
 *  </script>
 * </pre>
 * Use the qcl_ui_dialog_ServerProgress PHP class to produce this output.
 */
qx.Class.define("qcl.ui.dialog.ServerProgress",
{
  extend : dialog.Progress,
  
   /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */     
  properties :
  {
    /**
     * The service name
     */
    service :
    {
      check : "String",
      nullable : true,
      event : "changeService"
    },
    
    /**
     * The method name
     */
    method :
    {
      check : "String",
      nullable : true,
      event : "changeMethod"
    }
  },
 
   /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */     
  construct : function( widgetId, service, method )
  {
    this.base(arguments);
    this.setWidgetId( widgetId );
    qcl["__"+widgetId]=this;
    this.setService( service || null );
    this.setMethod( method || null );
    this.__iframe = new qx.html.Iframe();
    this.__iframe.hide();
    var app = qx.core.Init.getApplication();
    app.getRoot().getContentElement().add(this.__iframe);
    this.__sourceTemplate = app.getRpcManager().getServerUrl();
    this.__sourceTemplate += "?service=%1&method=%2&params=%3&sessionId=%4&nocache=%5";    
  }, 
  
  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */     
  members :
  {
    __iframe : null,
    __sourceTemplate : "",
    
    /*
    ---------------------------------------------------------------------------
       API METHODS
    ---------------------------------------------------------------------------
    */
    
    /**
     * Start the server method and display progress
     */
    start : function(params)
    {
      // reset
      this.set({
        progress : 0,
        message : "",
        logContent : ""
      });
      
      // format source string
      var source = qx.lang.String.format(
        this.__sourceTemplate, [
          this.getService(),
          this.getMethod(),
          params || "",
          qx.core.Init.getApplication().getSessionManager().getSessionId(),
          (new Date()).getTime() 
        ]
      );
      
      // start request and show dialog
      this.__iframe.setSource( source );
      this.show();
    }
  }  
});