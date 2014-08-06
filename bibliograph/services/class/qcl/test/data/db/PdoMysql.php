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
 
qcl_import( "qcl_test_data_db_AbstractPdo" );

/**
 * MySql Test
 */
class qcl_test_data_db_PdoMysql
  extends qcl_test_data_db_AbstractPdo
{
  protected function getType()
  {
    return "mysql";
  }
  
  function getDsn()
  {
    return $_ENV['QCL_TEST_MYSQL_DSN'];
  }
  
  protected function getUser()
  {
    return $_ENV['QCL_TEST_MYSQL_USER'];
  }
  
  protected function getPassword()
  {
    return $_ENV['QCL_TEST_MYSQL_PASSWORD'];
  }  
  
  protected function getOptions()
  {
    return array(
      PDO::ATTR_PERSISTENT         => true,
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
    );    
  }

}
