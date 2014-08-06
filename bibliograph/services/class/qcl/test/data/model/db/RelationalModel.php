<?php

/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
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
qcl_import( "qcl_data_model_db_ActiveRecord" );

class relational_User extends qcl_data_model_db_ActiveRecord
{
  /*
   * Model properties. Foreign key properties will be
   * automatically created
   */
  private $properties = array(
    "name" => array(
      "check"     => "string",
      "sqltype"   => "varchar(32)"
    )
  );

  /*
   * the key with which the model id is identified in
   * "foreign" tables. Can be omitted, defaults to
   * class name plus "Id" (here, "UserId")
   */
  protected $foreignKey = "UserId";

  /*
   * relations (associations) of the model
   */
  private $relations = array(
    /*
     * user belongs to exactly one group
     */
    "relational_User_Group" => array(
      "type"      => QCL_RELATIONS_HAS_ONE, //"n:1"
      "target"    => array( "class" => "relational_Group")
    ),

    /*
     * user belongs to several categories
     * the "jointable" key can can be omitted, defaults to "join_" plus
     * relation name (here, "join_user_category")
     */
    "relational_User_Category" => array(
      "type"      => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,  // "n:n"
      "jointable" => "join_user_category", // can be omitted, see above
      "target"    => array( "class" => "relational_Category" )
    ),

    /*
     * users have a history of actions which needs to be
     * deleted when the user is deleted
     */
    "relational_User_History" => array(
      "type"    => QCL_RELATIONS_HAS_MANY, // "1:n"
      "target"  => array(
        "class"     => "relational_History",
        "dependent" => true // dependent targets are removed upon deletion of the "parent" model record
      )
    )
  );

  /**
   * Constructor. Initializes the properties and relationships
   */
  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
  }
}

class relational_Group extends qcl_data_model_db_ActiveRecord
{
  private $properties = array(
    "name" => array(
      "check"     => "string",
      "sqltype"   => "varchar(32)"
    )
  );

  private $relations = array(
    /*
     * group has many users
     */
    "relational_User_Group" => array(
      "type"      => QCL_RELATIONS_HAS_MANY, // "1:n"
      "target"    => array( "class" => "relational_User" )
    )
  );

  function __construct()
  {
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
    parent::__construct();
  }
}

/**
 * Categories
 */
class relational_Category extends qcl_data_model_db_ActiveRecord
{

  private $properties = array(
    "name" => array(
      "check"     => "string",
      "sqltype"   => "varchar(32)"
    )
  );

  private $relations = array(
    /*
     * A category has many users and the other way round
     */
    "relational_User_Category" => array(
      "type"      => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY, //  "n:n"
      "target"    => array( "class" => "relational_User" )
    )
  );

  function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
  }
}


/**
 * History model, has a reference to a user and one to an
 * action
 */
class relational_History extends qcl_data_model_db_ActiveRecord
{

  private $relations = array(
    /*
     * A history record belongs to exactly one user and
     * will be deleted with that user (this is set up in
     * the "relations/user_history/target/dependent" entry
     * in the relational_User model class).
     */
    "relational_User_History" => array(
      "type"      => QCL_RELATIONS_HAS_ONE, // "n:1"
      "target"    => array(
        "class" => "relational_User"
      )
    ),
    /*
     * A history record is also linked to an Action
     * model
     */
    "relational_Action_History" => array(
      "type"      => QCL_RELATIONS_HAS_ONE, //  "n:1"
      "target"    => array(
        "class" => "relational_Action"
      )
    )
  );

  function __construct()
  {
    $this->addRelations( $this->relations, __CLASS__ );
    parent::__construct();
  }
}

class relational_Action extends qcl_data_model_db_ActiveRecord
{

  private $properties = array(
    "description" => array(
      "check"     => "string",
      "sqltype"   => "varchar(32)"
    )
  );

  private $relations = array(
    "relational_Action_History" => array(
      "type"      => QCL_RELATIONS_HAS_MANY, // same as "1:n"
      "target"    => array(
        "class"   => "relational_History"
      )
    )
  );

  function __construct()
  {
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
    parent::__construct();
  }
}

/**
 * Service class containing test methods
 */
