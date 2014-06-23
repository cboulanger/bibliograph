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
 
qcl_import("qcl_test_data_db_AbstractPdo");
qcl_import("qcl_data_db_adapter_PdoSqlite");

/**
 *
 */
class qcl_test_data_db_PdoSqlite
  extends qcl_test_data_db_AbstractPdo
{
  protected function getType()
  {
    return "sqlite";
  }
  
  private function getDbFile()
  {
    return QCL_SQLITE_DB_DATA_DIR . "/qcl-test-db.sqlite3";
  }
  
  protected function getDsn()
  {
    return "sqlite:" . $this->getDbFile();
  }
  
  protected function getUser(){}
  
  protected function getPassword(){}
  
  protected function createAdapter()
  {
    return new qcl_data_db_adapter_PdoSQLite( $this->getDsn() );
  }
  
  protected function tearDown()
  {
    unlink( $this->getDbFile() );
  }
}

qcl_test_data_db_PdoSqlite::getInstance()->run();