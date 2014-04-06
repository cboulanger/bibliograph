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
   PROPERTIES
   *****************************************************************************
   */

  properties :
  {
    /**
     * An isbn that has been scanned in
     */
    isbn :
    {
      check : "String",
      nullable : true,
      event : "changeIsbn",
      apply : "_applyIsbn"
    },

    /**
     * The current datasource
     */
    datasource :
    {
      check : "String",
      nullable : true,
      event : "changeDatasource"
    }
  },

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
       * save information from the hash in the session storage
       */
      var localStorage = qx.bom.storage.Web.getLocal();
      var stateManager = this.getStateManager();
      if( stateManager.getState("datasource") )
      {
        var ds = stateManager.getState("datasource");
        localStorage.setItem("bibliograph.datasource", ds);
        this.setDatasource(ds);
      }
      else if (localStorage.getItem("bibliograph.datasource") )
      {
        this.setDatasource(localStorage.getItem("bibliograph.datasource") );
      }

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
       * does what it says
       */
      this.renderUI();

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
         * FIXME the polling interval must decrease with the number of connected sessions
         * to avoid unneccarily heavy server load - us a config value for this.
         */
        setInterval(qx.lang.Function.bind(this._pollingService, this), 10000);

        /*
         * if the app is called from Scanner Go, go to the page that handles the scanned isbn
         */
        this.getStateManager().updateState();
        if( this.getIsbn() )
        {
          this.getPage(2).show();
        }
      }
    },

    /**
     * The UI pages
     */
    __pages :[null],

    /**
     * Adds a page to the page registry
     * @param page
     */
    addPage : function(page)
    {
      this.__pages.push(page);
    },

    /**
     * The pages of the application
     * @returns {array}
     */
    getPages : function()
    {
      return this.__pages;
    },

    /**
     * Returns the page with the given index.
     * @param {int} index
     * @returns {var} qx.ui.mobile.page.NavigationPage
     */
    getPage : function(index)
    {
      return this.__pages[index];
    },


    /**
     * render the User Interface
     *
     */
    renderUI : function()
    {
      var permissionMgr = this.getAccessManager().getPermissionManager();

      /*
       * Start page
       */
      var page1 = new qx.ui.mobile.page.NavigationPage();
      page1.setTitle("Bibliograph Mobile Client");
      this.addPage(page1);
      page1.addListener("initialize", function()
      {
        var app = this;
        var content = page1.getContent();
        
        var debuglabel = new qx.ui.mobile.basic.Label(
            window.location.href
        );
        //content.add(debuglabel);

        var logo = new qx.ui.mobile.basic.Image("bibmobile/bibliograph-logo-text.png");
        content.add( logo ,{
          alignX : "center"
        } );

        var button = new qx.ui.mobile.form.Button();
        button.setEnabled(false);
        this.getApplication().bind("accessManager.userManager.activeUser.fullname", button, "label", {
          converter : (function(name){
            var activeUser = this.getAccessManager().getActiveUser();
            return ( activeUser && ! activeUser.isAnonymous() ) ?
                this.tr("Logout %1", name) : this.tr("Login");
          }).bind(this)
        });
        content.add(button);
        button.addListener("tap", function() {
            alert("Not implemented.");
        }, this);

        var button = new qx.ui.mobile.form.Button("Scan ISBN Barcode");
        button.addListener("tap", this.scanIsbn,this);
        content.add(button);

      },this);

      /*
       * page which is shown when isbn has been scanned in.
       */
      var page2 = new qx.ui.mobile.page.NavigationPage();
      this.addPage(page2);
      page2.setTitle("ISBN barcode");
      page2.setShowBackButton(true);
      page2.setBackButtonText("Back");
      page2.addListener("initialize", function()
      {
        var label = new qx.ui.mobile.basic.Label("Loading...");
         page2.getContent().add(label);

        function updateLabel()
        {
          var text = this.tr("Scanned ISBN:") + " " + this.getIsbn() + "<br/>";
          text    += this.tr("Datasource:") + " " + this.getDatasourceLabel( this.getDatasource() );
          label.setValue(text);
        }
        updateLabel.call(this);
        this.addListener("changeIsbn", updateLabel, this);
        this.getDatasourceStore().addListener("changeModel", updateLabel, this);

        var button = new qx.ui.mobile.form.Button(this.tr("Import"));
        page2.getContent().add(button);
        button.setVisibility("excluded");
        permissionMgr.create("reference.import")
            .bind("state", button, "visibility", {converter : qcl.bool2visibility});

        button.addListener("tap", function(){
          this.importReferenceByIsbn( this.getIsbn(), this.getDatasource() );
        },this);

      }, this);

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
     * redirect to scanner go app
     */
    scanIsbn : function()
    {
      var targetUrl = window.location.href;
      for( var i=0; i<2; i++)
      {
        targetUrl  = targetUrl.substring( 0, targetUrl.lastIndexOf( "/" ) );
      }
      targetUrl += "/index.php"
      var scannerUrl = "ilu://x-callback-url/scanner-go?x-source=Bibliograph&x-success=" +
          targetUrl + "?&sg-result=isbn";
      window.location.href = scannerUrl;
    },

    /**
     * import the data referenced by the isbn into the datasource on the server
     * @param {string} isbn
     * @param {string} datasource
     */
    importReferenceByIsbn : function(isbn, datasource)
    {
      this.getRpcManager().execute("bibliograph.plugin.isbnscanner.Service", "import",
          [isbn,datasource],
          function(message){
            alert(message);
            this.getStateManager().removeState("isbn");
            this.getPage(1).show({reverse:true});
          }, this);
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
    },

    /**
     * Returns the label of the given datasource
     * @param datasource
     * @returns {string}
     */
    getDatasourceLabel : function(datasource)
    {
      if ( !this.__datasourceStore )
      {
        return datasource;
      }
      var label="";
      this.__datasourceStore.getModel().forEach(function(item){
        if(item.getValue()==datasource){
          label=item.getLabel();
        }
      });
      return label;
    },

    /**
     * Apply function for the property isbn
     * @param value
     * @private
     */
    _applyIsbn : function(value)
    {
      //this.debug("ISBN:" + value);
    }
  }
});
