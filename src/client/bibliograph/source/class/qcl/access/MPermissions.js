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
  construct : function(){
    this.__instances = [];
    this.info("Setting up permissions via mixin constructor in " + this.classname );
    this.setupPermissions();
    // monkeypatch main app's finalize() method
    let app = qx.core.Init.getApplication();
    let finalize = app.finalize.bind(app);
    let self = this;
    app.finalize = function(){
      self.finalize();
      finalize();
    }.bind(app);
  },
  
  members :
  {
    /**
     * {
     *    name : {
     *      // permission can be turned off no matter all other conditions until "granted"
     *      granted : {Boolean},
     *      // set a dependency on a permission(s) given by the server
     *      depends : {String|String[]},
     *      // alias of existing permission from server
     *      aliasOf : {String},
     *      // event(s) that trigger the update of the event
     *      // can be of form "widgetId:eventName", then the event listener
     *      // is attached to the object with the given widgetId
     *      updateEvent : {String|String[]}
     *      // functions that will be called to determine the permission
     *      // state. State will only be true if all functions return true.
     *      // the functions will be called with the target of the update
     *      // event(s), either the current instance or the instance with
     *      // the widgetId in the updateEvent. If either `updateEvent` or
     *      // `condition` is an array, the number of array element must match.
     *      condition : {Function|Function[]}
     *    }
     * }
     */
    // permissions : {},
    
    __instances : null,
    
    /**
     * @return {qcl.access.PermissionManager}
     */
    getPermissionManager : function()
    {
      return qx.core.Init.getApplication().getPermissionManager();
      //return qcl.access.PermissionManager().getInstance();
    },
  
    /**
     * @param name {String}
     * @return {qcl.access.Permission}
     */
    getPermission : function(name)
    {
      return this.getPermissionManager().create(name);
    },
  
    /**
     * Initializes the permissions map by replacing the defintions with
     * the fully configured permission object.
     */
    setupPermissions : function()
    {
      if( ! qx.lang.Type.isObject(this.permissions) ){
        throw new Error("You need to define a 'permissions' member property object.");
      }
      let manager = this.getPermissionManager();
      for( let name in this.permissions ){
        let permData = this.permissions[name];
        if( ! permData ){
          this.permissions[name] = manager.create(name);
          continue;
        }
        /** @var {qcl.access.Permission} */
        let permission;
        if ( permData.aliasOf !== undefined ){
          permission = manager.create(permData.aliasOf);
        } else {
          permission = manager.create(name);
        }
        // Dependencies
        if( permData.depends !== undefined ){
          this._castToArray(permData.depends).forEach((dependencyName)=>{
            let dependency = manager.create(dependencyName);
            permission.addCondition(() => dependency.getState());
            dependency.addListener("changeState", () => permission.update());
          });
        }
        // Update events
        if( permData.updateEvent !== undefined ){
          this._castToArray(permData.updateEvent).forEach((name, index)=>{
            // micro-protocol: if updateEvent is of the form
            // "widget/id:event-name", use object with the widgetId
            // as event target
            if( name.includes(":") ){
              let widgetId = name.substr(0, name.indexOf(":") );
              let eventName = name.substr( name.indexOf(":")+1 );
              let obj = this.getApplication().getWidgetById(widgetId);
              if( obj ){
                this.__instances[index] = obj;
                obj.addListener(eventName, () => permission.update() );
              } else {
                this.debug(`Deferring attachment of event listener '${eventName}' to object with widget id '${widgetId}'...`);
                this.__instances[index] = [widgetId, eventName, permission];
              }
            } else {
              // normal event name
              this.__instances[index] = this;
              this.addListener(name, () => permission.update() )
            }
          });
        }
        // Conditions
        if( permData.condition !== undefined ){
          this._castToArray(permData.condition).forEach((fn, index)=>{
            let instance = this.__instances[index];
            if ( instance instanceof qx.ui.core.Widget){
              permission.addCondition( fn, instance );
            } else if (qx.lang.Type.isArray(instance)) {
              this.debug(`Deferring adding of condition to permission '${instance[2].getNamedId()}' in context of object with widget id '${instance[0]}'...`);
              this.__instances[index].push(fn);
            } else {
              this.error("Invalid instance data");
            }
          });
        }
        // Grant state
        if( permData.granted !== undefined ){
          permission.setGranted(permData.granted);
        }
        
        // overwrite the defintion with the permission object
        this.debug(`Created permission ${this.classname}#permissions.${name}`);
        this.permissions[name]=permission;
      }
    },
  
    /**
     * Execute deferred permission actions
     */
    finalize : function()
    {
      this.info("Finalizing permission setup...");
      this.__instances.forEach((elem)=>{
        if( qx.lang.Type.isArray(elem) ){
          if( elem.length === 3){
            // update
            let [widgetId, eventName, permission] = elem;
            let instance = qx.core.Init.getApplication().getWidgetById(widgetId);
            if( ! instance ){
              this.error(`Object with id '${widgetId}' does not exist.`);
            }
            instance.addListener(eventName,() => permission.update());
            this.debug(`Adding update event listener '${eventName}' to object with widget id '${widgetId}'...`);
          } else if (elem.length === 4 ){
            // condition
            let [widgetId, eventName, permission, fn] = elem;
            let instance = qx.core.Init.getApplication().getWidgetById(widgetId);
            if( ! instance ){
              this.error(`Object with id '${widgetId}' does not exist.`);
            }
            permission.addCondition( fn, instance );
            this.debug(`Adding condition to permission '${permission.getNamedId()}' ('${widgetId}')...`);
          } else {
            this.error("Invalid instance data");
          }
        }
      });
    },
  
    /**
     * Bind the `enabled` property of the targetWidget to the state of
     * this permission
     * @param permission {qcl.access.Permission}
     * @param targetWidget {qx.ui.core.Widget}
     */
    bindEnabled : function(permission, targetWidget){
      this._checkBindArguments(permission, targetWidget);
      permission.bind("state", targetWidget, "enabled");
    },
  
  
    /**
     * Bind the `visibility` property of the targetWidget to the state of
     * this permission. This maps `true` to `visible` and `false` to `excluded`
     * @param permission {qcl.access.Permission}
     * @param targetWidget {qx.ui.core.Widget}
     */
    bindVisibility : function(permission, targetWidget){
      this._checkBindArguments(permission, targetWidget);
      permission.bind("state", targetWidget, "visibility", {
        converter: bibliograph.Utils.bool2visibility
      });
    },
    
    _checkBindArguments : function(permission, targetWidget)
    {
      if( ! (permission instanceof qcl.access.Permission ) ){
        this.error("Permission must be instanceof qcl.access.Permission but is " + qx.lang.Type.getClass(permission) )
      }
      if( ! (targetWidget instanceof qx.ui.core.Widget ) ){
        this.error("target must be instanceof qx.ui.core.Widget but is " + qx.lang.Type.getClass(targetWidget) );
      }
    },
    
    _castToArray : function(value)
    {
      return qx.lang.Type.isArray(value) ? value : [value];
    }
  }
});