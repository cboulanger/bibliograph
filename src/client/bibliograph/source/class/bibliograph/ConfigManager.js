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
 * This object manages the configuration settings of an application. It loads
 * the configuration data and synchronize sthe config values with the server.
 *
 * The server must expose a "load"  method that returns the following response
 * data:
 *
 * <pre>
 * {
 *   keys : [ ... array of the names of the configuration keys ],
 *   values : [ ... array of the configuration values ... ],
 *   types : [ ... array of the configuration value types ... ]
 * }
 * </pre>
 *
 * You can bind any property of an object to a config value by using
 * the {@link #bindKey} method.
 *
 */
qx.Class.define("bibliograph.ConfigManager", {
  extend: qx.core.Object,
  type: "singleton",
  properties: {
    /**
     * The data store used for configuration data
     */
    store: {
      check: "qcl.data.store.JsonRpcStore",
      nullable: true,
      event: "changeStore"
    },

    /*
    * The config manager's data model which can be
    * bound to a data store.
    */
    model: {
      check: "qx.core.Object",
      nullable: true,
      event: "changeModel",
      apply: "_applyModel"
    }
  },

  /*
  *****************************************************************************
    EVENTS
  *****************************************************************************
  */
  events: {
    /* 
    * Dispatched when the configuration data is ready
    */
    ready: "qx.event.type.Event",

    /* 
    * Dispatched with the name of the changed config key when
    * the config value changes, regardless of whether the change
    * was caused be the client or server.
    */
    change: "qx.event.type.Data",

    /* 
    * Dispatched  when the config value changes on the client.
    * The evend data is a map with the keys 'key','value','old' 
    */
    clientChange: "qx.event.type.Data"
  },

  members: {
    /*
   ---------------------------------------------------------------------------
      PRIVATE MEMBERS
   ---------------------------------------------------------------------------
   */

    _configSetup: false,
    _index: null,

    /*
   ---------------------------------------------------------------------------
      APPLY METHODS
   ---------------------------------------------------------------------------
   */

    _applyModel: function (model, old) {
      if (model === null) return;

      // create index
      this._index = {};

      let keys = model.getKeys();
      for (let i = 0; i < keys.length; i++) {
        let key = keys.getItem(i);
        this._index[key] = i;
        this.fireDataEvent("change", key);
      }

      // attach event listener
      model.getValues().addListener(
      "changeBubble",
      function (event) {
        let data = event.getData();
        let key = model.getKeys().getItem(data.name);
        if (data.value != data.old) {
          this.fireDataEvent("change", key);
        }
      },
      this
      );

      /*
       * inform the listeners that we're ready
       */
      this.fireEvent("ready");
    },

    /*
   ---------------------------------------------------------------------------
     PRIVATE METHODS
   ---------------------------------------------------------------------------
   */

    /**
     * Returns the numerical index for a config key
     * name
     * @param key {String}
     * @return {Integer}
     */
    _getIndex: function (key) {
      if (!this._index) {
        this.error("Model has not yet finished loading.");
      }
      let index = this._index[key];
      if (index == undefined) {
        throw new Error("Invalid config key '" + key + "'.");
      }
      return index;
    },

    /*
     ---------------------------------------------------------------------------
        API METHODS
     ---------------------------------------------------------------------------
     */

    /**
     * Initializes the manager
     * @return bibliograph.ConfigManager Returns itself
     */
    init: function (service) {
      // avoid duplicate bindings
      if (this._configSetup) {
        this.warn("Configuration already set up");
        return this;
      }
      this._configSetup = true;

      // set default config store
      this.setStore(new qcl.data.store.JsonRpcStore("config"));

      // bind the configuration store's data model to the manager's data model
      // so that it is copied over when loaded
      this.bind("store.model", this, "model");

      // whenever a config value changes on the server, send it to server
      this.addListener(
      "clientChange",
      function (event) {
        let data = event.getData();
        this.getStore().execute("set", [data.key, data.value]);
      },
      this
      );
      return this;
    },

    /**
     * Loads configuration values from the server and configures auto-update
     * whenever the a value changes on the server. The config data has to be sent
     * in the following format:
     * <pre>
     * {
    *   keys : [ ... array of the names of the configuration keys ],
    *   values : [ ... array of the configuration values ... ]
    * }
     * </pre>
     * @return {Promise<Object>}
     */
    load: function (callback, context) {
      return this.getStore().load(null, null, callback, context);
    },

    /**
     * Checks if a config key exists
     * @param key {String}
     * @return {Boolean}
     */
    keyExists: function (key) {
      try {
        this._getIndex(key);
        return true;
      } catch (e) {
        return false;
      }
    },

    /**
     * Returns a config value
     * @param key {String}
     * @return {var}
     */
    getKey: function (key) {
      let index = this._getIndex(key);
      return this.getModel().getValues().getItem(index);
    },

    /**
     * Sets a config value and fire a 'clientChange' event.
     * @param key {String}
     * @param value {Mixed}
     */
    setKey: function (key, value) {
      let index = this._getIndex(key);
      let old = this.getModel().getValues().getItem(index);
      if (value != old) {
        this.getModel().getValues().setItem(index, value);
        this.fireDataEvent("clientChange", {
          key: key,
          value: value,
          old: old
        });
      }
    },

    /**
     * Binds a config value to a target widget property, optionally in both
     * directions.
     * @param key {String}
     * @param targetObject {qx.core.Object}
     * @param targetPath {String}
     * @param bidirectional {Boolean} Optional, defaults to false.
     *    If true, change the config value if the target property changes
     * @return {void}
     */
    bindKey: function (key, targetObject, targetPath, bidirectional=false) {
      if (!this.getModel()) {
        this.addListenerOnce("ready", e => {
          this.bindKey(key, targetObject, targetPath, bidirectional);
        });
        return;
      }

      if (!targetObject instanceof qx.core.Object) {
        this.error("Invalid target object.");
      }

      if (!qx.lang.Type.isString(targetPath)) {
        this.error("Invalid target path.");
      }
      /*
       * if the target path is a property and not a property chain,
       * use event listeners. This also solves a problem with a bug
       * in the SigleValueBinding implementation,
       * see http://www.nabble.com/Databinding-td24099676.html
       */
      if (targetPath.indexOf(".") === -1) {
        // set the initial value
        targetObject.set(targetPath, this.getKey(key));

        // add a listener to update the target widget property when config value changes
        // @todo: add converter
        this.addListener( "change", e => {
          let changeKey = e.getData();
          if (changeKey === key) {
            //console.warn("Updating property "+targetPath+" from config key "+key+":"+this.getValue(key));
            targetObject.set(targetPath, this.getKey(key));
          }
        });
        // update config value if target widget property changes
        if (bidirectional) {
          let eventName =  "change" + targetPath.substr(0, 1).toUpperCase() + targetPath.substr(1);
          targetObject.addListener( eventName, e => {
            let value = e.getData();
            //console.warn("Updating config key "+key+" with "+value);
            this.setKey(key, value);
          });
        }
      } else {
        //use SigleValueBinding, was not working last time I checked.
        // @todo check with current qooxdoo code, but might have been fixed.
        let index = this._getIndex(key);
        targetObject.bind(targetPath, this, "model.values[" + index + "]");
        if (bidirectional) {
          this.bind("model.values[" + index + "]", targetObject, targetPath);
        }
      }
    },


    /**
     * Binds a config value to the selection of a widget that has a 'model' and
     * a 'selection' property (both must be a qx.data.Array).
     * @param key
     * @param target
     * @param bidirectional
     * @todo implement
     */
    bindKeyToSelection : function( key, target, bidirectional=false)
    {
      throw new Error("Not implemented");
    }
  },

  /*
  *****************************************************************************
      DESTRUCTOR
  *****************************************************************************
  */

  destruct: function () {
    this._disposeArray("_index");
  }
});