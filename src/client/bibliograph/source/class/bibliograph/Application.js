/* ************************************************************************

   Copyright: 2018 Christian Boulanger

   License: MIT license

   Authors: Christian Boulanger (cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * This is the main application class of "Bibliograph"
 *
 * @asset(bibliograph/*)
 */
qx.Class.define("bibliograph.Application",
{
  extend : qx.application.Standalone,



  /*
  *****************************************************************************
     MEMBERS
  *****************************************************************************
  */

  members :
  {
    /**
     * This method contains the initial application code and gets called 
     * during startup of the application
     * 
     * @lint ignoreDeprecated(alert)
     */
    main : function()
    {
      this.base(arguments);
      if (qx.core.Environment.get("qx.debug"))
      {
        qx.log.appender.Native;
      }

      var button1 = new qx.ui.form.Button("Log in", "bibliograph/test.png");
      var doc = this.getRoot();
      doc.add(button1, {left: 100, top: 50});
      button1.addListener("execute", async function(e) {
        
      });

      // boot sequence
      (async ()=>{
        let setup = bibliograph.Setup.getInstance();
        await setup.authenticate();  
      })();
    }
  }
});