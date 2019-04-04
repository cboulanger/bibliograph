/* ************************************************************************

   Copyright: 2018 Christian Boulanger

   License: MIT license

   Authors: Christian Boulanger (cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * This overrides main application class of "Bibliograph" to provide a
 * minimal UI to test things.
 *
 * @asset(bibliograph/*)
 */
qx.Class.define("bibliograph.TestApplication",
{
  extend : qx.application.Standalone,

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

      //  Mixes `getApplication()` into all qooxdoo objects
      qx.Class.include( qx.core.Object, qcl.application.MGetApplication );

      let setup = bibliograph.Setup.getInstance();
      let am = this.getAccessManager();
      let cm = this.getConfigManager();
      
      var doc = this.getRoot();

      var button1 = new qx.ui.form.Button("Log in as Admin", "bibliograph/test.png");
      button1.setEnabled(false);
      doc.add(button1, {left: 100, top: 50});
      button1.addListener("execute", async () => {
        // auth as admin
        let { token } = await this.getRpcClient("access").send('authenticate',['admin','admin']);
        console.info( "token " + token );
        am.setToken(token || null);
        await am.load();
        await cm.load();
      });
      //am.getPermission( 'config.value.edit' ).bind( "state", button1, "enabled" );
      am.getUserManager().bind( 'activeUser.anonymous', button1, "enabled" );

      var button2= new qx.ui.form.Button("Change application title", "bibliograph/test.png");
      button2.setEnabled(false);
      doc.add(button2, {left: 100, top: 100 });
      button2.addListener("execute", async () => {
        cm.setKey(
          "application.title",
          await dialog.Dialog.prompt("Enter application title").promise()
        );
      });
      am.getPermission( 'config.default.edit' ).bind("state", button2, "enabled");
      
      var button3= new qx.ui.form.Button("Logout", "bibliograph/test.png");
      button3.setEnabled(false);
      doc.add(button3, {left: 100, top: 150});
      button3.addListener("execute", async () => {
        am.logout();
      });
      am.getUserManager().bind( 'activeUser.anonymous', button3, "enabled", {
        converter : value => ! value
      });
      am.getUserManager().bind( 'activeUser.username', button3, "label", {
        converter : value => `Logout ${value||""}`
      });

      var label1 = new qx.ui.basic.Label("Loading...");
      doc.add(label1,  {left: 300, top: 90});
      cm.addListener("ready", () =>{
        cm.bindKey("application.title",label1,"value", true);
      });

      /*
       * application startup
       */
      (async ()=>{
        
        await setup.checkServerSetup();
        await setup.authenticate();
        await setup.loadConfig();
        await setup.loadUserdata();
      })();
    }
  }
});
