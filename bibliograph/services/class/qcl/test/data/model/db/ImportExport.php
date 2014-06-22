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
qcl_import( "qcl_data_model_db_NamedActiveRecord" );
qcl_import( "qcl_data_model_import_Xml" );
qcl_import( "qcl_data_model_export_Xml" );

class imex_User extends qcl_data_model_db_ActiveRecord
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
    "User_Group" => array(
      "type"      => QCL_RELATIONS_HAS_ONE, //"n:1"
      "target"    => array( "class" => "imex_Group")
    ),

    /*
     * user belongs to several categories
     * the "jointable" key can can be omitted, defaults to "join_" plus
     * relation name (here, "join_user_category")
     */
    "User_Category" => array(
      "type"      => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,  // "n:n"
      "jointable" => "join_user_category", // can be omitted, see above
      "target"    => array( "class" => "imex_Category" )
    )
  );

  /**
   * Constructor. Initializes the properties and relationships
   */
  function __construct()
  {
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );
    parent::__construct();
  }
}

class imex_Group extends qcl_data_model_db_ActiveRecord
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
    "User_Group" => array(
      "type"      => QCL_RELATIONS_HAS_MANY, // "1:n"
      "target"    => array( "class" => "imex_User" )
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
class imex_Category extends qcl_data_model_db_NamedActiveRecord
{
  private $relations = array(
    /*
     * A category has many users and the other way round
     */
    "User_Category" => array(
      "type"      => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY, //  "n:n"
      "target"    => array( "class" => "imex_User" )
    )
  );

  function __construct()
  {
    $this->addRelations( $this->relations, __CLASS__ );
    parent::__construct();
  }
}

/**
 * Service class containing test methods
 */
class qcl_test_data_model_db_ImportExport
  extends qcl_test_TestRunner
{

  /**
   * @rpctest OK
   */
  public function test_testModel()
  {

    qcl_data_model_db_ActiveRecord::resetBehaviors();

    $time = microtime(true);

    $user     = new imex_User();
    $group    = new imex_Group();
    $category = new imex_Category();

    $this->info( sprintf(
      "Creating classes took %s seconds.",
      microtime(true) - $time
    ) );

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
      $category->create( $name );
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
      $group->loadWhere( array( "name" => $groupName ) );
      foreach ( $users as $userName )
      {
        $user->loadWhere( array( "name" => $userName ) );
        $group->linkModel( $user );
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
      $category->load( $categoryName );
      foreach ( $users as $userName )
      {
        $user->loadWhere( array( "name" => $userName ) );
        $category->linkModel( $user );
      }
    }

    $userXml     = $user->export( new qcl_data_model_export_Xml() );
    $groupXml    = $group->export( new qcl_data_model_export_Xml() ) ;
    $categoryXml = $category->export( new qcl_data_model_export_Xml() );

    /*
     * create md5 hashes to validate the result
     */
    $this->info( $userXml );
    $this->info( $groupXml );
    $this->info( $categoryXml );
    $this->info( "Hash for user xml: " . md5( $userXml ) );
    $this->info( "Hash for group xml: " . md5( $groupXml) );
    $this->info( "Hash for category xml: " . md5( $categoryXml ) );

    $userXmlHash     = "eeb93b093269986c6a7f6ce2baea73ac";
    $groupXmlHash    = "f338fc49f036c179b32daf25e27e8500";
    $categoryXmlHash = "8bc86efaf0e9c5222930b1610e01df06";

    $message = "XML was not correctly exported: ";
    assert( $userXmlHash, md5( $userXml ), $message . "user");
    assert( $groupXmlHash, md5( $groupXml ), $message . "group");
    assert( $categoryXmlHash, md5( $categoryXml ), $message . "category");

    /*
     * delete all records
     */
    $user->deleteAll();
    $group->deleteAll();
    $category->deleteAll();

    /*
     * re-import from xml
     */
    $user->import( new qcl_data_model_import_Xml( $userXml) );
    $group->import( new qcl_data_model_import_Xml( $groupXml ) );
    $category->import( new qcl_data_model_import_Xml( $categoryXml ) );

    /*
     * re-export and validate
     */
    $userXml     = $user->export( new qcl_data_model_export_Xml() );
    $groupXml    = $group->export( new qcl_data_model_export_Xml() ) ;
    $categoryXml = $category->export( new qcl_data_model_export_Xml() );

    $message = "XML was not correctly imported: ";
    assert( $userXmlHash, md5( $userXml ), $message . "user");
    assert( $groupXmlHash, md5( $groupXml ), $message . "group");
    assert( $categoryXmlHash, md5( $categoryXml ), $message . "category");

    /*
     * Cleanup
     */
    $user->destroy();
    $group->destroy();
    $category->destroy();

    return "OK";
  }


  function startLogging()
  {
    //$this->getLogger()->setFilterEnabled( QCL_LOG_DB, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, true );
  }

  function endLogging()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_DB, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, false );

  }
}

?>