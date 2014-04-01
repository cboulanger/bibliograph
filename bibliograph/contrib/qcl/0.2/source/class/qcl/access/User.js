/* ************************************************************************

   qcl - the qooxdoo component library
  
   http://qooxdoo.org/contrib/project/qcl/
  
   Copyright:
     2007-2014 Christian Boulanger

   License:
     LGPL: http://www.gnu.org/licenses/lgpl.html
     EPL: http://www.eclipse.org/org/documents/epl-v10.php
     See the LICENSE file in the project's top-level directory for details.

   Authors:
 * Christian Boulanger (cboulanger)

 ************************************************************************ */

/* ************************************************************************
#require(qcl.access.UserManager)
 ************************************************************************ */

/**
 * A user object
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
    this.setNamedId(vName);
    this._manager = qx.core.Init.getApplication().getAccessManager().getUserManager();
    this._manager.add(this);
  
    this.setPermissions([]);
  },

  /*
  *****************************************************************************
  PROPERTIES
  *****************************************************************************
  */

  properties :
  {
    /**
     * login name of the user
     */
    namedId :
    {
      check    : "String",
      nullable : false,
      apply : "_applyNamedId"
    },

    /**
     * Alias for namedId which can be bound
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
      init : true
    },
    
    /**
     * Whether the user data is editable
     */
    editable :
    {
      check : "Boolean",
      init : false
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


  /*
  *****************************************************************************
  MEMBERS
  *****************************************************************************
  */

  members :
  {

    _manager : null,
    
    _applyNamedId : function( value, old )
    {
      this.setUsername( value );
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
     * @return
     */
    addPermissionsByName : function( names )
    {
      var permMgr = qx.core.Init.getApplication().getAccessManager().getPermissionManager();
      for( var i=0; i < names.length; i++)
      {
        this.getPermissions().push(
          permMgr.create( names[i] ) 
        );
      }
      this.fireDataEvent("changePermissions",this.getPermissions());
    },

    /**
     * Grant all permissions that the user has.
     */
    grantPermissions : function()
    {
      this.getPermissions().forEach( function( permission )
      {
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

  /*
  *****************************************************************************
  DESTRUCTOR
  *****************************************************************************
  */

  destruct : function() {
    this._manager.remove(this);
  }
});