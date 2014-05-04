<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_data_model_db_NamedActiveRecord" );
qcl_import( "qcl_data_datasource_DbModel" );

/**
 * Class modelling a user record
 */
class qcl_access_model_User
  extends qcl_data_model_db_NamedActiveRecord
{

  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  /**
   * The table storing model data
   */
  protected $tableName = "data_User";

  /**
   * Properties
   */
  private $properties = array(
    'name'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(100)"
    ),
    'password'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(50)"
    ),
    'email'  => array(
      'check'     => "string",
      'sqltype'   => "varchar(255)"
    ),
    'anonymous'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1) NOT NULL DEFAULT 0",
      'nullable'  => false,
      'init'      => false
    ),
    'ldap'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1) NOT NULL DEFAULT 0",
      'nullable'  => false,
      'init'      => false
    ),
    'active'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1)",
      'nullable'  => false,
      'init'      => true
    ),
    'lastAction'  => array(
      'check'     => "qcl_data_db_Timestamp",
      'sqltype'   => "timestamp",
      'export'    => false
    ),
    'confirmed'  => array(
      'check'     => "boolean",
      'sqltype'   => "int(1) NOT NULL DEFAULT 0",
      'nullable'  => false,
      'init'      => false
    ),
    'online'		=> array(
      'check'     => "boolean",
      'sqltype'   => "int(1) NOT NULL DEFAULT 0",
      'nullable'  => false,
      'init'      => false
    )
  );

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "UserId";

  /**
   * Relations
   */
  private $relations = array(
    'User_Role' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class'    => "qcl_access_model_Role" ),
      'depends'     => array(
         array( 'relation'  => "Group_User" )
      )
    ),
    'Group_User' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_access_model_Group" )
    ),
    'User_UserConfig' => array(
      'type'        => QCL_RELATIONS_HAS_MANY,
      'target'      => array(
        'class'       => "qcl_config_UserConfigModel",
        'dependent'   => true
      )
    ),
    'User_Session'  => array(
      'type'    => QCL_RELATIONS_HAS_MANY,
      'target'  => array(
        'class'     => "qcl_access_model_Session",
        'dependent' => true
      )
    ),
    'Datasource_User' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_data_datasource_DbModel" )
    )
  );

  //-------------------------------------------------------------
  // Class properties
  //-------------------------------------------------------------

  /**
   * names that cannot be used as namedId
   */
  protected $reservedNames = array("anonymous","admin");

  /**
   * Cache for user permissions
   */
  private $permissions;

  /**
   * Cache for user roles
   */
  private $roles;

  /**
   * Cache for user groups
   */
  private $groups;

  //-------------------------------------------------------------
  // Initialization
  //-------------------------------------------------------------

  /**
   * Constructor
   * @return \qcl_access_model_User
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );

    $this->formData = array(
      'name'        => array(
        'name'        => "name",
        'label'       => $this->tr("Full name")
      ),
      'email'       => array(
        'label'       => $this->tr("Email address"),
        'placeholder' => $this->tr("Enter a valid Email address"),
        'validation'  => array(
          'validator'   => "email"
        )
      ),
      'password'    => array(
        'label'       => $this->tr("Password"),
        'type'        => "PasswordField",
        'value'       => "",
        'placeholder' => $this->tr("Leave blank if you don't want to change the password"),
        'marshaler'   => array(
          'unmarshal'  => array( 'callback' => array( "this", "checkFormPassword" ) )
        )
      ),
      'password2' => array(
        'label'       => $this->tr("Repeat password"),
        'type'        => "PasswordField",
        'value'       => "",
        'ignore'      => true,
        'placeholder' => $this->tr("Repeat password"),
        'marshaler'   => array(
          'unmarshal'  => array( 'callback' => array( "this", "checkFormPassword" ) )
        )
      )
    );
  }

  /**
   * Returns singleton instance.
   * @return qcl_access_model_User
   */
  static public function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  /**
   * Getter for permission model instance
   * @return qcl_access_model_Permission
   */
  protected function getPermissionModel()
  {
    return $this->getRoleModel()->getRelationBehavior()->getTargetModel("Permission_Role");
  }

  /**
   * Getter for role model instance
   * @return qcl_access_model_Role
   */
  protected function getRoleModel()
  {
    return $this->getRelationBehavior()->getTargetModel("User_Role");
  }

  /**
   * Getter for group model instance
   * @return qcl_access_model_Role
   */
  protected function getGroupModel()
  {
    return $this->getRelationBehavior()->getTargetModel("Group_User");
  }

  /**
   * Return the username (login name) of the current user.
   * Alias of getNamedId()
   * @return string
   * @todo rename to getUsername()
   */
  public function username()
  {
    return $this->namedId();
  }

  /**
   * Whether the given user name is the name of a guest (anonymous) user
   * @return bool True if user name is guest
   * @todo we need some more sophisticated stuff here
   */
  public function isAnonymous()
  {
    return (bool) $this->getAnonymous();
  }

  /**
   * Creates a new anonymous guest user
   * @throws LogicException
   * @return int user id of the new user record
   */
  public function createAnonymous()
  {

    /*
     * role model
     */
    $roleModel =$this->getRoleModel();
    try
    {
       $roleModel->load("anonymous");
    }
    catch( qcl_data_model_Exception $e)
    {
      throw new LogicException("No 'anonymous' role defined.");
    }

    $username = QCL_ACCESS_ANONYMOUS_USER_PREFIX . microtime_float()*100;
    $id = $this->create( $username, array(
      'anonymous' => true,
      'name'      => $this->tr("Anonymous User")
    ) );

    /*
     * link to anonymous role
     */
    try
    {
      $this->linkModel( $roleModel );
    }
    catch( qcl_data_model_RecordExistsException $e)
    {
      $this->warn( $e->getMessage() );
    }
    return $id;
  }

  /**
   * Returns the value of the "online" property. This doesn't guarantee that the 
   * value actually reflects the user's online status - it is the role of the access 
   * controller to set/unset it. 
   * @return boolean
   */
  function isOnline()
  {
    return $this->get("online");
  }

  /**
   * Checks if the current user has the given permission
   * respects wildcards, i.e. myapp.permissions.* covers
   * myapp.permissions.canDoFoo
   * @param string $requestedPermission the permission to check
   * @return bool
   * @todo cache result for performance
   */
  public function hasPermission( $requestedPermission )
  {
    static $cache = array();

    /*
     * cache result
     */
    if ( isset( $cache[$requestedPermission] ) )
    {
      return $cache[$requestedPermission];
    }
    else
    {
      $hasPermission = $this->hasPermissionImpl( $requestedPermission ) ;
      $cache[$requestedPermission] = $hasPermission;
      return $hasPermission;
    }
  }

  /**
   * The implementation of hasPermission
   * @param $requestedPermission
   * @return bool
   */
  protected function hasPermissionImpl ( $requestedPermission )
  {

    /*
     * get all permissions of the user
     */
    $permissions = $this->permissions();

    /*
     * use wildcard?
     */
    $useWildcard = strstr( $requestedPermission, "*" );

    /*
     * check if permission is granted
     */
    foreach( $permissions as $permission )
    {
      /*
       * exact match
       */
    	if ( $permission == $requestedPermission )
      {
        return true;
      }
      
      /*
       * else if the current permission name contains a wildcard
       */
      elseif ( ($pos = strpos($permission,"*") ) !== false )
      {
      	if ( substr($permission,0,$pos) == substr($requestedPermission,0,$pos) )
        {
          return true;
        }
      }
      
      /*
       * else if the requested permission contains a wildcard
       */
      elseif ( $useWildcard and ($pos = strpos($requestedPermission,"*")) !== false )
      {
        if ( substr($permission,0,$pos) == substr($requestedPermission,0,$pos) )
        {
          return true;
        }
      }
    }

    /*
     * check if permission exists at all
     */
    try
    {
      $this->getPermissionModel();
    }
    catch( qcl_data_model_RecordNotFoundException $e)
    {
      $this->warn("Permission '$requestedPermission' is not defined.");
    }

    return false;
  }

  /**
   * Whether the user has the given role
   * @param string $role
   * @return bool
   * @todo this can be optimized
   */
  function hasRole( $role )
  {
    return in_array( $role, $this->roles() );
  }

  /**
   * Returns list of roles that a user has.
   * @param bool $refresh
   *    If true, reload group memberships. If false(default),
   *    use cached values
   * @return string[]
   *    Array of role names
   */
  public function roles( $refresh=false )
  {
    if ( $refresh or ! $this->roles )
    {
      $roleModel = $this->getRoleModel();
      $roles = array();

      /*
       * simple user-role link
       * FIXME rewrite this, now group-specific roles ar NOT ignored
       */
      if ( $this->getApplication()->getIniValue("access.global_roles_only") )
      {
        try
        {
          $roleModel->findLinked( $this );
          while( $roleModel->loadNext() )
          {
            $roles[] = $roleModel->namedId();
          }
        }
        catch( qcl_data_model_RecordNotFoundException $e ){}
      }

      /*
       * users have roles dependent on group
       */
      else
      {
        $groups = $this->groups();
        $groupModel = $this->getGroupModel();

        /*
         * get the group-dependent role
         */
        foreach( $groups as $groupName )
        {
          $groupModel->load( $groupName );
          try
          {
            $roleModel->findLinked( $this, $groupModel );
            while( $roleModel->loadNext() )
            {
              $roles[] = $roleModel->namedId();
            }
          }
          catch( qcl_data_model_RecordNotFoundException $e ){}
        }

        /*
         * add the global roles
         */
        try
        {
          $roleModel->findLinkedNotDepends( $this, $groupModel );
          while( $roleModel->loadNext() )
          {
            $roles[] = $roleModel->namedId();
          }
        }
        catch( qcl_data_model_RecordNotFoundException $e ){}
      }
      $this->roles = array_unique( $roles );
    }
    return $this->roles;
  }

  /**
   * Returns list of groups that a user belongs to.
   *
   * @param bool $refresh
   *    If true, reload group memberships. If false(default),
   *    use cached values
   *
   * @return array
   *    Array of string values: group named ids.
   */
  public function groups( $refresh=false )
  {
    $groupModel = $this->getGroupModel();
    if ( $refresh or ! $this->groups )
    {
      $groups= array();
      try
      {
        $groupModel->findLinked( $this );
        while ( $groupModel->loadNext() )
        {
          $groups[] = $groupModel->namedId();
        }
      }
      catch( qcl_data_model_RecordNotFoundException $e){}
      $this->groups = $groups;
    }
    return $this->groups;
  }


  /**
   * Returns list of permissions that the user has
   *
   * @param bool $refresh
   *    If true, reload group memberships. If false(default),
   *    use cached values
   * @return string[]
   *    Array of permission ids
   */
  public function permissions( $refresh=false )
  {
    if ( $refresh or ! $this->permissions )
    {
      $roleModel = $this->getRoleModel();
      $roles = $this->roles( $refresh );
      $permissions = array();
      foreach( $roles as $roleName )
      {
        $roleModel->load( $roleName );
        $permissions = array_merge(
          $permissions,
          $roleModel->permissions()
        );
      }
      $this->permissions = $permissions;
    }
    return $this->permissions;
  }

  /**
   * Overridden to clear cached roles and permissions
   * @see class/qcl/data/model/qcl_data_model_AbstractNamedActiveRecord#load()
   */
  public function load( $id )
  {
    $this->roles = null;
    $this->permissions = null;
    $this->groups = null;
    return parent::load( $id );
  }

  /**
   * Resets the timestamp of the last action  for the current user
   * @return void
   */
  public function resetLastAction()
  {
    $this->set( "lastAction", new qcl_data_db_Timestamp("now") );
    $this->save();
  }

  /**
   * Returns number of seconds since resetLastAction() has been called
   * for the current user
   * @return int seconds
   */
  public function getSecondsSinceLastAction()
  {
    $now  = new qcl_data_db_Timestamp();
    $lastAction = $this->get( "lastAction" );
    if ( $lastAction )
    {
	    $d = $now->diff( $lastAction );
	    return (int) ( $d->s + ( 60 * $d->i ) + ( 3600 * $d->h ) + 3600*24 * $d->d );
    }
    return 0;
  }

  /**
   * Function to check the match between the password and the repeated
   * password
   * @param $value
   * @throws JsonRpcException
   * @return string
   */
  public function checkFormPassword ( $value )
  {
    if ( ! isset( $this->__password ) )
    {
      $this->__password = $value;
    }
    elseif ( $this->__password != $value )
    {
      throw new JsonRpcException( "Passwords do not match..." );
    }
    return $this->getApplication()->getAccessController()->generateHash( $value );
  }
  
  /**
   * Overridden. Checks if user is anonymous and inactive, and deletes user if so.
   * @see qcl_data_model_AbstractActiveRecord::checkExpiration()
   * @todo Unhardcode expiration time
   */
  protected function checkExpiration()
  {
  	$purge = ( $this->isAnonymous() && $this->getSecondsSinceLastAction() > 600 );
  	if ( $purge ) {
  		$this->delete();
  	}
  	return false;
  }
}
?>