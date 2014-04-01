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
qcl_import( "qcl_data_db_adapter_Abstract" );
qcl_import( "qcl_data_db_adapter_IAdapter" );
qcl_import( "qcl_data_db_adapter_IRemoteHostAdapter" );


/**
 * Adapter for PDO-compatible database drivers, specializing on the
 * MySQL backend. For other PDO backend, subclass this class to adapt
 * driver-specific features.
 *
 * See http://www.php.net/manual/de/book.pdo.php
 */
class qcl_data_db_adapter_PdoMysql
  extends    qcl_data_db_adapter_Abstract
  implements qcl_data_db_adapter_IAdapter,
             qcl_data_db_adapter_IRemoteHostAdapter
{

  //-------------------------------------------------------------
  // class properties
  //-------------------------------------------------------------

  /**
   * @var string $database name of database, read from dsn
   */
  protected $database;

  /**
   * The database host
   * @var string
   */
  protected $host;

  /**
   * The port on which the database daemon listens
   * @var int
   */
  protected $port;

  /**
   * The last PDOStatement object created by a query or prepare object
   * @var PDOStatement
   */
  protected $pdoStatement;

  /**
   * The resultset from the last query
   * @var array
   */
  protected $resultSet;

  //-------------------------------------------------------------
  // sql definitions
  //-------------------------------------------------------------

  protected $columnTypeDefinitions = array(
    'primary-key'     => "int(11)",
    'serialized-data' => "blob"
  );

  //-------------------------------------------------------------
  // accessors
  //-------------------------------------------------------------

  /**
   * Getter for database handler object
   * @return PDO
   */
  public function db()
  {
    return $this->db;
  }

  /**
   * Getter for database host
   * return string
   */
  public function getHost()
  {
    return $this->host;
  }

  /**
   * Setter for database host
   * @param string $host
   * return void
   */
  public function setHost( $host )
  {
    $this->host = $host;
  }

  /**
   * Getter for database port
   * return string
   */
  public function getPort()
  {
    return $this->port;
  }

  /**
   * Setter for database port
   * @param string $port
   * return string
   */
  public function setPort( $port )
  {
    $this->port = port;
  }

  /**
   * Setter for database name
   * @return string
   */
  public function getDatabase()
  {
    return $this->database;
  }

  /**
   * Setter for database name
   * @param $database
   * @return void
   */
  public function setDatabase( $database )
  {
    $this->database = $database;
  }

  //-------------------------------------------------------------
  // Iterator API
  //-------------------------------------------------------------

  /**
   * Iterator interface method
   */
  public function current()
  {
    return $this->pdoStatement->current();
  }

  /**
   * Iterator interface method
   */
  public function next()
  {
    return $this->pdoStatement->next();
  }

  /**
   * Iterator interface method
   */
  public function key()
  {
    return $this->pdoStatement->key();
  }

  /**
   * Iterator interface method
   */
  public function valid()
  {
    return $this->pdoStatement->valid();
  }

  /**
   * Iterator interface method
   */
  public function rewind ()
  {
    return $this->pdoStatement->rewind();
  }

  //-------------------------------------------------------------
  // PDO-specific and helper methods
  //-------------------------------------------------------------

  /**
   * Return the backend-specific column defintion for the given
   * type.
   * @param $type
   * @throws InvalidArgumentException
   * @return string
   */
  public function getColumnTypeDefinition( $type )
  {
    if( isset( $this->columnTypeDefinitions[$type] ) )
    {
      return $this->columnTypeDefinitions[$type];
    }
    throw new InvalidArgumentException("No column definition for '$type'");
  }

  /**
   * Given a scalar value, returns the PDO parameter type value
   * @param $value
   * @throws LogicException
   * @throws InvalidArgumentException
   * @return int
   */
  public function getParameterType( $value )
  {
    switch( gettype( $value ) )
    {
      case "boolean":
        return PDO::PARAM_BOOL;
      case "integer":
        return PDO::PARAM_INT;
      case "double":
      case "string":
        return PDO::PARAM_STR;
      case "NULL":
        return PDO::PARAM_NULL;
      case "object":
        if( method_exists( $value, "__toString" ) )
        {
          return PDO::PARAM_STR;
        }
        else
        {
          throw new LogicException(
            "Object of class " . get_class( $value ) . " cannot be converted into a string"
         );
        }
      default:
        throw new InvalidArgumentException("Unsupported type: " . typeof( $value ) );
    }
  }

  /**
   * Extracts the values contained in the dsn into an associated array of
   * key-value pairs that can be set as  properties of this object.
   * @param $dsn
   * @return array
   */
  public function extractDsnProperties( $dsn )
  {
    $dsn = new String( $dsn );
    $dsnprops = array();

    /*
     * the type is the string before the first ":"
     */
    $type = $dsn->substr( 0, $dsn->indexOf(":") );

    /*
     * analyse the rest of the string by splitting it along first
     * the ";" and then the "="
     */
    $rest = explode(";", $dsn->substr( $dsn->indexOf(":") ) );
    foreach( $rest as $part )
    {
      $value = explode("=",$part);
      $dsnprops[$value[0]] = $value[1];
    }

    return array(
      'type'     => $type,
      'host'     => either($dsnprops['host'],"localhost"),
      'port'     => either($dsnprops['port'], 3306),
      'database' => $dsnprops['dbname']
    );
  }

  /**
   * Returns the default options for initiating a PDO connection
   * @return array
   */
  public function getDefaultOptions()
  {
    $options =  array(
      PDO::ATTR_PERSISTENT => true,
      PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION
    );
    $encoding = $this->getApplication()->getIniValue("database.encoding");
    if ( $encoding )
    {
      $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '$encoding'";
    }
    return $options;
  }

  /**
   * Prepares data for PDO insert and update operations.
   * @param array $data associative array containing key-value pairs for
   * column names and values.
   * @return array Map of "columns" (Array),parameter "names" (Array),
   * parameter "types" (Map), and (parameter) "values" (Map).
   * @throws InvalidArgumentException
   */
  protected function prepareParameters( $data )
  {
    /*
     * prepare columns and values
     */
    $columns  = array();
    $names    = array();
    $types    = array();
    $values   = array();

    foreach ( $data as $key => $value )
    {
      if ( ! $key )
      {
        throw new InvalidArgumentException("Invalid data.");
      }

      $param_name = ":" . $key;
      $columns[]  = $this->formatColumnName( $key );
      $names[]    = $param_name;
      $values[ $param_name ] = $value;
      $types[ $param_name ]  = $this->getParameterType( $value );
    }

    /*
     * check result
     */
    if ( count($columns) == 0 )
    {
      throw new InvalidArgumentException("Invalid data.");
    }

    /*
     * return map
     */
    return array(
      "columns" => $columns,
      "names"   => $names,
      "types"   => $types,
      "values"  => $values
    );
  }

  /**
   * Tell driver to use the given database. Since the database cannot be
   * changed, the is s a protected function. Do not pass user-generated
   * data to this method.
   * @param $database
   * @return void
   */
  protected function useDatabase( $database )
  {
    $this->execute("USE `$database`");
  }

  /**
   * Checks an sql WHERE statement. Raises an error if problems
   * are found.
   *
   * @todo sanity checks.
   * @param string $where
   * @throws InvalidArgumentException
   * @return void
   */
  protected function checkWhereStatement( $where )
  {
    if ( ! is_string( $where ) or ! trim( $where ) )
    {
      throw new InvalidArgumentException("Invalid 'where' statement '$where'");
    }
  }

  //-------------------------------------------------------------
  // IAdapter Interface
  //-------------------------------------------------------------

  /**
   * Connects to database.
   * @throws PDOException
   * @return void
   */
  public function connect()
  {

    $dsn     = $this->getDsn();
    $user    = $this->getUser();
    $pass    = $this->getPassword();
    $options = $this->getOptions();

    if ( $this->getLogger()->isFilterEnabled( QCL_LOG_DB ) )
    {
        $this->log("Connecting to '$dsn' with user '$user', options: " . json_encode( $options ), QCL_LOG_DB );
    }


    /*
     * connect. this will throw a PDOException if unsuccesful
     */
    $this->db = new PDO( $dsn, $user, $pass, $options );

    /*
     * tell driver to use the current database
     */
    $this->useDatabase( $this->getDatabase() );

  }

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
  public function query( $sql, $parameters=null, $parameter_types=null, $driver_options=array() )
	{
		/*
		 * log
		 */
	  if ( $this->getLogger()->isFilterEnabled( QCL_LOG_DB ) ) // todo: cache result for better perfomance
	  {
  	  $this->log("query: $sql", QCL_LOG_DB );
      if ( $parameters )
      {
        $this->log("Parameters:" . print_r($parameters,true) . "\nParameter types:" . print_r($parameter_types,true) , QCL_LOG_DB );
      }
	  }

		/*
		 * bind values
		 */
		if( is_array( $parameters ) )
		{
		  $this->pdoStatement = $this->db()->prepare( $sql, $driver_options );
		  foreach( $parameters as $key => $value )
		  {
		    if ( ! is_numeric( $key ) )
		    {
		      if ( is_array( $parameter_types ) and isset( $parameter_types[$key] ) )
		      {
		        $this->pdoStatement->bindValue( $key, $value, $parameter_types[$key] );
		      }
		      else
		      {
		        $this->pdoStatement->bindValue( $key, $value, $this->getParameterType( $value ) );
		      }
		    }
		  }
      $this->pdoStatement->execute();
		}
		else
		{
	    $this->pdoStatement = $this->db()->query( $sql );
		}

		$this->pdoStatement->setFetchMode( PDO::FETCH_ASSOC );

		return $this->pdoStatement;
	}

  /**
   * Executes an SQL statement in a single method call, returning the number of rows
   * affected by the statement. No parameter replacement or checking is done, so
   * make sure not to pass any user-generated data as part of the sql statement.
   *
   * @param string $sql
   * @return int
   */
  public function exec( $sql )
  {
    if ( $this->getLogger()->isFilterEnabled( QCL_LOG_DB ) )
    {
      $this->log("exec: $sql", QCL_LOG_DB );
    }
    return $this->db()->exec( $sql );
  }

  /**
   * Executes an SQL statement. Alias of query().
   * @param string $sql
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * @return PDOStatement
   */
  public function execute( $sql, $parameters=null, $parameter_types=null )
  {
    return $this->query( $sql, $parameters, $parameter_types );
  }

  /**
   * Returns the number of rows affected by the last SQL statement (INSERT, UPDATE,
   * DELETE). For MySql, this also returns the number of records found in the
   * last SELECT query. For other drivers, a similar behavior might have to be
   * simulated otherwise.
   * @return int
   */
  public function rowCount()
  {
    return $this->pdoStatement->rowCount();
  }


  /**
   * Fetches the first or next row from a result set
   * @param string|null $sql Optional sql query. This allows
   * to query and fetch the result in one go.
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * == getAllRecords
   * @return array
   */
  public function fetch( $sql=null, $parameters=null, $parameter_types=null )
  {
    if ( $sql )
    {
      $this->query( $sql, $parameters, $parameter_types );
    }
    return $this->pdoStatement->fetch();
  }

  /**
   * Returns an array containing all of the result set rows
   * @param string|null $sql Optional sql query. This allows
   * to query and fetch the results in one go.
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * == getAllRecords
   * == query
   * @return array
   */
  public function fetchAll( $sql=null, $parameters=null, $parameter_types=null )
  {
    if ( $sql )
    {
      $this->query( $sql, $parameters, $parameter_types );
    }
    return $this->pdoStatement->fetchAll();
  }

  /**
   * Returns a single column from the first or next row of a result set.
   * @param $column_number
   * @param string|null $sql Optional sql to query
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * @return mixed
   */
	function fetchColumn ( $column_number, $sql = null, $parameters=null, $parameter_types=null )
	{
	  if ( $sql )
    {
      $this->query( $sql, $parameters, $parameter_types );
    }
		return $this->pdoStatement->fetchColumn( $column_number );
	}

  /**
   * Returns the value of the first column of the first/next row of the result set.
   * Useful for example for "SELECT count(*) ... " queries
   * ==getValue
   * @param string|null $sql Optional sql to query
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
   * return mixed
   * @return mixed
   */
  public function getResultValue( $sql=null, $parameters=null, $parameter_types=null )
  {
    return $this->fetchColumn( 0, $sql, $parameters, $parameter_types );
  }

	/**
	 * Returns the values of the first column of each row of the result set.
	 * Useful if only one column is queried.
	 * ==values
	 * @param string|null $sql Optional sql to query
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
	 * @return array
	 */
	function getResultValues( $sql, $parameters=null, $parameter_types=null  )
	{
		$result= array();
		if ( $sql )
		{
		  $this->query( $sql, $parameters, $parameter_types );
		}
		while ( false !== ( $value = $this->getResultValue() ) )
		{
		  $result[] = $value;
		}
		return $result;
	}

	/**
	 * Checks whether a certain where statement returns any rows.
	 * @param string $table Table name
	 * @param string $where 'where' statement. Make sure this string contains no
	 * user-generated input except by using named parameters.
   * @param array|null $parameters Optional, @see query()
   * @param array|null $parameter_types Optional, @see query()
	 * @return bool
	 */
	function existsWhere( $table, $where, $parameters=null, $parameter_types=null )
	{
	   $this->checkWhereStatement($where);
	   $table = $this->formatTableName($table);
     $count = $this->getResultValue("SELECT 1 FROM $table WHERE $where LIMIT 1", $parameters, $parameter_types);
	   return $count > 0;
	}

	/**
	 * Inserts a record into a table and returns its id.
	 * @param string $table table name
	 * @param array $data associative array with the column names as keys and the column data as values
	 * @return int the id of the inserted row (only if auto_increment-key)
	 */
	function insertRow( $table, $data )
	{
		/*
		 * prepare sql statement
		 */
    $param = $this->prepareParameters( $data );
	  $columns = implode(",", $param['columns'] );
		$names   = implode(",", $param['names'] );
		$table   = $this->formatTableName( $table );
		$sql     = "INSERT INTO $table ($columns) VALUES ($names)";

		/*
		 * execute query
		 */
		$this->execute( $sql, $param['values'], $param['types'] );

		/*
		 * return last insert id
		 */
		return $this->lastInsertId();
	}

	/**
	 * Returns the ID of the last inserted row or sequence value
	 * @return int
	 */
	public function lastInsertId()
	{
	  return (int) $this->db()->lastInsertId();
	}

  /**
   * Updates a record in a table identified by id.
   * @param string $table table name
   * @param array $data associative array with the column names as keys and the column data as values
   * @param string $idColumn name of column containing the record id, defaults to "id"
   * @param int|null $id Optional id value, if id is not part of the data.
   * @return bool
   * @throws InvalidArgumentException
   * @return bool
   * @internal param \Success $bool
   */
	public function updateRow( $table, $data, $idColumn="id", $id=null )
	{
    if ( $id === null )
		{
	    if ( isset( $data[$idColumn] ) )
		  {
        $id = $data[$idColumn];
        unset($data[$idColumn]);
  		}
  		else
  		{
  		  throw new InvalidArgumentException("No id given.");
  		}
		}

		if ( ! is_integer( $id ) )
		{
      throw new InvalidArgumentException("Invalid id:" . $id );
		}

    /*
     * execute query
     */
    $idColumn = $this->formatColumnName( $idColumn );
    $this->updateWhere( $table, $data, "$idColumn = :id", array( ':id' =>  $id ) );
		return (bool) $this->rowCount();
	}

  /**
   * Updates records in a table identified by a where condition
   * @param string $table table name
   * @param array $data associative array with the column names as keys and the column data as values
   * @param string  $where where condition. Make sure not to include any user-generated content, use
   *   parameters instead.
   * @param array|null $parameters Optional parameters to the where condition, @see query()
   * @param array|null $parameter_types Optional parameter types, @see query()
   * @return int Number of affected rows
   */
  public function updateWhere ( $table, $data, $where, $parameters=array(), $parameter_types=array() )
  {
    /*
     * prepare sql statement
     */
    $this->checkWhereStatement($where);

    $param    = $this->prepareParameters( $data );
    $columns  = $param['columns'];
    $names    = $param['names'];
    $table    = $this->formatTableName( $table );
    $pairs    = array();
    for ( $i=0; $i< count($columns); $i++ )
    {
      $pairs[] = $columns[$i] . "=" . $names[$i];
    }
    $set = implode(",",$pairs);
    $sql = "UPDATE $table SET $set WHERE $where";

    /*
     * execute query
     */
    $values = array_merge( $parameters, $param['values'] );
    $types  = array_merge( $parameter_types, $param['types'] );
    $this->execute( $sql, $values, $types );

    return $this->rowCount();
  }

	/**
	 * Deletes one or several records in a table identified by id(s)
	 * @param string $table table name
	 * @param int|array $ids (array of) record id(s)
	 * @param string $idColumn name of column containing the record id
	 * @return bool success
	 */
  public function deleteRow( $table, $ids, $idColumn="id" )
	{
		$id_list  = implode(",", (array) $ids );
		$table    = $this->formatTableName( $table );
		$idColumn = $this->formatColumnName( $idColumn );
		$this->execute("DELETE FROM $table WHERE $idColumn IN ($id_list)");
		return (bool) $this->rowCount();
	}

	/**
	 * Deletes one or more records in a table matching a where condition
	 * @param string $table table name
   * @param string  $where where condition. Make sure not to include any user-generated content, use
   *   parameters instead.
   * @param array|null $parameters Optional parameters to the where condition, @see query()
   * @param array|null $parameter_types Optional parameter types, @see query()
   * @return int Number of affected rows
	 */
	function deleteWhere( $table, $where, $parameters=null, $parameter_types=null )
	{
		$this->checkWhereStatement($where);
	  $table = $this->formatTableName( $table );
	  $this->execute( "DELETE FROM $table WHERE $where", $parameters, $parameter_types);
	  return $this->rowCount();
	}

  /**
   * Deletes all records from a table and resets the id counter.
   * @param string $table table name
   * @return bool Success
   */
  function truncate( $table )
  {
    $table = $this->formatTableName( $table );
    $this->execute( "TRUNCATE $table");
    return true;
  }

  /**
   * Counts records in a table matching a where condition.
   * @param $table
   * @param string $where where condition. Make sure not to include any user-generated content, use
   *   parameters instead.
   * @param array|null $parameters Optional parameters to the where condition, @see query()
   * @param array|null $parameter_types Optional parameter types, @see query()
   * @return mixed
   * @return mixed
   */
  public function countWhere( $table, $where, $parameters=null, $parameter_types=null )
  {
    $this->checkWhereStatement($where);
    $table = $this->formatTableName( $table );
    $sql   = "SELECT COUNT(*) FROM $table WHERE $where";
    return $this->getResultValue($sql, $parameters, $parameter_types);
  }

  //-------------------------------------------------------------
  // special purpose sql statements
  //-------------------------------------------------------------

  /**
   * Returns the column definition string to create a timestamp column that
   * automatically updates when the row is changed.
   * @return string
   */
  public function currentTimestampSql()
  {
    return "timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP";
  }

  /**
   * Returns the sql to do a fulltext search.
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
    /*
     * match mode
     */
    $searchMode = "";
    switch ( $mode )
    {
      case "fuzzy":
        break;

      case "boolean":

        $searchMode = "IN BOOLEAN MODE";

         /*
          *  add a plus sign ( AND operator) to each search word
          */
        $searchWords = explode(" ",$expr);
        foreach($searchWords as $index => $word)
        {
          if ( $word[0] != "-" )
          {
            $searchWords[$index] = "+" . $word;
          }
        }
        /*
         * remove dupliate plus signs (in case the user has added them)
         */
        $expr = str_replace("++","+", implode(" ",$searchWords ) );
        break;
      default:
        throw new InvalidArgumentException("Match mode '$mode' is invalid");
    }

    /*
     * get index columns
     * @todo: use cache for this!
     */
    $fullTextCols = $this->getIndexColumns( $table, $indexName );
    if( ! count( $fullTextCols ) )
    {
      throw new InvalidArgumentException("Model has no fulltext index '$indexName'");
    }

    /*
     * construct sql query
     */
    $fullTextCols = implode( ",",$fullTextCols );
    $sql = "MATCH ($fullTextCols) AGAINST ('" . addslashes ( $expr ) . "' $searchMode)" ;

    return $sql;
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
    return $this->getResultValue("SHOW CREATE TABLE " . $this->formatTableName( $table ) );
  }


  /**
   * Checks if table exists.
   * @param $table string
   * @return boolean
   */
  public function tableExists( $table )
  {
    $database = $this->getDatabase();
    $column   = $this->formatColumnName("tables_in_" . $database );
    $result = $this->getResultValue(
      "show tables from $database where $column like :table",
      array( ":table" => $table )
    );
    return (bool) $result;
  }

  /**
   * Checks if a function or stored procedure of this name exists in the database
   * @param $routine
   * @return boolean
   */
  public function routineExists( $routine )
  {
    return (bool) $this->getResultValue("
      SELECT count(*) FROM INFORMATION_SCHEMA.ROUTINES
      WHERE ROUTINE_NAME = :routine
    ", array( ":routine" => $routine ) );
  }

  /**
   * Creates a table with an numeric, unique, self-incrementing id column,
   * which is also the primary key, with utf-8 as default character set. Throws
   * an error if table already exists.
   * WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string $table
   * @param string Optional id column name, defaults to 'id'
   */
  public function createTable( $table, $idCol="id" )
  {
    $table    = $this->formatTableName( $table );
    $idCol    = $this->formatColumnName( $idCol );
    $this->exec("
     CREATE TABLE $table (
       $idCol INT(11) NOT NULL AUTO_INCREMENT,
       PRIMARY KEY ($idCol)
      ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ");
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
    $this->exec("DROP TABLE $table" );
    $this->flush();
  }

  /**
   * Format a table name for use in the sql query. This will
   * add backticks for MySql tables and database names.
   * @param string $table Table name
   * @return string
   * FIXME sanitize string
   */
  public function formatTableName( $table )
  {
    $parts = explode(".", $table);
    return "`" . implode("`.`", $parts ) . "`";
  }

  /**
   * Format a column name for use in the sql query. This will
   * add backticks for MySql columns.
   * @param $column
   * @internal param string $table Column name
   * @return string
   * FIXME sanitize string
   */
  public function formatColumnName( $column )
  {
    return "`$column`";
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
    $cache = &$_SESSION['qcl_data_db_adapter_PdoMysql_ColumnCache'];
    if ( ! is_array( $cache ) )
    {
      $cache = array();
    }
    if ( $useCache === false or ! isset( $cache[$table][$column] ) )
    {
      $database = $this->getDatabase();
      $table = $this->formatTableName( $table );
      $cache[$table][$column] = (bool) count( $this->fetchAll("
        SHOW COLUMNS FROM $table FROM `$database` LIKE :column
      ", array(
        ":column"   => $column
      ) ) );
    }
    return $cache[$table][$column];
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
    $table = $this->formatTableName( $table );
    $c = $this->fetch(
      "SHOW COLUMNS FROM $table WHERE Field like :column",
      array( ':column' => $column )
    );

    /*
     * @todo Ternary stuff below needs a transparent rework!
     */
    if ( count($c) )
    {
      $definition = trim(str_replace("  "," ",implode(" ", array(
        $c['Type'],
        ( $c['Null']=="YES" ? "NULL":"NOT NULL" ),
        ( is_null($c['Default']) ? "" :
          "DEFAULT " . (
            in_array($c['Default'], array("CURRENT_TIMESTAMP") ) ?
              $c['Default'] : "'" . addslashes($c['Default']) . "'"
           ) ),
        $c['Extra']
      ))));
      return trim($definition);
    }
    return null;
  }

  /**
   * Adds a column, throws error if column exists.
   * WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string $table
   * @param string $column
   * @param string $definition
   * @param string $after Optional placement instruction. Must be one of (FIRST|AFTER xxx|LAST)
   * @throws Exception
   * @throws PDOException
   */
  public function addColumn( $table, $column, $definition, $after="")
  {
    if ( ! $this->columnExists( $table, $column ) )
    {
      $table  = $this->formatTableName( $table );
      $column = $this->formatColumnName( $column );
      try
      {
        $this->exec("
          ALTER TABLE $table ADD COLUMN $column $definition $after;
        ");
        $this->log("Added $table.$column with definition '$definition'.",QCL_LOG_TABLES);
      }
      catch( PDOException $e )
      {
        if ( stristr( $e->getMessage(), "duplicate column name" ) )
        {
          $this->warn("Column '$table.$column' already exists.'");
          return;
        }
        throw $e;
      }
    }
    else
    {
      $this->warn("Column $table.$column exists, not added.");
    }
  }

  /**
   * Modifies a column.
   * WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string $table
   * @param string $column
   * @param string $definition
   * @param string $after Optional placement instruction. Must be one of (FIRST|AFTER xxx|LAST)
   */
  public function modifyColumn( $table, $column, $definition, $after="" )
  {
    $oldDef = $this->getColumnDefinition($table,$column);
    $table  = $this->formatTableName( $table );
    $column = $this->formatColumnName( $column );
    $this->exec("
      ALTER TABLE $table MODIFY COLUMN $column $definition $after;
    ");
    $this->log("Modified $table.$column from '$oldDef' to '$definition'.",QCL_LOG_TABLES);
  }

  /**
   * Renames a column.
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
    $table     = $this->formatTableName( $table );
    $oldColumn = $this->formatColumnName( $oldColumn );
    $newColumn = $this->formatColumnName( $newColumn );
    $this->exec("
      ALTER TABLE $table CHANGE COLUMN $oldColumn $newColumn $definition $after
    ");
    $this->log("Renamed $table.$oldColumn to $table.$newColumn.",QCL_LOG_TABLES);
  }

  /**
   * Deletes a column from a table.
   * WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string $table
   * @param string $column
   * return bool
   * @return int
   */
  public function dropColumn( $table, $column )
  {
    $table  = $this->formatTableName( $table );
    $column = $this->formatColumnName( $column );
    return $this->exec("ALTER TABLE $table DROP COLUMN $column;");
  }

  /**
   * Returns the primary key(s) from a table.
   * @param string $table table name
   * @return array array of columns
   */
  public function getPrimaryKey( $table )
  {
    return $this->getResultValues("
      SELECT COLUMN_NAME
      FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
      WHERE
        TABLE_SCHEMA    = :database AND
        TABLE_NAME      = :table AND
        CONSTRAINT_NAME = 'PRIMARY'
    ", array(
      ":database" => $this->getDatabase(),
      ":table"    => $table
    ));
  }

  /**
   * Adds a primary key for the table
   * @param string $table table name
   * @param string|array $columns column(s) for the primary key
   */
  public function addPrimaryKey( $table, $columns )
  {
    $columns = (array) $columns;
    $table   = $this->formatTableName( $table );
    $this->execute("
      ALTER TABLE $table
      ADD PRIMARY KEY  (`" . implode("`,`", $columns) . "`);
    ");
    $this->log("Added primary key to table $table.",QCL_LOG_TABLES );
  }

  /**
   * Removes the primary key index from a table
   * @param string $table
   */
  public function dropPrimaryKey( $table )
  {
    $table   = $this->formatTableName( $table );
    $this->execute("
      ALTER TABLE $table DROP PRIMARY KEY;
    ");
    $this->log("Removed primary key from table $table.",QCL_LOG_TABLES );
  }

  /**
   * Modify the primary key index from a table
   *
   * @param string $table
   * @param string[] $columns Columns for the primary key
   */
  public function modifyPrimaryKey( $table, $columns )
  {
    $table   = $this->formatTableName( $table );
    $columns = (array) $columns;
    $this->execute("
      ALTER TABLE $table DROP PRIMARY KEY, ADD PRIMARY KEY  (`" . implode("`,`", $columns) . "`);
    ");

     $this->log("Primary key from table $table modified.",QCL_LOG_TABLES );
  }

  /**
   * Removes an index
   * @param string $table table name
   * @param string $index index name
   * @return void
   */
  public function dropIndex( $table, $index )
  {
    if ( count( $this->getIndexColumns($table, $index ) ) )
    {
      $table = $this->formatTableName( $table );
      $this->execute("
        ALTER TABLE $table DROP INDEX `$index`
      ");
       $this->log("Removed index '$index' from table $table.",QCL_LOG_TABLES );
    }
  }

  /**
   * Return the columns in index
   * @param string $table
   * @param string $index
   * @return array Array of column names that belong to the index
   */
  public function getIndexColumns( $table, $index )
  {
    $table = $this->formatTableName( $table );
    $records = $this->fetchAll("
      SHOW INDEX FROM $table
      WHERE Key_name = :index
    ", array(
      ":index" => $index
    ));
    $result = array();
    foreach( $records as $record )
    {
      $result[] = $record['Column_name'];
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
    $records = $this->getAllRecords("
      SHOW INDEX FROM $table
    ");
    $result = array();
    foreach( $records as $record )
    {
      $result[] = $record['Key_name'];
    }
    return $result;
  }

  /**
   * Checks whether an index exists
   * @param $table
   * @param $index
   * @return boolean
   */
  public function indexExists( $table, $index )
  {
    return count( $this->getIndexColumns( $table, $index ) ) > 0;
  }

  /**
   * Adds a an index.
   * FIXME: WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string $table Table name
   * @param string $type Any of (FULLTEXT|UNIQUE)
   * @param string $index index name
   * @param string|array $columns name(s) of column(s) in the index
   * @throws Exception
   * @throws PDOException
   * @return void
   */
  public function addIndex( $table, $type, $index, $columns )
  {
    if ( ! count( $this->getIndexColumns($table, $index ) ) )
    {
      $columns = (array) $columns;
      $table = $this->formatTableName( $table );
      try
      {
        $this->execute ("
          ALTER TABLE $table ADD $type `$index` (`" . implode("`,`", $columns) . "`);
        ");
      }
      catch (PDOException $e)
      {
        if ( stristr( $e->getMessage(), "duplicate key name" ) )
        {
          $this->warn("Index '$table.$index' already exists.'");
          return;
        }
        throw $e;
      }
      $this->log("Added $type index '$index' to table $table indexing columns " . implode(",",$columns) . ".", QCL_LOG_TABLES);
    }
    else
    {
      $this->warn("Index $index already exists in table $table.");
    }
  }

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
  public function createLimitStatement( $first, $second=null )
  {
    /*
     * if no second argument
     */
    if ( $second === null )
    {
      return "LIMIT $first";
    }
    else
    {
      return "LIMIT $first, $second";
    }
  }

  /**
   * Creates a trigger that inserts a timestamp on
   * each newly created record.
   * WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   * @param string $table Name of table
   * @param string $column Name of column that gets the timestamp
   */
  public function createTimestampTrigger( $table, $column )
  {
    $this->execute("
      DROP TRIGGER IF EXISTS `{$table}_create_timestamp`
    ");

    $this->execute("
      CREATE TRIGGER `{$table}_create_timestamp`
      BEFORE INSERT ON `$table`
      FOR EACH ROW SET NEW.`$column` = NOW();
    ");
  }

  /**
   * Creates triggers that will automatically create
   * a md5 hash string over a set of columns
   * FIXME: WARNING: Input values are assumed to come from internal processing only and are therefore
   * not sanitized. Make sure not to pass user-generated data to this method!
   */
  public function createHashTriggers ( $table, $columns )
  {
    /*
     * @todo check permisions
     */
    $this->execute("
      DROP TRIGGER IF EXISTS `{$table}_insert_create_hash`
    ");
    $this->execute("
      DROP TRIGGER IF EXISTS `{$table}_update_create_hash`
    ");

    $col=array();
    for($i=0;$i<count($columns);$i++ )
    {
      $col[] = "NEW.`" . $columns[$i] . "`";
    }

    $col_sql = implode(",",$col);

    $this->execute("
      CREATE TRIGGER `{$table}_insert_create_hash`
      BEFORE INSERT ON `$table`
      FOR EACH ROW SET NEW.hash = md5(concat_ws(',',$col_sql));
    ");

    $this->execute("
      CREATE TRIGGER `{$table}_update_create_hash`
      BEFORE UPDATE ON `$table`
      FOR EACH ROW SET NEW.hash = md5(concat_ws(',',$col_sql));
    ");

    $this->log("Created trigger to update hash over " . implode(",",$columns) . ".",QCL_LOG_TABLES);
  }

  /**
   * Returns the current time from the database
   * @return string
   */
  public function getTime()
  {
    return $this->getResultValue("SELECT NOW()");
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
      SELECT TIME_TO_SEC(TIMEDIFF(NOW(),:timestamp));
    ", array( ":timestamp" => $timestamp ) );
  }

  /**
   * Updates table information schema of the backend. This is necessary
   * for MySql after deleting tables. Requires administrative privileges.
   * @return void.
   */
  public function flush()
  {
    static $warning = true;
    try
    {
      $this->exec("FLUSH TABLES");
    }
    catch( PDOException $e)
    {
      if( $warning )
      {
        $this->warn("Cannot flush tables:" . $e->getMessage() );
        $warning = false; // warn only once
      }
    }
  }

}

?>