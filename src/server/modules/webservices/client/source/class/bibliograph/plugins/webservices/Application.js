/* ************************************************************************

   Copyright: 2018 Christian Boulanger

   License: MIT license

   Authors: Christian Boulanger (cboulanger) info@bibliograph.org

************************************************************************ */
/**
 *
 * @include(qxl.dialog.Dialog)
 */
qx.Class.define("bibliograph.plugins.webservices.Application",
{
  extend: qx.application.Standalone,
  members:
  {
    main: function () {
      this.base(arguments);
      if (qx.core.Environment.get("qx.debug")) {
         qx.log.appender.Native;
      }
      qx.Class.include(qx.core.Object, qcl.application.MGetApplication);
      if (!window.opener) {
        qxl.dialog.Dialog.error("The application in the main window cannot be accessed!");
        throw new Error("No main application, aborting.");
      }
      qx.event.message.Bus = window.opener.qx.event.message.Bus;
      let view = new bibliograph.plugins.webservices.View();
      view.setWindow(window);
      this.getRoot().add(view, { edge: 0 });
    }
  }
});
