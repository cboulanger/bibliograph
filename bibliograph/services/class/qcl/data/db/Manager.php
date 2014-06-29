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
  protected function getDbType( $dsn )
  {
    $type = substr( $dsn, 0, strpos( $dsn, ":" ) );
    if (empty($type))
    {
      throw new LogicException("DSN '$dsn' is not valid - no db type information.");
    }
    return $type;
  }

  /**
   * Creates and caches a database connection object (adapter).
   * @param string|null $dsn Use dsn if given, otherwise use dsn
   *  of admin database as specified in the service.ini.php of the
   *  application.
   * @param string $user user name used for accesing the database
   * @param string $pass password
   * @throws LogicException
   * @return qcl_data_db_adapter_IAdapter
   */
  public function createAdapter( $dsn=null, $user=null, $pass=null )
  {
    if ( $dsn === null )
    {
      $dsn = $this->getApplication()->getDsn();
    }
    elseif ( ! is_string( $dsn ) ) // @todo use regexp
    {
      throw new LogicException("Invalid dsn '$dsn'.");
    }

    $this->log("Using dsn '$dsn' ",QCL_LOG_DB);

    /*
     * pool connection objects
     */
    if ( isset( $this->cache[$dsn] ) )
    {
      $this->log("Getting adapter from cache... ",QCL_LOG_DB);
      $adapter = $this->cache[$dsn];
    }

    /*
     * else connect to new database
     */
    else
    {
      $this->log("Creating new adapter ... ",QCL_LOG_DB);

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
      $adapter = new $class( $dsn, $user, $pass );

      /*
       * save adapter
       */
      $this->cache[$dsn] = $adapter;
    }

    return $adapter;
  }
}