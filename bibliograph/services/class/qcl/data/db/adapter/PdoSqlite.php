<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
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
qcl_import( "qcl_data_db_adapter_PdoMysql" );

/**
 * Adapter for PDO-compatible database drivers for the 
 * SQLite backend. Only a minimal subset of the required
 * methods are implemented. It is not yet possible to use
 * SQLite as a full-fledged data storage with full support 
 * for flexible schemas, indexing etc. 
 *
 * See http://www.php.net/manual/de/book.pdo.php
 */
class qcl_data_db_adapter_PdoSqlite
  extends qcl_data_db_adapter_PdoMysql
{
  
  /**
   * The databases that have been attached
   */
  protected $attachedDatabases = array();


  /**
   * Returns the default options for initiating a PDO connection
   * @return array
   */
  public function getDefaultOptions()
  {
    return array(
      PDO::ATTR_PERSISTENT => true,
      PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION
    );
  }
  
  /**
   * Extracts the values contained in the dsn into an associated array of
   * key-value pairs that can be set as  properties of this object.
   * @param $dsn
   * @return array
   */
  public function extractDsnProperties( $dsn )
  {
    $dsnprops = parent::extractDsnProperties( $dsn );
    unset( $dsnprops['host'] );
    unset( $dsnprops['port'] );
    return $dsnprops;
  }  


  //-------------------------------------------------------------
  // special purpose sql statements
  //-------------------------------------------------------------

  /**
   * Returns the column definition string to create a timestamp column that
   * automatically updates when the row is changed.
   * Returns false if not available.
   * @return string
   */
  public function currentTimestampSql()
  {
    return false;
  }

  /**
   * Returns the sql to do a fulltext search. 
   * @todo This hasn't been converted to sqlite yet.
   * @see http://www.phparch.com/2011/11/full-text-search-with-sqlite
   *
   * @param string $table
   *    The name of the table that is searched.
   * @param string $indexName
   *    The name of the index that is used in the search.
   * @param string $expr
   *    The search expression against which the records are matched.
   * @param string|null $mode
   *    Matching mode. Currently, "fuzzy" and "boolean" are implemented.
   *    "Fuzzy" returns records that are similar to the query, "boolean" forces strict
   *    comparisons. Defaults to "boolean", i.e. the searched records have
   *    to match all the words contained in the expression, unless
   *    they are prefixed by a minus sign ("-"), which indicates that the
   *    word should not be part of the record.
   * @throws InvalidArgumentException
   * @return string
   *    The sql expression that to use in a WHERE statement
   *
   * @todo this needs to be reworked in connection with
   *    qcl_data_model_db_QueryBehavior::createWhereStatement and
   *    qcl_data_db_Query
   */
  public function fullTextSql( $table, $indexName, $expr, $mode="boolean" )
  {
    
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  //-------------------------------------------------------------
  // Databases
  //-------------------------------------------------------------

  /**
   * Returns the path to the database file
   * @return string
   */
  protected function getDbFilePath( $database )
  {
    return QCL_SQLITE_DB_DATA_DIR . "/" . 
      $this->getApplication()->id() . "-" . $database . ".sqlite3";
  }
  
  /**
   * Creates a new database
   * @param string $database Database name
   */
  public function createDatabase( $database )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  
  /**
   * Tell driver to use the given database. Does nothing in SQLite.
   * @param $database
   * @return void
   */
  public function useDatabase( $database ){}

  /**
   * Tell driver to attach the given database. SQLite-only.
   * @param $database
   * @return void
   */
  protected function attachDatabase( $database )
  {
    if ( $database == $this->getDatabase() or in_array( $database, $this->attachedDatabases ) )
    {
      throw new LogicException("Database $database already attached");
    }
    $this->log("Attaching '$database'...");
    $dbfile = $this->getDbFilePath($database);
    $this->execute("ATTACH '$dbfile' AS '$database';");
    $this->attachedDatabases[] = $database;
  }  

  /**
   * Deletes a database
   * @param string $database Database name
   */
  public function dropDatabase( $database )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  //-------------------------------------------------------------
  // Database usage and introspection
  //-------------------------------------------------------------
  
  /**
   * Returns table structure as sql create statement
   * @param string $table table name
   * @return string
   */
  public function sqlDefinition( $table )
  {
    return $this->getResultValue("
      select `sql` from sqlite_master 
      WHERE type='table' and name='$table';
    ");
  }

  /**
   * Checks if table exists.
   * @param $table string
   * @return boolean
   */
  public function tableExists( $table )
  {
    $result = $this->getResultValue("
      SELECT count(*) FROM sqlite_master 
      WHERE type='table' AND name='$table';
    ");
    return (bool) $result;
  }

  /**
   * Checks if a function or stored procedure of this name exists in the database
   * @todo Not implemented for SQLite
   * @param $routine
   * @return boolean
   */
  public function routineExists( $routine )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Creates a table with an numeric, unique, self-incrementing id column,
   * which is also the primary key, with utf-8 as default character set. Throws
   * an error if table already exists.Returns the instance to allow chaining. 
   * @todo sanitize qcl string
   * @param string $table
   * @param string Optional id column name, defaults to 'id'
   * @return qcl_data_db_adapter_PdoMSqlite 
   */
  public function createTable( $table, $idCol="id" )
  {
    $table    = $this->formatTableName( $table );
    $idCol    = $this->formatColumnName( $idCol );
    $this->exec("
      CREATE TABLE $table ( $idCol INTEGER PRIMARY KEY AUTOINCREMENT );
    ");
    return $this;
  }

  /**
   * Format a table name for use in the sql query. This will
   * add the currently used database if not present.
   * @param string $table Table name
   * @return string
   * FIXME sanitize string
   */
  public function formatTableName( $table )
  {
    $parts = explode(".", $table);
    return '"' . implode('"."', $parts ) . '"';
  }
  
  /**
   * Deletes a table from the database.
   * WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string|array $table Drop one or several tables
   */
  public function dropTable( $table )
  {
    if ( is_array($table) )
    {
      foreach( $table as $t )
      {
        $this->dropTable( $t );
      }
      return;
    }
    $table = $this->formatTableName( $table );
    if( $this->pdoStatement )
    {
      $this->pdoStatement->closeCursor();
    }
    $this->exec("DROP TABLE $table" );
  }  

  /**
   * Format a column name for use in the sql query.
   * @param $column
   * @internal param string $table Column name
   * @return string
   * FIXME sanitize string
   */
  public function formatColumnName( $column )
  {
    return '"' . $column .'"';
  }

  /**
   * Checks if a column exists in the table. Caches the result for better
   * performance. To get around the cache, pass false as third parameter.
   *
   * @param string $table
   * @param string $column
   * @param boolean $useCache Default: true
   * @return boolean
   */
  public function columnExists( $table, $column, $useCache = true )
  {
    static $cache=array();
    
    if ( $useCache === false or ! isset( $cache[$table][$column] ) )
    {
      $t = $this->formatTableName($table);
      $records = $this->fetchAll("PRAGMA TABLE_INFO($t);");
      foreach($records as $record)
      {
        if ( $record['name'] == $column )
        {
          $cache[$table][$column] = true;
        }
      }
    }
    return $cache[$table][$column];
  }
  
  /**
   * Adds a column, throws error if column exists. Returns the instance to allow
   * chaining
   * @todo Sanitize sql string
   * @param string $table
   * @param string $column
   * @param string $definition
   * @param string $after Optional placement instruction. Must be one of (FIRST|AFTER xxx|LAST)
   * @throws Exception
   * @throws PDOException
   * @return qcl_data_db_adapter_PdoSqlite
   */
  public function addColumn( $table, $column, $definition, $after="")
  {
    if ( $this->columnExists( $table, $column ) )
    {
      throw new LogicException("Column $column already exists in table $table.");
    }
    $table  = $this->formatTableName( $table );
    $column = $this->formatColumnName( $column );
    $this->exec("ALTER TABLE $table ADD COLUMN $column $definition;");
    $this->log("Added $table.$column with definition '$definition'.");
    return $this;
  }

  /**
   * Returns the definition of a column as specified in a column definition in a
   * CREATE TABLE statement.
   * @param string $table
   * @param string $column
   * @return mixed string defintion or null if column does not exist
   */
  public function getColumnDefinition( $table, $column )
  {
    $table = $this->formatTableName($table);
    $stm = $this->query("PRAGMA TABLE_INFO($table);");
    while( $c = $stm->fetch() )
    {
      if ( $c['name'] == $column )
      {
        $definition = trim(str_replace("  "," ",implode(" ", array(
          $c['type'],
          ( ! $c['notnull']    ? "NULL": "NOT NULL" ),
          ( ! $c['dflt_value'] ? ""    : "DEFAULT " . $c['dflt_value'] )
        ))));
      }
    }
    if ( ! $definition )
    {
      throw new LogicException("Column '$column' does not exist.");
    }
    return $definition;
  }

  /**
   * Renames a column.
   * @todo not implemented for SQLite
   * WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string $table
   * @param string $oldColumn old column name
   * @param string $newColumn new column name
   * @param string $definition (required)
   * @param string $after Optional placement instruction. Must be one of (FIRST|AFTER xxx|LAST)
   * return void
   */
  public function renameColumn( $table, $oldColumn, $newColumn, $definition, $after="" )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Deletes a column from a table.
   * @todo not implemented for SQLite
   * WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string $table
   * @param string $column
   * return bool
   * @return int
   */
  public function dropColumn( $table, $column )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Returns the primary key(s) from a table.
   * @todo: not implemented for SQLite, returns always ["id"]
   * @param string $table table name
   * @return array array of columns
   */
  public function getPrimaryKey( $table )
  {
    return array("id");
    // http://www.sqlite.org/pragma.html#pragma_table_info
  }

  /**
   * Adds a primary key for the table
   * @todo not implemented for SQLite
   * @param string $table table name
   * @param string|array $columns column(s) for the primary key
   */
  public function addPrimaryKey( $table, $columns )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
    //http://stackoverflow.com/questions/946011/sqlite-add-primary-key
  }

  /**
   * Removes the primary key index from a table
   * @todo not implemented for SQLite
   * @param string $table
   */
  public function dropPrimaryKey( $table )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
    //http://stackoverflow.com/questions/946011/sqlite-add-primary-key
  }

  /**
   * Modify the primary key index from a table
   * @todo not implemented for SQLite
   * @param string $table
   * @param string[] $columns Columns for the primary key
   */
  public function modifyPrimaryKey( $table, $columns )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
    //http://stackoverflow.com/questions/946011/sqlite-add-primary-key
  }
  
  /**
   * Checks whether an index exists
   * @param $table
   * @param $index
   * @return boolean
   */
  public function indexExists( $table, $index )
  {
    return count( $this->fetchAll( "PRAGMA INDEX_INFO(`$index`);" ) ) > 0;
  }  

  /**
   * Removes an index
   * @param string $table table name
   * @param string $index index name
   * @return void
   */
  public function dropIndex( $table, $index )
  {
    $this->execute("DROP INDEX `$index`;");
  }

  /**
   * Return the columns in index
   * @param string $table
   * @param string $index
   * @return array Array of column names that belong to the index
   */
  public function getIndexColumns( $table, $index )
  {
    $records = $this->fetchAll( "PRAGMA INDEX_INFO(`$index`);" );
    $result = array();
    foreach( $records as $record )
    {
      $result[] = $record['name'];
    }
    return $result;
  }

  /**
   * Returns an array of index names defined in the table
   * @param $table
   * @return array
   */
  public function indexes( $table )
  {
    $table = $this->formatTableName( $table );
    $records = $this->getAllRecords("PRAGMA INDEX_LIST($table)");
    $result = array();
    foreach( $records as $record )
    {
      $result[] = $record['name'];
    }
    return $result;
  }

  /**
   * Adds a an index.
   * @param string $table Table name
   * @param string $type Any of (FULLTEXT|UNIQUE)
   * @param string $index index name
   * @param string|array $columns name(s) of column(s) in the index
   * @throws Exception
   * @throws PDOException
   * @return void
   */
  public function addIndex( $table, $type="UNIQUE", $index, $columns )
  {
    if ( !empty($type) and strtolower($type) !== "unique" )
    {
      throw InvalidArgumentException("Type must be 'UNIQUE' or empty.");
    }
    if ( $this->indexExists( $table, $index ) )
    {
      throw new LogicException("Index $index already exists in table $table.");
    }

    $table = $this->formatTableName( $table );
    $cols = '"' . implode('","', (array) $columns ) . '"';
    $this->exec("CREATE $type INDEX `$index` ON $table( $cols );");
    $this->log("Added $type index '$index' to table $table indexing columns " . implode(",",$columns) . ".");
  }

  /**
   * Creates a trigger that inserts a timestamp on
   * each newly created record.
   * @todo not yet implemented for SQLite
   * @param string $table Name of table
   * @param string $column Name of column that gets the timestamp
   */
  public function createTimestampTrigger( $table, $column )
  {
    // http://stackoverflow.com/questions/6578439/on-update-current-timestamp-with-sqlite
    $this->execute("
      CREATE TRIGGER IF NOT EXISTS :trigger
      AFTER UPDATE ON :table FOR EACH ROW
      BEGIN
        UPDATE :table SET :column = CURRENT_TIMESTAMP WHERE id = old.id;
      END;
      ", 
      array(
        ":trigger" => "update_modified_timestamp_" . $column,
        ":table"   => $table,
        ":column"  => $column 
      )
    );
  }

  /**
   * Creates triggers that will automatically create
   * a md5 hash string over a set of columns
   * @todo not implemented for SQLite
   */
  public function createHashTriggers ( $table, $columns )
  {
    throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Returns the current time from the database
   * @return string
   */
  public function getTime()
  {
    return $this->getResultValue("SELECT datetime('now')");
  }

  /**
   * Calculates the number of seconds passed between the
   * timestamp value parameter. The difference is calculated
   * by the db engine, not by php.
   * @param string $timestamp Timestamp value
   * @return string
   */
  public function getSecondsSince( $timestamp )
  {
    return $this->getResultValue("
      SELECT strftime('%s','now') - strftime('%s', :timestamp);
    ", array( ":timestamp" => $timestamp ) );
  }
  
  /**
   * Deletes all records from a table and resets the id counter.
   * @param string $table table name
   * @return bool Success
   */
  function truncate( $table )
  {
    $table = $this->formatTableName( $table );
    $this->execute( "DELETE FROM $table;");
    return true;
  }

  /**
   * Clear internal caches; This is not available for SQLite
   * @return void.
   */
  public function flush(){}
}