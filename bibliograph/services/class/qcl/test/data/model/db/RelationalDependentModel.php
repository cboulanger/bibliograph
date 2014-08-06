<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2010 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_test_TestRunner");
qcl_import( "qcl_access_model_User" );


/**
 * Class to model the user data. As the other model classes defined
 * in this test, we subclass the available access model types and
 * selectively change some aspects, such as the names of tables and
 * columns. This is done to demonstrate these features but also in order
 * not to interfere with the real access model data.
 */
class rdm_User extends qcl_access_model_User
{
  /*
   * set a custom table name for this derived model
   */
  protected $tableName = "test_data_User";

  /**
   * Relations
   */
  private $relations = array(
    'User_Role' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "rdm_Role" ),
      'depends'     => array(
         array( 'relation'  => "Group_User" )
      )
    ),
    'Group_User' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "rdm_Group" )
    )
  );

  /*
   * Constructor. Applies some necessary modifications that are
   * needed in a derived model to work like the parent class.
   */
  function __construct()
  {
    /*
     * define properties / relations in the parent class
     */
    parent::__construct();

    /*
     * overwrite relation definition
     */
    $this->addRelations( $this->relations, __CLASS__ );

    /*
     * selectively alter the name join tables without redefining the
     * relations in this class. This is needed so that the join tables of
     * the "original" access models are not overwritten by this test.
     */
    $this->getRelationBehavior()->setJoinTableName( "User_Role", "test_join_User_Role" );
    $this->getRelationBehavior()->setJoinTableName( "Group_User", "test_join_Group_User" );

  }

  /**
   * Returns singleton instance of this class. Needed in each derived class that
   * has singleton behavior.
   * @return rdm_User
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }
}

/**
 * Class to model the role data
 * @see rdm_User
 */
class rdm_Role extends qcl_access_model_Role
{
  protected $tableName = "test_data_Role";

  /**
   * Relations
   */
  private $relations = array(
    'User_Role' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "rdm_User" )
    )
  );

  function __construct() {
    parent::__construct();
    $this->addRelations( $this->relations, __CLASS__ );
    $this->getRelationBehavior()->setJoinTableName( "User_Role", "test_join_User_Role" );
    $this->getRelationBehavior()->setJoinTableName( "Permission_Role", "test_join_Permission_Role" );
  }

  /**
   * @return rdm_Role
   */
  public static function getInstance() {
    return qcl_getInstance( __CLASS__ );
  }
}

/**
 * Class to model the permission data.
 * @see rdm_User
 */
class rdm_Permission extends qcl_access_model_Permission
{
  protected $tableName = "test_data_Permission";
  function __construct() {
    parent::__construct();
    $this->getRelationBehavior()->setJoinTableName( "Permission_Role", "test_join_Permission_Role" );
  }
  /**
   * @return rdm_Permission
   */
  public static function getInstance() {
    return qcl_getInstance( __CLASS__ );
  }
}


/**
 * Class to model the group data
 * @see rdm_User
 */
class rdm_Group extends qcl_access_model_Group
{
  protected $tableName = "test_data_Group";

  /**
   * Relations
   */
  private $relations = array(
    'Group_User' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "rdm_User" )
    )
  );

  function __construct() {
    parent::__construct();
    $this->addRelations( $this->relations, __CLASS__ );
    $this->getRelationBehavior()->setJoinTableName( "Group_User", "test_join_Group_User" );
  }
  /**
   * @return rdm_Group
   */
  public static function getInstance() {
    return qcl_getInstance( __CLASS__ );
  }
}


/**
 * Service class containing test methods for access package
 */
