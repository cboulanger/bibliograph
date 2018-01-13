/* ************************************************************************

  Bibliograph. The open source online bibliographic data manager

  http://www.bibliograph.org

  Copyright: 
    2018 Christian Boulanger

  License: 
    MIT license
    See the LICENSE file in the project's top-level directory for details.

  Authors: 
    Christian Boulanger (@cboulanger) info@bibliograph.org

************************************************************************ */

/**
 * This is a qooxdoo singleton class
 *
 */
qx.Class.define("bibliograph.Setup", {
  extend: qx.core.Object,
  type: "singleton",

  /**
   * The properties of the singleton
   */
  properties: {
    /** The foo property of the object */
    foo: {
      apply: "_applyFoo",
      nullable: true,
      check: "String",
      event: "changeFoo"
    }
  },


  /**
   * Methods and simple properties of the singleton
   */
  members: {

    /**
     * Dummy method to mark dynamically generated messages for translation
     */
    markForTranslation : function()
    {
      this.tr("No connection to server.");
      this.tr("Loading folder data ...");
    },

    /*
    ---------------------------------------------------------------------------
       SETUP METHODS
    ---------------------------------------------------------------------------
    */

    /**
     * This will initiate server setup. When done, server will send a 
     * "bibliograph.setup.done" message.
     */
    checkServerSetup : async function(){
      this.getApplication().getRpcClient("setup").send("setup");
      await this.getApplication().resolveOnMessage("bibliograph.setup.done");
      this.info("Server setup done.");
    },

    /**
     * Unless we have a token in the session storage, authenticate
     * anomymously with the server.
     */
    authenticate : async function(){
      let am = bibliograph.AccessManager.getInstance();
      let token = am.getToken();
      let client = this.getApplication().getRpcClient("access");
      if( ! token ) {
        this.info("Authenticating with server...");
        let response = await client.send("authenticate",[]);
        if( ! response ) {
          return this.error("Cannot authenticate with server: " + client.getErrorMessage() );
        }
        let { message, token, sessionId } = response; 
        this.info(message);
        
        am.setToken(token);
        am.setSessionId(sessionId);
        this.info("Acquired access token.");
      } else {
        this.info("Got access token from session storage" );
      }
    },

    loadConfig : async function(){
      this.info("Loading config values...");
      await this.getApplication().getConfigManager().init().load();
      this.info("Config values loaded.");
    },

    loadUserdata : async function(){
      this.info("Loading userdata...");
      await this.getApplication().getAccessManager().init().load();
      this.info("Userdata loaded.");
    },

    /** Applies the foo property */
    _applyFoo: function(value, old) {
      //
    }
  }
});
