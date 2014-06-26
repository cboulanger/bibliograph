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

require_once dirname(dirname(__DIR__)) . "/bootstrap.php";

qcl_import("qcl_test_TestRunner");
qcl_import("qcl_data_db_adapter_Abstract");
qcl_import("qcl_data_db_Table");

/**
 * Service class containing test methods
 */
class qcl_test_data_db_Table
  extends qcl_test_TestRunner
{
  
  protected $adapter = null;
  
  protected $table = null;
  
  function test_createAdapter()
  {
    $dsn = $_ENV['QCL_TEST_DSN'] ?
      $_ENV['QCL_TEST_DSN'] : 
      "sqlite:" . QCL_SQLITE_DB_DATA_DIR . "/qcl-test-db.sqlite3";
    $this->adapter = qcl_data_db_adapter_Abstract::createAdapter( $dsn );
    $this->info("Created adapter for DSN '$dsn'");
  }

  public function test_testCreateTable()
  {
    //$this->startLogging();
    $table   = new qcl_data_db_Table( "qcltest", $this->adapter );
    if ( $result = $table->exists() )
    {
      $this->info("Table exists, deleting...");
      $table->delete();
    }
    
    $table->create();
    assert('$table->exists()===true',"Problem creating table.");
    
    $table->addColumn( "col1", "VARCHAR(32) NULL" );
    assert('$table->columnExists("col1")===true',"Problem creating column.");
    
    $table->addColumn( "col2", "INT(11) NOT NULL" );
    $table->addColumn( "col3", "INT(1) NULL" );
    
    $table->insertRows( array(
      array( "col1" => "row1", "col2" => 1, "col3" => true ),
      array( "col1" => "row2", "col2" => 2, "col3" => false ),
      array( "col1" => "row3", "col2" => 3, "col3" => NULL )
    ) );
  }
    
  function toBeRefactored()
  {
    /*
     * test renaming
     */
    $col3def = $table->getColumnDefinition( "col3" );
    $table->renameColumn( "col3", "colDrei", $col3def );

    /*
     * test modifying
     */
    $col1def = $table->getColumnDefinition( "col1" );
    $table->modifyColumn( "col1", "varchar(100) NOT NULL");
    $col1def = $table->getColumnDefinition( "col1" );
 

    /*
     * delete table
     */
    $table->delete();

    $this->endLogging();

    return "OK";
  }



  function startLogging()
  {
    $this->getLogger()->setFilterEnabled(array(QCL_LOG_DB,QCL_LOG_TABLES),true);
  }

  function endLogging()
  {
    $this->getLogger()->setFilterEnabled(array(QCL_LOG_DB,QCL_LOG_TABLES),false);
  }
}

qcl_test_data_db_Table::getInstance()->run();