class qcl_test_data_model_db_RelationalDependentModel
  extends qcl_test_TestRunner
{

  /**
   * @rpctest OK
   */
  public function test_testModel()
  {
    try
    {
      //$this->getLogger()->setFilterEnabled(QCL_LOG_ACCESS,true);

      /*
       * create model instances
       */
      $user       = rdm_User::getInstance();
      $role       = rdm_Role::getInstance();
      $permission = rdm_Permission::getInstance();
      $group      = rdm_Group::getInstance();

      /*
       * users
       */
      $user->deleteAll();
      $user->create("user1",array( 'name' => "User 1", 'password' => "user1" ) );
      $user->create("user2",array( 'name' => "User 2", 'password' => "user2" ) );
      $user->create("user3",array( 'name' => "User 3", 'password' => "user3" ) );
      $user->create("user4",array( 'name' => "User 4", 'password' => "user4" ) );
      $user->create("user5",array( 'name' => "User 5", 'password' => "user5" ) );
      $user->create("user6",array( 'name' => "User 6", 'password' => "user6" ) );
      $user->create("user7",array( 'name' => "User 7", 'password' => "user7" ) );
      $user->create("user8",array( 'name' => "User 8", 'password' => "user8" ) );
      $user->create("user9",array( 'name' => "User 9", 'password' => "user9" ) );
      $user->create("admin",array( 'name' => "Administrator", 'password' => "admin" ) );

      /*
       * roles
       */
      $role->deleteAll();
      $role->create("anonymous", array( 'name' => "Anonymous user" ) );
      $role->create("user", array( 'name' => "Normal user" ) );
      $role->create("manager", array( 'name' => "Manager role" ) );
      $role->create("admin", array( 'name' => "Administrator role" ) );

      /*
       * permissions
       */
      $permission->deleteAll();
      $permission->create("viewRecord");
      $permission->create("createRecord");
      $permission->create("deleteRecord");
      $permission->create("manageUsers");
      $permission->create("manageConfig");

      /*
       * groups
       */
      $group->deleteAll();
      $group->create("group1");
      $group->create("group2");
      $group->create("group3");


      /*
       * link role to permission
       */
      $Role_Permission = array(
        'admin'     => array( "manageUsers" ),
        'manager'   => array( "deleteRecord" ),
        'user'      => array( "createRecord", "viewRecord" ),
        'anonymous' => array( "viewRecord" )
      );
      foreach( $Role_Permission as $roleName => $permissions )
      {
        $role->load( $roleName );
        foreach( $permissions as $permissionName )
        {
          $role->linkModel( $permission->load( $permissionName ) );
        }
      }

      /*
       * link role to user dependent on the group
       */
      $Group_Role_User = array(
        'group1' => array(
          'user'      => array( "user1", "user2", "user3", "admin" ),
          'manager'   => array( "user3", "admin" ),
          'admin'     => array( "admin" )
        ),
        'group2' => array(
          'user'      => array( "user4", "user5", "user6", "admin" ),
          'manager'   => array( "user6", "admin" ),
          'admin'     => array( "admin" )
        ),
        'group3' => array(
          'user'      => array( "user7", "user8", "user9", "admin" ),
          'manager'   => array( "user9", "admin" ),
          'admin'     => array( "admin" )
        )
      );
      foreach( $Group_Role_User as $groupName => $groupData )
      {
        $group->load( $groupName );
        foreach( $groupData as $roleName => $users )
        {
          $role->load( $roleName );
          foreach( $users as $userName )
          {
            $user->load( $userName );
            /*
             * link user to role depending on group
             */
            $user->linkModel( $role, $group );
          }
        }
      }

      /*
       * tests
       */
      $group->load("group1");
      $user->load("user1");
      $this->info( "User 1 has the following roles in group 1:" . implode(",",$user->roles( $group ) ) );
      $this->info( "which results in the following permissions: " . implode(",",$user->permissions( $group ) ) );

      $user->load("user3");
      $this->info( "User 3 has the following roles in group 1:" . implode(",",$user->roles( $group ) ) );
      $this->info( "which results in the following permissions: " . implode(",",$user->permissions( $group ) ) );

      $user->load("admin");
      $this->info( "Administrator has the following roles in group 1:" . implode(",",$user->roles( $group ) ) );
      $this->info( "which results in the following permissions: " . implode(",",$user->permissions( $group ) ) );

      $group->load("group2");

      $user->load("user1");
      $this->info( "User 1 has the following roles in group 2:" . implode(",",$user->roles( $group ) ) );
      $this->info( "which results in the following permissions: " . implode(",",$user->permissions( $group ) ) );

      $user->load("user4");
      $this->info( "User 4 has the following roles in group 2:" . implode(",",$user->roles( $group ) ) );
      $this->info( "which results in the following permissions: " . implode(",",$user->permissions( $group ) ) );

      $user->load("admin");
      $this->info( "Administrator has the following roles in group 2:" . implode(",",$user->roles( $group ) ) );
      $this->info( "which results in the following permissions: " . implode(",",$user->permissions( $group ) ) );

      /*
       * cleanup
       */
      $user->destroy();
      $role->destroy();
      $permission->destroy();
      $group->destroy();

      return "OK";

    }
    catch ( Exception $e )
    {
      $this->warn( $e );
      throw $e;
    }


  }


  protected function startLogging()
  {
    //$this->getLogger()->setFilterEnabled( QCL_LOG_ACCESS, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, true );
    //$this->getLogger()->setFilterEnabled( QCL_LOG_DB, true );

  }

  protected function endLogging()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_DB, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, false );
  }
}
