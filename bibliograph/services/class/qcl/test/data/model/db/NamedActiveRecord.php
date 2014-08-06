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
qcl_import( "qcl_data_model_db_NamedActiveRecord" );

class named_User
  extends qcl_data_model_db_NamedActiveRecord
{

  protected $tableName = "test_users";

  private $properties = array(
    "name" => array(
      "check"     => "string",
      "sqltype"   => "varchar(52)"
    ),
    "email"  => array(
      "check"     => "string",
      "sqltype"   => "varchar(100)"
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
class qcl_test_data_model_db_NamedActiveRecord
  extends qcl_test_TestRunner
{
  /**
   * @rpctest OK
   */
  public function test_testModel()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, true );
    $user = new named_User();
    $user->deleteAll();

    $randomdata = file( qcl_realpath("qcl/test/data/model/data/randomdata.csv") );
    $subscriber = false;
    $counter = 0;
    foreach( $randomdata as $line )
    {
      if ( ! trim( $line ) ) continue;
      $columns = explode( ";", $line );
      $user->create("user" . $counter++);
      $user->set( array(
        "name"        => trim($columns[0]),
        "email"       => trim($columns[1])
      ));
      $user->save();
    }

    //$this->getLogger()->setFilterEnabled( QCL_LOG_DB, true );

    $user = new named_User();
    $user->load( "user50" );
    $email = $user->getEmail();
    $this->info( "user50 has email <$email>" );
    assert("Duis.a.mi@aliquamenimnec.edu",$email,null,__CLASS__,__METHOD__);

    /*
     * try to create an existing user
     */
    try
    {
      $user->create("user50");
    }
    catch( qcl_data_model_RecordExistsException $e ) {}

    /*
     * create a new user
     */
    $user->create("foo");
    $user->setEmail("foo@bar.org");
    $user->setName("Foo B. Baz");
    $user->save();

    $user->load("foo");
    assert("foo@bar.org",$user->getEmail(),null,__CLASS__,__METHOD__);


    $user->destroy();

    return "OK";
  }


  function startLogging()
  {
    $this->getLogger()->setFilterEnabled( QCL_LOG_DB, true );
    $this->getLogger()->setFilterEnabled( QCL_LOG_TABLES, true );
  }

  function endLogging()
  {
    $this->getLogger()->setFilterEnabled(array(QCL_LOG_DB,QCL_LOG_TABLES),false);
  }
}

