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
 * A dialog for authentication and login
 */
qx.Class.define("qcl.ui.dialog.Login",
{
  extend : qxl.dialog.Login,

  construct : function() {
    this.base(arguments);
    this.setCheckCredentials(async (username, password, callback) => {
      let accessManager = qx.core.Init.getApplication().getAccessManager();
      let response = await accessManager.authenticate(username, password, true);
      let { message, token, sessionId, error } = response;
      if (error) {
        this.warn(error);
        return;
      }
      this.info(message);
      accessManager.setToken(token || null);
      accessManager.setSessionId(sessionId);
      // this calls the server method
      callback();
    });
  }
});
