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
 *    top.qx.core.Id.getQxObject("(object id)").set({
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
     * The route name
     */
    route :
    {
      check : "String",
      nullable : false
    }
  },
  
  /**
   * Constructor
   * @param {String} objectId An arbitrary string (must not contain a slash) which
   * will be registered as a top-level widget
   * @param {String} route The route to the server action that returns the chunked http response
   */
  construct : function(objectId, route) {
    this.base(arguments);
    if (!objectId.match(/^[a-zA-Z0-9-]+$/)) {
      throw new Error(`Invalid object id "${objectId}"`);
    }
    this.setQxObjectId(objectId);
    qx.core.Id.getInstance().register(this);
    this.setRoute(route);
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
      const app = qx.core.Init.getApplication();
      params.id = this.getQxObjectId();
      params.auth_token = app.getAccessManager().getToken();
      let source = this.__sourceUrl + "/" + this.getRoute() + app.formatParams(params);
      // start request and show dialog
      this.__iframe.setSource(source);
      this.show();
    }
  },
  
  /**
   * On dispose, unregister this object
   */
  destruct: function() {
    qx.core.Id.getInstance().unregister(this);
  }
});
