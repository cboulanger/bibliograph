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
  
  public function test_updateRow()
  {
    $data = array( "age" => 99 );
    $id = $this->adapter->getResultValue("SELECT id FROM test WHERE lastname='Doe';");
    $this->adapter->updateRow( "test", $data, "id", $id );
    $age = $this->adapter->getResultValue("SELECT age FROM test WHERE lastname='Doe';");
    assert('$age==99',"Update row failed");
  }
  
  public function test_deleteWhere()
  {
    $this->adapter->insertRow("test", 
      array("firstname"=>"Pierre","lastname"=>"Richard","age"=>75));
    $rowExists = $this->adapter->existsWhere("test","lastname='Richard'");
    assert('$rowExists==true', "Insert failed");
    $this->adapter->deleteWhere("test","lastname='Richard'");
    $rowExists = $this->adapter->existsWhere("test","lastname='Richard'");
    assert('$rowExists==false', "Delete failed");
  }
  
  public function test_columnDefinition()
  {
    $def1 = "VARCHAR(20) NULL DEFAULT 'unkown'";
    $this->adapter->addColumn("test","profession", $def1 );
    $def2 = $this->adapter->getColumnDefinition("test","profession");
    assert('$def1==$def2',"Wrong SQL Definition");
  }
  
  public function test_createIndex()
  {
    $columns1 = array("firstname","lastname");
    $this->adapter->addIndex( "test", "unique", "uniqueName", $columns1 );
    $exists   = $this->adapter->indexExists( "test", "uniqueName" );
    assert('$exists==true', "Problem creating index.");
    $columns2 = $this->adapter->getIndexColumns( "test", "uniqueName" );
    assert('$columns1==$columns2', "Problem with index columns.");
    $this->info("Index created");
    try
    {
      $this->adapter->insertRow("test",array("firstname"=>"John","lastname"=>"Doe"));
      $this->warn("Unique index not working: no exeption thrown on duplicate entry.");
    }
    catch( PDOException $e)
    {
      $this->info("Expected exception thrown." );
    }
  }
  
  function test_dropIndex()
  {
    $this->adapter->dropIndex("test","uniqueName");
    $indexExists = $this->adapter->indexExists("test","uniqueName");
    assert('$indexExists===false',"Problem dropping index.");
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
    if( $this->adapter->tableExists("test"))
    {
      $this->adapter->dropTable("test");
    }
  }
}