/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2015 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
 * Christian Boulanger (cboulanger)

 ************************************************************************ */

/**
 * A user object
 * @require(qcl.access.UserManager)
 */
qx.Class.define("qcl.access.User",
{
  extend : qx.core.Object,

  /*
  *****************************************************************************
  CONSTRUCTOR
  *****************************************************************************
  */

  construct : function(vName)
  {
    this.base(arguments);
    this.setUsername(vName);
    this._manager = this.getApplication().getAccessManager().getUserManager();
    this._manager.add(this);
    this.setPermissions([]);
  },

  properties :
  {

    /**
     * The username
     */
    username :
    {
      check : "String",
      nullable : false,
      event : "changeUsername"
    },
    
    /**
     * A hash map of user data
     */
    fullname :
    {
      check : "String",
      event : "changeFullname"
    },

    /**
     * Whether user is an unauthenticated guest user
     */
    anonymous :
    {
      check : "Boolean",
      init : true,
      event : "changeAnonymous"
    },
    
    /**
     * Whether the user data is editable
     */
    editable :
    {
      check : "Boolean",
      init : false,
      event : "changeEditable"
    },    
    
    /**
     * An array of permission objects
     */
    permissions :
    {
      check : "Array",
      nullable : false,
      event : "changePermissions"
    }

  },

  members :
  {

    _manager : null,

    /**
     * Shim for abstract manager
     */
    getNamedId : function(){
      return this.getUsername()
    },

    /**
     * Check if user has the given permission
     * @param permissionRef {String|qcl.access.Permission} name of permission object or object reference
     * @return {Boolean} Whether user has permission
     */
    hasPermission : function( permissionRef )
    {
      var hasPermission = false;
      var perms = this.getPermissions();
      for ( var i=0; i<perms.length; i++ )
      {
        var permission = perms[i];
        if ( permissionRef instanceof qcl.access.Permission 
            && permissionRef === permission ) return true;
        else if ( permissionRef == permission.getNamedId() ) return true;
      };
      return false;
    },

    /**
     * gets name of permissions
     * @return {Array} Array of permission names
     */
    getPermissionNames : function()
    {
      var names = [];
      var perms = this.getPermissions();
      for ( var i=0; i<perms.length; i++ )
      {
        names.push( perms[i].getNamedId() );
      }
      return names;
    },
    
    /**
     * Adds a permission identified by its id, creating it if
     * it doesn't already exist.
     * @param names {Array} Array of strings
     * @return {void}
     */
    addPermissionsByName : function( names )
    {
      var permMgr = this.getApplication().getAccessManager().getPermissionManager();
      for( var i=0; i < names.length; i++)
      {
        let name = names[i];
        // superpowers! Already taken care of in Permission._applyGranted()
        // if( name == "*" ){
        //   permMgr.getAll().forEach((permission)=>{
        //     permission.setGranted(true);
        //   });
        // }
        //console.debug( `Added permission ${name}`);
        this.getPermissions().push(
          permMgr.create( name ) 
        );
      }
      this.fireDataEvent("changePermissions",this.getPermissions());
    },

    /**
     * Grant all permissions that the user has.
     */
    grantPermissions : function()
    {
      this.getPermissions().forEach( function( permission ){
        permission.setGranted(true);
      },this);
    },

    /**
     * Revoke all permissions of the particular user
     */
    revokePermissions : function()
    {
      this.getPermissions().forEach( function( permission )
      {
        permission.setGranted(false);
      },this);
      this.setPermissions([]);
    }
  },

  destruct : function() {
    this._manager.remove(this);
  }
});