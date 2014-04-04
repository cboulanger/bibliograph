/* ************************************************************************

   Copyright:

   License:

   Authors:

************************************************************************ */

/**
 * This is the main application class of your custom application "bibliograph-mobile"
 *
 * @asset(bibmobile/*)
 */
qx.Class.define("bibmobile.Application",
{
  extend : qx.application.Mobile,



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
      -------------------------------------------------------------------------
        Below is your actual application code...
        Remove or edit the following code to create your application.
      -------------------------------------------------------------------------
      */

      // ugh. so much code just to get the querystring parameters
      var urlParams;
      var match,
            pl     = /\+/g,  // Regex for replacing addition symbol with a space
            search = /([^&=]+)=?([^&]*)/g,
            decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
            query  = window.location.search.substring(1);
        urlParams = {};
        while (match = search.exec(query))
          urlParams[decode(match[1])] = decode(match[2]);


      var page1 = new qx.ui.mobile.page.NavigationPage();
      page1.setTitle("Bibliograph Mobile Client");
      page1.addListener("initialize", function()
      {
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
          var backendUrl = window.location.href.split(/\//);
          backendUrl = backendUrl.slice(0, backendUrl.length-3).join("/") + "/bibliograph/services/server.php";
          var scannerUrl = "ilu://x-callback-url/scanner-go?x-source=Bibliograph&x-success=" +
              backendUrl + "?&sg-result=isbn";
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
    }
  }
});
