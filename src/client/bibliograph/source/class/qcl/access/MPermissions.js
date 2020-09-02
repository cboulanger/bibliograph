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
 * Mixin that provides helper methods for permissions
 */
qx.Mixin.define("qcl.access.MPermissions",
{
  construct : function() {
    this.info(`*** Setting up permissions via mixin constructor in ${this}...`);
    this.setupPermissions();
    // monkeypatch main app's finalize() method
    let app = qx.core.Init.getApplication();
    let finalize = app.finalize.bind(app);
    let self = this;
    app.finalize = function() {
      self.finalize();
      finalize();
    };
  },

  /*
   *****************************************************************************
     EVENTS
   *****************************************************************************
   */
  events: {
    /**
     * Dispatched when the permission data is ready
     */
    permissionsReady: "qx.event.type.Event"

  },
  
  statics : {
    events : {
      permissionsReady : "permissionsReady"
    }
  },

  members:
  {
    /**
     * {
     *    <key> : {
     *
     *      // The name if different from the key (optional). The name can
     *      // contain characters which are forbidden in dot notation.
     *      // Name must not have been used before and will be globally available.
     *      // If the permission should be unique to the instance, don't use a name,
     *      // use the key only.
     *      name : {String},
     *
     *      // Create a dependency on one or more existing permissions
     *      depends : {String|String[]},
     *
     *      // Create an alias of existing permission
     *      aliasOf : {String},
     *
     *      // Event(s) that trigger the update of the event.
     *      // Can be of form "objectId:eventName", then the event listener
     *      // is attached to the object with the given object id
     *      updateEvent : {String|String[]}
     *
     *      // functions that will be called to determine the permission
     *      // state. State will only be true if all functions return true.
     *      // the functions will be called with the target of the update
     *      // event(s), either the current instance or the instance with
     *      // the widgetId in the updateEvent. If either `updateEvent` or
     *      // `condition` is an array, the number of array element must match.
     *      condition : {Function|Function[]},
     *
     *      // permission can be turned off no matter all other conditions if you
     *      // set granted:false. This is true by default
     *      granted : {Boolean}
     *    }
     * }
     */
    // permissions : {},

    $$permissionsSetup : false,
    $$permissionUpdaters : null,

    /**
     * @return {qcl.access.PermissionManager}
     */
    getPermissionManager : function() {
      return qx.core.Init.getApplication().getPermissionManager();
      //return qcl.access.PermissionManager().getInstance();
    },

    /**
     * Shorthand method to return a permission object by name
     *
     * @return {qcl.access.Permission}
     * @param name
     */
    getPermission : function(name) {
      return this.getPermissionManager().create(name);
    },

    /**
     * Shorthand method to return a permission state
     *
     * @return {Boolean}
     * @param name
     */
    getPermissionState : function(name) {
      return this.getPermissionManager().create(name).getState();
    },

    /**
     * Shorthand method to update a permission
     *
     * @return {void}
     * @param name
     */
    updatePermission : function(name) {
      this.getPermission(name).update();
    },

    /**
     * Initializes the permissions map by replacing the defintions with
     * the fully configured permission object.
     */
    setupPermissions : function() {
      if (this.$$permissionsSetup) {
        this.debug(`Permissions of ${this} already set up.`);
        return;
      }
      if (!qx.lang.Type.isObject(this.permissions)) {
        this.warn("No 'permissions' member property object, creating enpty one.");
        this.permissions = {};
      }
      this.$$permissionUpdaters = {};
      let manager = this.getPermissionManager();

      // create a new permission object on each instance
      this.$$permissions = {};
      this.$$permissionData = this.permissions; // need to clone to preserve?

      // loop over permissions
      for (let permissionName in this.$$permissionData) {
        this.debug(`Creating permission ${this}.permissions.${permissionName}...`);

        // data
        let permData = this.permissions[permissionName];
        if (permData instanceof qcl.access.Permission) {
          this.warn(" - Already set up!");
          continue;
        }

        /** @var {qcl.access.Permission} */
        let permission;
        let permissionUpdaters = [];

        if (!qx.lang.Type.isObject(permData)) {
          // if you don't supply a map (i.e., null), the value will be an unmodified permission with that name
          this.$$permissions[permissionName] = manager.create(permissionName);
          this.debug(" - No data, creating unmodified permission object.");
          continue;
        }

        // Name and alias
        if (qx.lang.Type.isString(permData.name)) {
          if (manager.getByName(permData.name)) {
            throw new Error(`Permission name '${permData.name}' is already taken.`);
          }
          this.debug(` - Using name '${permData.name}'.`);
          permission = manager.create(permData.name);
        } else if (qx.lang.Type.isString(permData.aliasOf)) {
          if (!manager.getObject(permData.aliasOf) && !manager.getObject("*")) {
            this.warn(` - Permission is alias of '${permData.aliasOf}', which does not exist (yet).`);
          } else {
            this.debug(` - Permission is alias of '${permData.aliasOf}'. Do not modify it unless you want the alias to be modified, too.`);
          }
          permission = manager.create(permData.aliasOf);
        } else {
          // create a name that is unique to the instance
          permission = manager.create(permissionName+this.toHashCode());
        }

        // Dependencies
        if (permData.depends !== undefined) {
          this._castToArray(permData.depends).forEach(dependencyName => {
            let dependency = manager.create(dependencyName);
            dependency.bind("granted", permission, "granted");
            permission.addCondition(() => dependency.getState());
            dependency.addListener("changeState", () => permission.update());
            this.debug(` - Added dependency on '${dependencyName}'.`);
          });
        }
        // Update events
        if (permData.updateEvent !== undefined) {
          this._castToArray(permData.updateEvent).forEach((eventName, index) => {
            // micro-protocol: if updateEvent is of the form
            // "objectId:event-name", use object with the object id
            // as event target
            if (eventName.includes(":")) {
              let splitPos = eventName.indexOf(":");
              let objectId = eventName.substr(0, splitPos);
              eventName = eventName.substr(splitPos+1);
              let obj = qx.core.Id.getQxObject(objectId);
              // backwards compatibility to deprecated widget ids
              // todo remove when all widget ids are gone
              if (!obj) {
                obj = objectId === "app" ?
                  this.getApplication() :
                  this.getApplication().getWidgetById(objectId);
              }
              if (obj) {
                permissionUpdaters[index] = obj;
                obj.addListener(eventName, () => permission.update());
                this.debug(` - Added event listener '${eventName}' to object with id '${objectId}'...`);
              } else {
                this.debug(` - Deferred attachment of event listener '${eventName}' to object with id '${objectId}'...`);
                permissionUpdaters[index] = [objectId, eventName, permission];
              }
            } else {
              // normal event name
              permissionUpdaters[index] = this;
              this.addListener(eventName, () => permission.update());
            }
          });
        }
        // Conditions
        if (permData.condition !== undefined) {
          let count=0;
          this._castToArray(permData.condition).forEach((fn, index) => {
            let instance = permissionUpdaters[index];
            if (instance instanceof qx.core.Object) {
              permission.addCondition(fn, instance);
              count++;
            } else if (qx.lang.Type.isArray(instance)) {
              this.debug(` - Deferred adding of condition in context of object with object id '${instance[0]}'...`);
              permissionUpdaters[index].push(fn);
            } else {
              this.error("Invalid instance data");
            }
          });
          if (count) {
           this.debug(` - Added ${count} condition(s).`);
          }
        }
        // Grant state, false by default
        if (permData.granted === undefined) {
          permission.setGranted(false);
        } else {
          permission.setGranted(permData.granted);
        }
        this.$$permissions[permissionName] = permission;
        this.$$permissionUpdaters[permissionName] = permissionUpdaters;
      }
      this.$$permissionsSetup = true;
      // replace class-wide property with instance property
      this.permissions = this.$$permissions;
    },

  
    /**
     * Execute deferred permission actions
     */
    finalize : function() {
      this.info("*** Finalizing permission setup...");
      for (let permissionName in this.permissions) {
        this.$$permissionUpdaters[permissionName].forEach(elem => {
          if (qx.lang.Type.isArray(elem)) {
            if (elem.length === 3) {
              // update
              let [widgetId, eventName, permission] = elem;
              let instance = qx.core.Init.getApplication().getWidgetById(widgetId);
              if (!instance) {
                this.error(`Object with id '${widgetId}' does not exist.`);
              }
              instance.addListener(eventName, () => permission.update());
              this.debug(` - (Finalize) Added update event listener '${eventName}' to object with widget id '${widgetId}'...`);
            } else if (elem.length === 4) {
              // condition
              let [widgetId, eventName, permission, fn] = elem;
              let app = qx.core.Init.getApplication();
              let instance = widgetId === "app" ? app : app.getWidgetById(widgetId);
              if (!instance) {
                this.error(`Object with id '${widgetId}' does not exist.`);
              }
              permission.addCondition(fn, instance);
              this.debug(` - (Finalize) Added condition to permission '${permission.getNamedId()}' ('${widgetId}')...`);
            } else {
              this.error("Invalid instance data");
            }
          }
        });
      }
      this.fireEvent("permissionsReady");
    },
  
    /**
     * Bind the given property of the targetWidget to the state of
     * this permission
     * @param permission {qcl.access.Permission|string } The permission object or name
     * @param targetWidget {qx.ui.core.Widget}
     * @param targetProperty {String}
     */
    bindState : function(permission, targetWidget, targetProperty) {
      let p = this._checkBindArguments(permission, targetWidget);
      p.bind("state", targetWidget, targetProperty);
    },
  
    /**
     * Bind the `enabled` property of the targetWidget to the state of
     * this permission
     * @param permission {qcl.access.Permission|string} The permission object or name
     * @param targetWidget {qx.ui.core.Widget}
     */
    bindEnabled : function(permission, targetWidget) {
      let p = this._checkBindArguments(permission, targetWidget);
      p.bind("state", targetWidget, "enabled");
    },
    
    /**
     * Bind the `visibility` property of the targetWidget to the state of
     * this permission. This maps `true` to `visible` and `false` to `excluded`
     * @param permission {qcl.access.Permission|string} The permission object or name
     * @param targetWidget {qx.ui.core.Widget}
     * @param {Boolean} reverse If true, hide the widget if permission is granted
     */
    bindVisibility : function(permission, targetWidget, reverse=false) {
      let p = this._checkBindArguments(permission, targetWidget);
      p.bind("state", targetWidget, "visibility", {
        converter: value => (reverse ? !value : value) ? "visible" : "excluded"
      });
    },
  
    /**
     * Binds a boolean property of the source object to the visibility property
     * of the target widget.
     * @param {qx.core.Object} source
     * @param {String} bProperty
     * @param {qx.ui.core.Widget} target
     * @param {Boolean} reverse If true, hide the widget if bProperty is true
     */
    bindVisibilityToProp(source, bProperty, target, reverse=false) {
      source.bind(bProperty, target, "visibility", {
        converter: value => (reverse ? !value : value) ? "visible" : "excluded"
      });
    },
    
    /**
     * Check the arguments passed to the bindXXX methods. If the permission is a string,
     * it is converted into a permission object
     * @param permission {qcl.access.Permission}
     * @param targetWidget {qx.ui.core.Widget}
     * @private
     * @return {qcl.access.Permission}
     */
    _checkBindArguments : function(permission, targetWidget) {
      let type = qx.lang.Type.getClass(permission);
      if (type === "Object" && (permission.depends||permission.updateEvent||permission.aliasOf||permission.condition)) {
        this.error("Permission object has not been set up - call setupPermissions() first.");
      }
      if (typeof permission === "string") {
        this.debug(`Creating permission ${permission}...`);
        permission = qcl.access.PermissionManager.getInstance().create(permission);
      }
      if (!(targetWidget instanceof qx.ui.core.Widget)) {
        this.error("target must be instanceof qx.ui.core.Widget but is " + qx.lang.Type.getClass(targetWidget));
      }
      return permission;
    },

    /**
     * Casts the given value to an array
     * @param value {Mixed}
     * @returns {Array}
     * @private
     */
    _castToArray : function(value) {
      return qx.lang.Type.isArray(value) ? value : [value];
    }
  }
});
