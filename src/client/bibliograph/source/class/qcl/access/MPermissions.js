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
  members :
  {
    /**
     * {
     *    name : {
     *      granted : true/false,
     *      depends : "depends on existing permission from server", // can also be an array of permissions
     *      aliasOf : "alias of existing permission from server",
     *      updateEvent : "changeProperty", // can also be an array of events
     *      condition : (includingObject) => includingObject.getProperty() !== null // can also be an array of functions
     *    }
     * }
     */
    // permissions : {},
    
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
          this._castToArray(permData.updateEvent).forEach((name)=>{
            this.addListener(name, () => permission.update() )
          });
        }
        // Conditions
        if( permData.condition !== undefined ){
          this._castToArray(permData.condition).forEach((fn)=>{
            permission.addCondition( fn, this );
          });
        }
        // Grant state
        if( permData.granted !== undefined ){
          permission.setGranted(permData.granted);
        }
        
        // overwrite the defintion with the permission object
        this.permissions[name]=permission;
      }
    },
    
    _castToArray : function(value)
    {
      return qx.lang.Type.isArray(value) ? value : [value];
    }
  }
});