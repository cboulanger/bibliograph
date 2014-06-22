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
qcl_import( "qcl_data_db_adapter_PdoMysql" );
qcl_import( "qcl_data_db_Table" );

/**
 * Service class containing test methods
 */
class qcl_test_data_db_Table
  extends qcl_test_TestRunner
{

  public function test_testCreateTable()
  {
    $this->startLogging();

    list($dsn, $user, $pass) = $this->getDsnUserPassword();
    $adapter = new qcl_data_db_adapter_PdoMysql( $dsn, $user, $pass );
    $table   = new qcl_data_db_Table( "qcltest", $adapter );
    if ( $result = $table->exists() )
    {
      $this->info("Table exists, deleting...");
      $table->delete();
    }
    $table->create();
    $table->addColumn( "col1", "VARCHAR(32) NULL" );
    $table->addColumn( "col2", "INT(11) NOT NULL" );
    $table->addColumn( "col3", "INT(1) NULL" );

    $table->insertRows( array(
      array( "col1" => "row1", "col2" => 1, "col3" => true ),
      array( "col1" => "row2", "col2" => 2, "col3" => false ),
      array( "col1" => "row3", "col2" => 3, "col3" => NULL )
    ) );

    /*
     * test renaming
     */
    $col3def = $table->getColumnDefinition( "col3" );
    assert('\1');
    $table->renameColumn( "col3", "colDrei", $col3def );

    /*
     * test modifying
     */
    $col1def = $table->getColumnDefinition( "col1" );
    $table->modifyColumn( "col1", "varchar(100) NOT NULL");
    $col1def = $table->getColumnDefinition( "col1" );
    assert('\1');

    /*
     * delete table
     */
    $table->delete();

    $this->endLogging();

    return "OK";
  }

  /**
   * Returns the dsn, user and password of the database used by the application
   * @return array( dsn, user, password )
   */
  protected function getDsnUserPassword()
  {
    list( $user,$pass,$dbname, $dbtype, $host, $port )  =
      $this->getApplication()->getIniValues(array(
        "database.username","database.userpassw","database.admindb","database.type",
        "database.host","database.port"
      ) );
    return array( "$dbtype:host=$host;port=$port;dbname=$dbname", $user, $pass) ;
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
?>