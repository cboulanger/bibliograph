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
 * This manager (singleton) manages users
 */
qx.Class.define("qcl.access.UserManager",
{
  
  extend: qcl.access.AbstractManager,
  type: "singleton",
  
  construct: function () {
    this.base(arguments);
    this._type = "User";
  },
  
  properties:
  {
    
    /**
     * The currently logged-in user
     */
    activeUser:
    {
      check: "Object",
      event: "changeActiveUser",
      apply: "_applyActiveUser",
      nullable: true
    },
    
    /**
     * A model which will store server data
     */
    model:
    {
      check: "Object",
      init: null,
      nullable: true,
      apply: "_applyModel",
      event: "changeModel"
    }
  },
  
  members:
  {
    /**
     * get user object by login name
     * @todo what is this for?
     * @param username {String}
     * @return {qcl.access.User}
     */
    getByUsername: function (username) {
      return this.getObject(username);
    },
    
    /**
     * apply the data model containing userdata
     */
    _applyModel: function (model, old) {
      if (model) {
        if (this.getActiveUser()) {
          this.reset();
        }
        
        // create user
        let user = this.create(model.getNamedId());
        
        // set user data
        user.setFullname(model.getName());
        user.setAnonymous(model.getAnonymous());
        user.setEditable(!model.getLdap());
        
        // update permissions
        user.setPermissions([]);
        if (model.getPermissions()) {
          user.addPermissionsByName(model.getPermissions().toArray());
        }
        
        // set user as active user
        this.setActiveUser(user);
        
        // grant permissions
        user.grantPermissions();
      }
    },
    
    /**
     * sets the currently active/logged-in user
     */
    _applyActiveUser: function (userObj, oldUserObj) {
      if (oldUserObj) {
        oldUserObj.revokePermissions();
      }
      
      if (userObj instanceof qcl.access.User) {
        userObj.grantPermissions();
      }
      else if (userObj !== null) {
        this.error("activeUser property must be null or of type qcl.access.User ");
      }
    },
    
    /**
     * removes all permission, role and user information
     */
    reset: function () {
      if (this.getActiveUser()) {
        this.getActiveUser().revokePermissions();
        this.setActiveUser(null);
      }
    }
  }
});
