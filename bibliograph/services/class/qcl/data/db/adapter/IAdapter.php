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
 *  * Oliver Friedrich (jesus77)
 */

/**
 * Interface for database adapter
 */
interface qcl_data_db_adapter_IAdapter
  extends Iterator
{


  //-------------------------------------------------------------
  // initialization
  //-------------------------------------------------------------

  /**
   * Constructor. Initializes adapter.
   * @param string $dsn
   * @param string $user
   * @param string $pass
   * @param string|null $options Optional options to pass to the driver
   * @return \qcl_data_db_adapter_IAdapter
   */
  function __construct( $dsn, $user=null, $pass=null, $options=null );

  /**
   * Hook for initialization stuff called from the constructor.
   */
  public function init();

  //-------------------------------------------------------------
  // accessors
  //-------------------------------------------------------------

  /**
   * Getter for database handler object
   * @return object
   */
  public function db();

  /**
   * Getter for DSN
   * @return string
   */
  public function getDsn();


  /**
   * Extracts the values contained in the dsn into an associated array of
   * key-value pairs that can be set as  properties of this object.
   * @param $dsn
   * @return array
   */
  public static function extractDsnProperties( $dsn );


  /**
   * Returns database type, such as "mysql"
   * @return string
   */
  public function getType();


  /**
   * getter for database user
   * @return string
   */
  public function getUser();




  //-------------------------------------------------------------
  // main API
  //-------------------------------------------------------------

  /**
   * Connects to database
   * @return void
   */
  public function connect();

  /**
   * Disconnects from database
   * @return void
   */
  public function disconnect();

  /**
   * Executes an SQL statement, returning a result set as a PDOStatement object. If
   * supported by the adapter, use PDO-style prepare syntax to prepare the query using
   * serial or named parameters. See http://www.php.net/manual/de/pdo.prepare.php and
   * http://www.php.net/manual/de/pdostatement.execute.php
   *
   * @param string $sql
   * @param array|null $parameters Optional map or array of parameters for use in an
   *  `execute` operation
   * @param array|null $parameter_types Optional map of parameter types for use in an
   *  `execute` operation. Works only with named parameters. Only needed for parameters
   *  that have a different type than the default string type.
   * @param array|null $driver_options Optional map of options passed to the driver
   * @return PDOStatement
   */
  public function query( $sql, $parameters=null, $parameter_types=null, $driver_options=null );

  /**
   * Executes an SQL statement in a single method call, returning the number of rows
   * affected by the statement. No parameter replacement or checking is done, so
   * make sure not to pass any user-generated data as part of the sql statement.
   *
   * @param string $sql
   * @return int
   */
  public function exec( $sql );

  /**
   * Executes an SQL statement. Alias of query().
   * @param string $sql
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * @return PDOStatement
   */
  public function execute( $sql, $parameters=null, $parameter_types=null );

  /**
   * Returns the number of rows affected by the last SQL statement (INSERT, UPDATE,
   * DELETE). For MySql, this also returns the number of records found in the
   * last SELECT query. For other drivers, a similar behavior might have to be
   * simulated otherwise.
   * @param string|null $sql Optional sql needed in case the driver doesn't support
   * row count for select queries.
   * @param array|null $parameters
   * @param array|null $parameter_types
   * @return int
   */
  public function rowCount($sql=null,$parameters=null,$parameter_types=null);

  /**
   * Fetches the first or next row from a result set
   * @param string|null $sql Optional sql query. This allows
   * to query and fetch the result in one go.
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * @return array
   */
  public function fetch( $sql=null, $parameters=null, $parameter_types=null );

  /**
   * Returns an array containing all of the result set rows
   * @param string|null $sql Optional sql query. This allows
   * to query and fetch the results in one go.
   * @param array|null $parameters Optional map or array of parameters for use in a `execute` operation
   * @param array|null $parameter_types Optional map of parameter types for use in a `execute` operation.
   * @see query()
   * @return array
   */
  public function fetchAll( $sql=null, $parameters=null, $parameter_types=null );

  /**
   * Returns a single column from the next row of a result set
   * @param $column_number
   * @param string|null $sql Optional sql to query
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * @return mixed
   */
  public function fetchColumn ( $column_number, $sql = null, $parameters=null, $parameter_types=null );

  /**
   * Returns the value of the first column of the first/next row of the result set.
   * Useful for example for "SELECT count(*) ... " queries
   * @param string|null $sql Optional sql to query
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * return mixed
   */
  public function getResultValue( $sql=null, $parameters=null, $parameter_types=null );

  /**
   * Returns the values of the first column of each row of the result set.
   * Useful if only one column is queried.
   * @param string|null $sql Optional sql to query
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * @return array
   */
  public function getResultValues( $sql, $parameters=null, $parameter_types=null );

  /**
   * Checks whether a certain where statement returns any rows.
   * @param string $table Table name
   * @param string $where 'where' statement. Make sure this string contains no
   * user-generated input except by using named parameters.
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * @return bool
   */
  public function existsWhere( $table, $where, $parameters=null, $parameter_types=null );

  /**
   * Inserts a record into a table and returns its id.
   * @param string $table table name
   * @param array $data associative array with the column names as keys and the column data as values
   * @return int the id of the inserted row (only if auto_incremnt-key)
   */
  public function insertRow( $table, $data );

  /**
   * Returns the ID of the last inserted row or sequence value
   * @return int
   */
  public function lastInsertId();

  /**
   * Updates a record in a table identified by id.
   * @param string $table table name
   * @param array $data associative array with the column names as keys and the column data as values
   * @param string $idColumn name of column containing the record id, defaults to "id"
   * @param int|null $id Optional id value, if id is not part of the data.
   */
  public function updateRow( $table, $data, $idColumn="id", $id=null );

  /**
   * Updates records in a table identified by a where condition
   * @param string $table table name
   * @param array $data associative array with the column names as keys and the column data as values
   * @param string $where Where condition
   * @param array|null $parameters Optional parameters to the where condition, @see query()
   * @param array|null $parameter_types Optional parameter types, @see query()
   */
  public function updateWhere ( $table, $data, $where, $parameters=array(), $parameter_types=array() );

  /**
   * Deletes one or several records in a table identified by id(s)
   * @param string $table table name
   * @param int|array $ids (array of) record id(s)
   * @param string $idColumn name of column containing the record id
   * @return bool success
   */
  public function deleteRow( $table, $ids, $idColumn="id" );

  /**
   * Deletes one or more records in a table matching a where condition
   * @param $table
   * @param string $where where condition. Do include any user-generated content, use
   *   parameters instead.
   * @param array|null $parameters Optional parameters to the where condition, @see query()
   * @param array|null $parameter_types Optional parameter types, @see query()
   * @return
   */
  function deleteWhere ( $table, $where, $parameters=null, $parameter_types=null );

  /**
   * Deletes all records from a table and resets the id counter.
   * @param string $table table name
   */
  function truncate( $table );

  /**
   * Counts records in a table matching a where condition
   * @param $table
   * @param string $where where condition
   * @param array|null $parameters Optional parameters to the where condition, @see query()
   * @param array|null $parameter_types Optional parameter types, @see query()
   * @return
   */
  public function countWhere( $table, $where, $parameters=null, $parameter_types=null );

  //-------------------------------------------------------------
  // special purpose sql statements
  //-------------------------------------------------------------

  /**
   * Returns the column definition string to create a timestamp column that
   * automatically updates when the row is changed.
   * @return string
   */
  public function currentTimestampSql();

  /**
   * Returns the sql to do a fulltext search. Uses boolean mode.
   * @param string $table
   * @param string $indexName
   * @param string $expr
   * @param string|null $mode Matching mode. Defaults to "and", i.e. the
   * searched records have to match all the words contained in the expression, unless
   * they are prefixed by a minus sign ("-"), which indicates that the word should
   * not be part of the record.
   * Currently, only "and" is implemented.
   * @return string
   */
  public function fullTextSql( $table, $indexName, $expr, $mode="and" );

  //-------------------------------------------------------------
  // Database usage and introspection
  //-------------------------------------------------------------

  /**
   * Returns true if PDOStatement::rowCount() returns the number
   * of rows found by the last SELECT command. True for MySQL,
   * false for most other systems.
   * @return bool
   */
  public function supportRowCountForQueries();

  /**
   * Returns table structure as sql create statement
   * @param string $table table name
   * @return string
   */
  public function sqlDefinition( $table );

  /**
   * Checks if table exists
   * @param $table string
   * @return boolean
   */
  public function tableExists( $table );


  /**
   * Checks if a function or stored procedure of this name exists in the database
   * @param $routine
   * @return boolean
   */
  public function routineExists( $routine );

  /**
   * Creates a table with an numeric, unique, self-incrementing id column,
   * which is also the primary key, with utf-8 as default character set. Throws
   * an error if table already exists.
   * @param string $table
   * @param string Optional id column name, defaults to 'id'
   * @throws PDOException
   */
  public function createTable( $table, $idCol="id" );

  /**
   * Deletes a table from the database
   * @param string|array $table Drop one or several tables
   */
  public function dropTable( $table );

  /**
   * Format a table name for use in the sql query.
   * @param string $table Table name
   * @return string
   */
  public function formatTableName( $table );

  /**
   * Format a column name for use in the sql query.
   * @param $column
   * @internal param string $table Column name
   * @return string
   */
  public function formatColumnName( $column );

  /**
   * Checks if a column exists in the table
   * @param string $table
   * @param string $column
   * @return boolean
   */
  public function columnExists( $table, $column );

  /**
   * Returns the definition of a column as specified in a column definition in a
   * CREATE TABLE statement.
   * @param string $table
   * @param string $column
   * @return mixed string defintion or null if column does not exist
   */
  public function getColumnDefinition( $table, $column );

  /**
   * Adds a column, throws if column exists.
   * @param string $table
   * @param string $column
   * @param string $definition
   * @param string $after Optional placement instruction. Must be one of (FIRST|AFTER xxx|LAST)
   */
  public function addColumn( $table, $column, $definition, $after="");

  /**
   * Modify a column.
   * @param string $table
   * @param string $column
   * @param string $definition
   * @param string $after Optional placement instruction. Must be one of (FIRST|AFTER xxx|LAST)
   */
  public function modifyColumn( $table, $column, $definition, $after="" );

  /**
   * Renames a column.
   * @param string $table
   * @param string $oldColumn old column name
   * @param string $newColumn new column name
   * @param string $definition (required)
   * @param string $after Optional placement instruction. Must be one of (FIRST|AFTER xxx|LAST)
   * return void
   */
  public function renameColumn( $table, $oldColumn, $newColumn, $definition, $after="" );

  /**
   * Returns name(s) of primary key(s) from table
   * @param string $table table name
   * @return array array of columns
   */
  public function getPrimaryKey( $table );

  /**
   * Adds a primary key for the table
   * @param string $table table name
   * @param string|array $columns column(s) for the primary key
   */
  public function addPrimaryKey( $table, $columns );

  /**
   * Removes a primary key index from a table
   * @param string $table
   */
  public function dropPrimaryKey( $table );

  /**
   * Modify the primary key index from a table
   * @param string $table
   * @param string[] $columns Columns for the primary key
   */
  public function modifyPrimaryKey( $table, $columns );

  /**
   * Removes an index
   * @param string $table table name
   * @param string $index index name
   * @return void
   */
  public function dropIndex( $table, $index );

  /**
   * Return the columns in index
   * @param string $table
   * @param string $index
   * @return array Array of column names that belong to the index
   */
  public function getIndexColumns( $table, $index );

  /**
   * Returns an array of index names defined in the table
   * @param $table
   * @return array
   */
  public function indexes( $table );

  /**
   * Checks whether an index exists
   * @param $table
   * @param $index
   * @return boolean
   */
  public function indexExists( $table, $index );

  /**
   * Adds a an index.
   * @param string $table
   * @param string $type Any of (FULLTEXT|UNIQUE)
   * @param string $index index name
   * @param string|array  $columns name(s) of column(s) in the index
   */
  public function addIndex( $table, $type, $index, $columns );

  /**
   * Creates a "LIMIT" statement to return only a subset of the
   * result. Takes either one or two arguments. If one, this is
   * the number of rows to retrieved. If two, the first is the
   * first row to retrieve, the second is the number of rows to
   * retrieve.
   *
   * @param int $first
   *    If a second argument is provided, this is the first record
   *    to retrieve. If no second argument, this is the number
   *    of rows to retrieve
   * @param int|null $second
   *    If provided, this is the number of rows to retrieve
   * @return array
   */
  public function createLimitStatement( $first, $second=null );

  /**
   * Returns the current time from the database
   * @return string
   */
  public function getTime();

  /**
   * Calculates the number of seconds passed between the
   * timestamp value parameter. The difference is calculated
   * by the db engine, not by php.
   * @param string $timestamp Timestamp value
   * @return string
   */
  public function getSecondsSince( $timestamp );

}
?>