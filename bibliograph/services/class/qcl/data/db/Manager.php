<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2014 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

/**
 * Database manager singleton, providing global access to a database object
 * with a unified API
 */
class qcl_data_db_Manager
  extends qcl_core_Object
{

  /**
   * Cache of database connections
   * @var array
   */
  private $cache = array();

  /**
   * The base string to which the database type will be appended
   * to form the class of the adapter
   * @var string
   */
  protected $adapterClassBase = "qcl_data_db_adapter_Pdo";

  /**
   * Returns singleton instance of the class. Must be called
   * statically.
   * @return qcl_data_db_Manager
   */
  static function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  /**
   * Returns the type of the database, based on the dsn string
   * @todo rename or purge, this looks like a getter method but is not one
   * @param $dsn
   * @return string
   */
  public function getDbType( $dsn )
  {
    return substr( $dsn, 0, strpos( $dsn, ":" ) );
  }

  /**
   * Creates and caches a database connection object (adapter).
   * @param string|null $dsn Use dsn if given, otherwise use dsn
   *  of admin database as specified in the service.ini.php of the
   *  application.
   * @param string $user user name used for accesing the database
   * @param string $pass password
   * @throws LogicException
   * @return qcl_data_db_type_Abstract
   */
  public function createAdapter( $dsn=null, $user=null, $pass=null )
  {
    if ( $dsn === null )
    {
      $app = $this->getApplication();
      if ( QCL_USE_EMBEDDED_DB and $app->useEmbeddedDatabase() )
      {
        if ( ! class_exists("SQLite3") )
        {
          throw new LogicException("Cannot use embedded database - SQLite3 is not available");
        }
        // use the file-based embedded SQLLite database
        $appid  = $app->id();
        $dbname = "main";
        $dbfile =  QCL_VAR_DIR . "/$appid-$dbname.sqlite3";
        $dsn    = "sqlite:$dbfile";  
      }
      else
      {
        // use the database specified in the ini file
        $dsn = $this->getApplication()->getIniValue("macros.dsn_admin");
        $dsn = str_replace("&",";", $dsn );        
      }
    }
    elseif ( ! is_string( $dsn ) ) // @todo use regexp
    {
      throw new LogicException("Invalid dsn '$dsn'.");
    }
    //$this->debug("Using dsn '$dsn' ",__CLASS__,__LINE__);

    /*
     * pool connection objects
     */
    if ( isset( $this->cache[$dsn] ) )
    {
      //$this->debug("Getting adapter from cache ",__CLASS__,__LINE__);
      $adapter = $this->cache[$dsn];
    }

    /*
     * else connect to new database
     */
    else
    {
      /*
       * type and class of database adapter
       */
      $type  = $this->getDbType( $dsn );
      $class = $this->adapterClassBase . ucfirst( $type ); // FIXME

      /*
       * include class file
       */
      qcl_import( $class );

      /*
       * user/password
       */
      if ( $user === null )
      {
        // @tod we don't need admin rights for all queries
        $user = $this->getApplication()->getIniValue("database.adminname");
        $pass = $this->getApplication()->getIniValue("database.adminpassw");
      }

      /*
       * create adapter
       */
      //$this->debug("New Connection with '$user', '$pass ",__CLASS__,__LINE__);
      $adapter = new $class( $dsn, $user, $pass );

      /*
       * save adapter
       */
      $this->cache[$dsn] = $adapter;
    }

    return $adapter;
  }
}
?>