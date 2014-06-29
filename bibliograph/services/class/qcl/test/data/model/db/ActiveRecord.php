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

require_once dirname(dirname(dirname(__DIR__))) . "/bootstrap.php";

qcl_import("qcl_test_TestRunner");
qcl_import("qcl_test_application_Application");
qcl_import("qcl_data_model_db_ActiveRecord");
qcl_import("qcl_data_db_Timestamp");

class active_Member
  extends qcl_data_model_db_ActiveRecord
{

  protected $tableName = "test_members";

  private $properties = array(
    "name" => array(
      "check"     => "string",
      "sqltype"   => "varchar(52)"
    ),
    "email"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(100)",
      "nullable"  => true,
    ),
    "city"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(50)",
      "nullable"  => true,
      "column"    => "town"
    ),
    "country"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(50)",
      "nullable"  => true,
    ),
    "newsletter"  => array(
      "check"     => "boolean",
      "sqltype"   => "int(1)",
      "init"      => false,
      "nullable"  => false,
      "column"    => "isSubscriber"
    )
  );

  function __construct()
  {
    $this->addProperties( $this->properties );
    parent::__construct();
  }
}

/**
 * Service class containing test methods
 */
class qcl_test_data_model_db_ActiveRecord
  extends qcl_test_TestRunner
{

  /**
   * @var active_Member
   */
  protected $member = null;

  public function test_createModel()
  {

    qcl_application_Application::setInstance(new qcl_test_application_Application());
    qcl_data_model_db_ActiveRecord::resetBehaviors();

    //$this->startLogging();

    /*
     * create model object
     */
    $this->member = new active_Member();

    $dsn = "sqlite:" . QCL_SQLITE_DB_DATA_DIR . "/qcl-test-model.sqlite3"; // todo get from environment!

    $this->member->getQueryBehavior()->setAdapter(
      qcl_data_db_Manager::getInstance()->createAdapter($dsn)
    );
    $this->info("DSN: $dsn");

    try
    {
      $this->member->set("name","Foo");
      $this->warn( "Unloaded model must throw exception" );
    }
    catch( qcl_data_model_NoRecordLoadedException $e )
    {
      $this->info("Trying to set property on unloaded model correctly threw exception.");
    }
  }

  public function test_loadData()
  {
    $this->info("Deleting all data...");
    $this->member->deleteAll();

    $this->info("Loading new data...");
    $data = file( qcl_realpath("qcl/test/data/model/data/data.csv") );

    $subscriber = false;

    foreach( $data as $line )
    {
      if ( ! trim( $line ) ) continue;
      $columns = explode( ";", $line );
      $this->member->create();
      $this->member->set( array(
        "name"        => trim($columns[0]),
        "email"       => trim($columns[1]),
        "address"     => trim($columns[2]),
        "city"        => trim($columns[3]),
        "country"     => trim($columns[4]),
        "newsletter"  => $subscriber = ! $subscriber
      ));
      $this->member->save();
    }

    assert('$this->member->countRecords()==200',"Incorrect number of records");
    $this->info("Created 200 records.");
  }

  public function test_testModel()
  {
    //$this->startLogging();
    $this->member->findWhere( array(
      "name"        => array( "LIKE" , "B%"),
      "newsletter"  => true
    ) );
    $count = $this->member->rowCount();
    //$this->endLogging();

    $this->info( "We have $count newsletter subscribers that start with 'B':");
    assert('$count==7',"Expected :7, got: $count");

    /*
     * querying records
     */
    $subscribers = array();
    while( $this->member->loadNext() )
    {
      $subscribers[] = $this->member->getName() . " <" . $this->member->getEmail() . ">";
    }
    $subscribers = implode(", ", $subscribers );

    $expected = "Bailey, Madaline O. <amet.consectetuer@nullaante.ca>, Buck, Gabriel R. <molestie.in@eu.com>, Bolton, Graham D. <Proin@cursus.org>, Baxter, Samson V. <lobortis.mauris@odioNaminterdum.edu>, Bender, Lisandra C. <Suspendisse@Donec.edu>, Bullock, Thaddeus I. <augue.ut.lacus@eget.com>, Benson, Erin M. <at.pede.Cras@acipsum.edu>";
    assert('$expected==$subscribers', "Expected: '$expected'\n      Got:'$subscribers'");

    /*
     * updating across records without changing the active record
     */
    $this->info("Making all members from China subscriber");
    $newSubscribers = $this->member->updateWhere(
      array( "newsletter" => true ),
      array( "country"    => "China" )
    );
    $this->info("We have $newSubscribers new subscribers from China now.");

    /*
     * deleting records: we don't like ".com" addresses from germany
     */
    $count = $this->member->deleteWhere(array(
      "email" => array( "LIKE", "%.com" ),
      "country" => "Germany"
    ));
    $this->info("Deleted $count .com - address from Germany.");

    /*
     * cleanup
     */
    $this->info("Cleaning up...");
    //$this->member->destroy();
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
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_PROPERTIES, false );
    $this->getLogger()->setFilterEnabled( QCL_LOG_MODEL_RELATIONS, false );
  }
}

qcl_test_data_model_db_ActiveRecord::getInstance()->run();
