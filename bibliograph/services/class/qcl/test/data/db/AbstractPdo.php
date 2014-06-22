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
 
qcl_import( "qcl_test_TestRunner" );

/**
 * Service class containing test methods
 */
abstract class qcl_test_data_db_AbstractPdo
  extends qcl_test_TestRunner
{

  abstract protected function getType();

  abstract protected function getDsn();
  
  abstract protected function getUser();
  
  abstract protected function getPassword();

  abstract protected function createAdapter();
  
  protected $adapter = null;
  
  public function setup()
  {
    $this->adapter = $this->createAdapter();
    $this->cleanup();
  }
  
  protected function getTestData()
  {
    return array(
      array("John","Doe",56),
      array("Mary","Poppins",21),
      array("Samuel","Jackson",35)
    );
  }
  
  public function test_createTable()
  {
    $this->adapter
      ->createTable("test")
      ->addColumn("test","firstname","VARCHAR(20)")
      ->addColumn("test","lastname", "VARCHAR(20)")
      ->addColumn("test","age","TINYINT");
    
    $this->info("Created table.");

    $testdata = $this->getTestData();
    foreach( $testdata as $row )
    {
      $data = array_combine( array("firstname", "lastname", "age"), $row );
      $this->adapter->insertRow( "test", $data  ); 
    }
    
    $this->info( sprintf( "Inserted %d rows.", count($testdata) ) );
  }

  public function test_testFetch()
  {
    $testdata = $this->getTestData();
    $this->adapter->query("select firstname, lastname, age from test;");
    while( $row1 = $this->adapter->fetch() )
    {
      $row2 = array_combine( array("firstname", "lastname", "age"), array_shift($testdata) );
      assert('$row1 == $row2', "Unexpected data from fetch operation");
      if( $row1 != $row2 )
      {
        $this->warn("Expected: " . json_encode($row2) );
        $this->warn("Received: " . json_encode($row1) );
      }
    }
  }
  
  public function test_fetchAll()
  {
    $testdata = $this->getTestData();
    $this->adapter->query("select firstname, lastname, age from test;");
    $data = $this->adapter->fetchAll();
    foreach( $data as $index => $row)
    {
      $row1 = array_values($row);
      $row2 = $testdata[$index];
      assert('$row1 == $row2', "Unexpected data from fetch operation");
      if( $row1 != $row2 )
      {
        $this->warn("Expected: " . json_encode($row2) );
        $this->warn("Received: " . json_encode($row1) );
      }      
    }
  }
  
  public function test_getResultValue()
  {
    $this->adapter->query("select age from test where lastname='Doe';");
    $age = $this->adapter->getResultValue();
    assert('$age==56',"Incorrect result value '$age'");
  }
  
  public function test_getResultValues()
  {
    $this->adapter->query("select firstname from test;");
    $names = $this->adapter->getResultValues();
    assert('$names==array("John","Mary","Samuel")',"Incorrect return values.");
  }
  
  public function test_existsWhere()
  {
    $test1 = $this->adapter->existsWhere("test", "firstname='John' and age=56");
    assert('$test1==true',"Assertion failed");
    $test2 = $this->adapter->existsWhere("test", "firstname='Mary' and age=56");
    assert('$test2==false',"Assertion failed");
  }
  
  public function test_dropTable()
  {
    $this->adapter->dropTable("test");
    assert('!$this->adapter->tableExists("test")',"Table was not removed" );
  }
  
  
  protected function tearDown()
  {
    $this->cleanup();
    $this->adapter->disconnect();
  }
  
  protected function cleanup()
  {
    if( $this->adapter->getPdoStatement() )
    {
      $this->adapter->getPdoStatement()->closeCursor();
    }
    if( $this->adapter->tableExists("test"))
    {
      $this->adapter->dropTable("test");
    }
  }
}