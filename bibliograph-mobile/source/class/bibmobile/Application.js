/* ************************************************************************

   Copyright:

   License:

   Authors:

************************************************************************ */

/**
 * This is the main application class of your custom application "bibliograph-mobile"
 *
 * @asset(bibmobile/*)
 * @asset(qx/icon/${qx.icontheme}/16/apps/preferences-users.png)
 */
qx.Class.define("bibmobile.Application",
{
  extend : qx.application.Mobile,
  include : [qcl.application.MAppManagerProvider],



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
     */
    main : function()
    {
      // Call super class
      this.base(arguments);

      // Enable logging in debug variant
      if (qx.core.Environment.get("qx.debug"))
      {
        // support native logging capabilities, e.g. Firebug for Firefox
        qx.log.appender.Native;
      }

      /*
       * initialize the managers
       */
      this.initializeManagers();

      /*
       * does what it says
       */
      this.renderUI();

      /*
       * rpc endpoint and timeout
       */
      this.getRpcManager().setServerUrl("../../bibliograph/services/server.php");
      this.getRpcManager().getRpcObject().setTimeout(180000);  //3 Minutes

      /*
       * Setup and start authentication and configuration
       */
      this.getAccessManager().init();
      this.getConfigManager().init();
      this.getAccessManager().setService("bibliograph.access");
      this.getConfigManager().setService("bibliograph.config");

      /*
       * connect to server for authentication
       */
      this.getAccessManager().connect(loadConfiguration, this);

      /*
       * load configuration
       */
      function loadConfiguration()
      {
        qx.event.message.Bus.dispatch(new qx.event.message.Message("connected"));
        this.getConfigManager().load( loadDatasource, this);
      }

      /*
       * load available datasources
       */
      function loadDatasource()
      {
        this.getDatasourceStore().load("getDatasourceListData", [], finalize, this);
      }

      function finalize()
      {

        /*
         * polling service to transport messages and ping server to keep
         * session alive and to clean up dead sessions on the server.
         */
        setInterval(qx.lang.Function.bind(this._pollingService, this), 10000);
      }
    },

    /**
     * render the User Interface
     *
     */
    renderUI : function()
    {
      var page1 = new qx.ui.mobile.page.NavigationPage();
      page1.setTitle("Bibliograph Mobile Client");
      page1.addListener("initialize", function()
      {
        var debuglabel = new qx.ui.mobile.basic.Label(
            window.location.href
        );
        page1.getContent().add(debuglabel);

        var atom = new qx.ui.mobile.basic.Atom("Loading user data...");
        page1.getContent().add(atom);

        this.getApplication().bind("accessManager.userManager.activeUser.fullname", atom, "label");

        var button = new qx.ui.mobile.form.Button("Scan ISBN barcode");
        page1.getContent().add(button);

        button.addListener("tap", function() {
          page2.show();
        }, this);
      },this);

      var page2 = new qx.ui.mobile.page.NavigationPage();
      page2.setTitle("Scan ISBN barcode");
      page2.setShowBackButton(true);
      page2.setBackButtonText("Back");
      page2.addListener("initialize", function()
      {
        var label = new qx.ui.mobile.basic.Label(
            "ISBN barcode scanning requires the Scanner Go Application, which is " +
            "availabe only for iOS (iPhone, iPad & iPod touch). Please click on the " +
            "button below to start.");
        page2.getContent().add(label);

        var button = new qx.ui.mobile.form.Button("Scan ISBN barcode");
        page2.getContent().add(button);

        button.addListener("tap", function()
        {
          var scannerUrl = "ilu://x-callback-url/scanner-go?x-source=Bibliograph&x-success=" +
              window.location.href + "?&sg-result=isbn";
          window.location.href = scannerUrl;
        }, this);
      },this);

      page2.addListener("back", function() {
        page1.show({reverse:true});
      }, this);
      
      // Add the pages to the page manager.
      var manager = new qx.ui.mobile.page.Manager(false);
      manager.addDetail([
        page1,
        page2
      ]);
      
      // Page1 will be shown at start
      page1.show();
    },

    /**
     * Polling service
     * @private
     */
    _pollingService : function() {
      this.getRpcManager().execute("bibliograph.access", "getMessages", [], null, this);
    },

    __datasourceStore : null,

    /**
     * Return the jsonrpc store for the datasource list
     *
     * @return {var} qcl.data.store.JsonRpc
     */
    getDatasourceStore : function()
    {
      if (!this.__datasourceStore)
      {
        this.__datasourceStore = new qcl.data.store.JsonRpc(null, "bibliograph.model", null);
        qx.event.message.Bus.subscribe("reloadDatasources", function() {
          this.__datasourceStore.reload();
        }, this);
      }
      return this.__datasourceStore;
    }
  }
});
