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
qx.Class.define("qcl.ui.dialog.ServerProgress", {
  extend : qxl.dialog.Progress,
  
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
 
  construct : function(widgetId, service, method) {
    this.base(arguments);
    this.setWidgetId(widgetId);
    this.setService(service || null);
    this.setMethod(method || null);
    this.__iframe = new qx.html.Iframe();
    this.__iframe.hide();
    let app = qx.core.Init.getApplication();
    app.getRoot().getContentElement().add(this.__iframe);
    this.__sourceUrl = app.getServerUrl();
  },

  members :
  {
    __iframe : null,
    __sourceTemplate : "",
    
    /**
     * Start the server method and display progress
     * @param params {Object}
     *    The parameters passed to the controller action as a key-value map, the keys corresponding
     *    to the names of the variables in the method signature
     */
    start : function(params={}) {
      // check parameters
      if (!qx.lang.Type.isObject(params)) {
        this.error("Paramteters must be a map");
      }
    
      // reset
      this.set({
        progress : 0,
        message : "",
        logContent : ""
      });
      
      // format source string
      params.id = this.getWidgetId();
      params.auth_token = qx.core.Init.getApplication().getAccessManager().getToken();
      let source = this.__sourceUrl +
        this.getService() + "/" + this.getMethod() + "&" +
        qx.util.Uri.toParameter(params) + "&nocache=" + Math.random();
      // start request and show dialog
      this.__iframe.setSource(source);
      this.show();
    }
  }
});
