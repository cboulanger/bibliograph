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

************************************************************************ */

/**
 * This manager (singleton) manages users
 */
qx.Class.define("qcl.access.UserManager",
{

	extend : qcl.access.AbstractManager,
  type : "singleton",

  
  /*
  *****************************************************************************
     CONSTRUCTOR
  *****************************************************************************
  */

  construct : function()
  {
		this.base(arguments);
    this._type = "User";
  },
  
 

  /*
  *****************************************************************************
     PROPERTIES
  *****************************************************************************
  */

  properties :
  {
    /**
     * The currently logged-in user
     */
    activeUser :
    {
      check       : "Object",
			event				: "changeActiveUser",
			apply				: "_applyActiveUser",
			nullable		: true
    },
    
    model : 
    {
      check : "Object",
      init : null,
      nullable : true,
      apply : "_applyModel",
      event : "changeModel"
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
		 * get user object by login name
		 * @param username {String}
		 * @return {qcl.access.User}
		 */
		getByUsername : function ( username )
		{
			return this.getObject( username );
		},
		
		/**
		 * apply the data model containing userdata
		 */
		_applyModel : function ( model, old )
		{
		  if ( model && ! model.getError() )
		  {
		    /*
		     * create user
		     */
		    var user = this.create( model.getUsername() );
		    
		    /*
		     * set user data
		     */
		    user.setFullname( model.getFullname() );
		    user.setAnonymous( model.getAnonymous() );
        user.setEditable( model.getEditable() );
		    user.setPermissions([]);
		    user.addPermissionsByName( model.getPermissions().toArray() );
		    
		    /*
		     * set user as active user
		     */
		    this.setActiveUser( user ); 
		  }
		},
		
		/** 
		 * sets the currently active/logged-in user
		 */
		_applyActiveUser : function ( userObj, oldUserObj )
		{
			if ( oldUserObj )
			{
				oldUserObj.revokePermissions();
			}
			
			if ( userObj instanceof qcl.access.User )
			{
				userObj.grantPermissions();
			}
			else if( userObj !== null )
			{
				this.error ( "activeUser property must be null or of type qcl.access.User ");
			}
		},
    
//    /**
//     * Creates or returns already created user with the given named id
//     * @param namedId {String}
//     * @return {qcl.access.User}
//     */
//    create : function( namedId )
//    {
//      return this.base(arguments, namedId );
//    },
		
		/**
		 * removes all permission, role and user information
		 */
		logout : function()
		{
		  this.setActiveUser(null);
		}
  }
});