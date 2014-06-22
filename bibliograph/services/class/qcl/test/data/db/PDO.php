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

/**
 * Service class containing test methods
 */
class qcl_test_data_db_PDO
  extends qcl_test_TestRunner
{

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

  public function test_testPdoFetch( $table = "access_config" )
  {

    list($dsn, $user, $pass) = $this->getDsnUserPassword();

    try
    {
      $options = array(
        PDO::ATTR_PERSISTENT         => true,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
      );

      $dbh = new PDO( $dsn, $user, $pass, $options );

      //$table = $dbh->quote( $table );
      $stm = $dbh->prepare( "SELECT * from $table" );
      $stm->execute();

      $this->info( "Found " . $stm->rowCount() . "rows" );

      $result = array();
      while ( $row = $stm->fetch( PDO::FETCH_ASSOC ) )
      {
        $result[] = $row;
      }

      $dbh = null;
    }
    catch (PDOException $e)
    {
      $this->raiseError( $e->getMessage() );
    }
    return $result;
  }


}
?>