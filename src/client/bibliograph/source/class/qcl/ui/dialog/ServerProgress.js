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
 * Use the lib\dialog\ServerProgress PHP class to produce this output.
 */
qx.Class.define("qcl.ui.dialog.ServerProgress", {
  extend : qxl.dialog.Progress,
  
  properties :
  {
    /**
     * The route name
     */
    route: {
      check : "String",
      nullable : false
    },
  
    /**
     * What to do if the server sends an message event: a) show a dialog, b)
     * ignore the message and let client code handle it
     */
    messageBehavior: {
      check: ["dialog", "ignore"],
      init: "dialog"
    },
    
    /**
     * What to do if the server sends an error event: a) show a dialog, b)
     * ignore the error and let client code handle it, c) throw an error
     */
    errorBehavior: {
      check: ["dialog", "ignore", "error"],
      init: "dialog"
    }
  },
  
  events:{
    /**
     * An arbitrary message passed by the server
     */
    "message": "qx.event.type.Data",
    
    /**
     * An error event sent by the server
     */
    "error": "qx.event.type.Data",
  
    /**
     * Fired when the Progress job has successfully completed
     */
    "done": "qx.event.type.Event"
  },
  
  /**
   * Constructor
   * @param {String} objectId An arbitrary string (must not contain a slash) which
   * will be registered as a top-level widget
   * @param {String?} route The route to the server action that returns the chunked http response
   */
  construct : function(objectId, route) {
    this.base(arguments);
    if (!objectId.match(/^[a-zA-Z0-9-]+$/)) {
      throw new Error(`Invalid object id "${objectId}"`);
    }
    this.setQxObjectId(objectId);
    qx.core.Id.getInstance().register(this);
    if (route) {
      this.setRoute(route);
    }
    this.__iframe = new qx.html.Iframe();
    this.__iframe.hide();
    let app = qx.core.Init.getApplication();
    app.getRoot().getContentElement().add(this.__iframe);
    this.__sourceUrl = app.getServerUrl();
  
    // errors
    this.addListener("message", e => {
      let message = e.getData();
      switch (this.getMessageBehavior()) {
        case "dialog":
          qxl.dialog.Dialog.alert(message);
          break;
      }
    });
    
    // errors
    this.addListener("error", e => {
      let message = e.getData();
      switch (this.getErrorBehavior()) {
        case "dialog":
          qxl.dialog.Dialog.error(message);
          break;
        case "error":
          throw new Error(message);
      }
    });
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
      params["access-token"] = app.getAccessManager().getToken();
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