class qcl_test_data_model_db_RelationalModel
  extends qcl_test_TestRunner
{

  /**
   * @rpctest OK
   */
  public function test_testModel()
  {

    $this->startTimer();

    /**
     * Reset model caches
     */
    qcl_data_model_db_ActiveRecord::resetBehaviors();

    /*
     * instantiate all needed models
     */
    $singletons = true;
    $user     = $singletons ? relational_User::getInstance() : new relational_User();
    $action   = $singletons ? relational_Action::getInstance() : new relational_Action();
    $history  = $singletons ? relational_History::getInstance() :new relational_History();
    $group    = $singletons ? relational_Group::getInstance() : new relational_Group();
    $category = $singletons ? relational_Category::getInstance() : new relational_Category();

    /*
     * create users
     */
    $user->deleteAll();

    $users = array(
      "mehmet", "ling", "john",
      "fritz", "cathrine", "peer",
      "enrico", "anusheh","akiko"
    );
    foreach( $users as $name )
    {
      $user->create( array( "name" => $name ) );
    }

    /*
     * create groups
     */
    $group->deleteAll();
    $groups = array( "customer", "employee", "manager" );
    foreach( $groups as $name )
    {
      $group->create( array( "name" => $name ) );
    }

    /*
     * create category
     */
    $category->deleteAll();
    $categories = array( "music", "sports", "health", "computer" );
    foreach( $categories as $name )
    {
      $category->create( array( "name" => $name ) );
    }

    /*
     * create user-group relations
     */
    $groups = array(
      "customer"  => array( "fritz", "cathrine","anusheh", "enrico" ),
       "employee" => array( "ling", "john","peer" ),
       "manager"  => array( "mehmet","akiko" )
    );
    foreach( $groups as $groupName => $users )
    {
      $this->info("Adding users to group '$groupName'");
      $group->loadWhere( array( "name" => $groupName ) );
      foreach ( $users as $userName )
      {
        $this->info("   Adding user '$userName'...");
        $user->loadWhere( array( "name" => $userName ) );
        $group->linkModel( $user );
        assert(true, $group->islinkedModel( $user ));

//        $this->info("   Removing user '$userName'...");
        $group->unlinkModel( $user );
        assert(false, $group->islinkedModel( $user ));

//        $this->info("   Re-adding user '$userName'...");
        $group->linkModel( $user );
        assert(true, $group->islinkedModel( $user ));
      }
    }

    /*
     * create user-category relations
     */
    $categories = array(
       "music"    => array( "fritz", "cathrine","anusheh", "ling" ),
       "sports"   => array( "ling", "john","cathrine", "mehmet"),
       "health"   => array( "mehmet","akiko",  "enrico" ),
       "computer" => array( "fritz", "anusheh", "enrico", "peer", "ling" )
    );
    foreach( $categories as $categoryName => $users )
    {
      $this->info("Adding users to category '$categoryName'");
      $category->loadWhere( array( "name" => $categoryName ) );
      foreach ( $users as $userName )
      {
        $this->info("   Adding user '$userName'...");
        $user->loadWhere( array( "name" => $userName ) );
        $category->linkModel( $user );
        assert(true, $category->islinkedModel( $user ));

//        $this->info("   Removing user '$userName'...");
        $category->unlinkModel( $user );
        assert(false, $category->islinkedModel( $user ));

//        $this->info("   Re-adding user '$userName'...");
        $category->linkModel( $user );
        assert(true, $category->islinkedModel( $user ));
      }
    }

    /*
     * pseudo user "history"
     */
    $this->info("Creating random user history...");

    $history->deleteAll();
    $action->deleteAll();

    /*
     * create actions
     */
    $actions = array(
      "logged on", "logged off", "bought stuff",
      "wrote review","asked question","answered question"
    );
    foreach ( $actions as $description )
    {
      $action->create( array(
        'description' => $description
      ) );
    }

    /*
     * create a user history
     */
    foreach( $users as $name )
    {
      /*
       * load the user record
       */
      $user->loadWhere( array( 'name' => $name ) );

      for( $i=0; $i < rand( 5,10 ); $i++)
      {
        /*
         * load a random action record
         */
        $action->load( rand(1,6) );

//        $this->info( sprintf(
//          "  %s: %s %s",
//          $history->getCreated(), $user->getName(), $action->getDescription()
//        ) );

        /*
         * create a history record and link it to the action
         */
        $history->create();
        $history->linkModel( $action );

        /*
         * link the user and the history record
         */
        $history->linkModel( $user );


      }
    }



    /*
     * iterate through the groups.
     */
    $q1 = $group->findAll();
    assert(3, $q1->getRowCount() );
    $this->info( sprintf( "We have %s groups", $q1->getRowCount() ), null, __CLASS__,__LINE__ );

    while( $group->loadNext() )
    {
      $q2 = $user->findLinked( $group );
      $members = array();
      while( $user->loadNext() )
      {
        $members[] = $user->getName();
      }
      $this->info( sprintf(
        "Group '%s' has %s members: %s",
        $group->getName(), $q2->getRowCount(), implode( ",", $members )
      ) );
    }
//$this->startLogging();
    /*
     * delete a user, this should delete his/her history
     */
    $user->loadWhere( array( 'name' => "peer" ) );
    $id = $user->id();
    $count = $history->find( new qcl_data_db_Query( array(
      'where' => array( 'UserId' => $id )
    ) ) );
    $this->info("'peer' has $count history records.");

    $this->info("Deleting user 'peer' with id#$id");
    $user->delete();

    assert( 0, $user->countWhere( array( 'name' => "peer" ) ) , null, __CLASS__,__LINE__);

    $count = $history->find( new qcl_data_db_Query( array(
      'where' => array( 'UserId' => $id )
    ) ) );
    $this->info("'peer' has $count history records.");
    assert( 0, $count , null, __CLASS__,__LINE__);

    /*
     * Cleanup
     */
    $user->destroy();
    $group->destroy();
    $category->destroy();
    $action->destroy();
    $history->destroy();

    $this->info("Execution took " .$this->timerAsSeconds() . " seconds.");

    return "OK";
  }


  function startLogging()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_DB, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, true );
  }

  function endLogging()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_DB, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, false );
  }
}

