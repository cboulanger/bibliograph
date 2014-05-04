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
 *  * Oliver Friedrich (jesus77)
 */

//qcl_import( "qcl_data_model_IQueryBehavior" );
qcl_import( "qcl_data_db_Table" );


/**
 * Query behavior for (PDO) database driver.
 * FIXME ORDER BY clause must be sanitized, remains unchecked!
 */
class qcl_data_model_db_QueryBehavior
//implements qcl_data_model_IQueryBehavior
{

  //-------------------------------------------------------------
  // Class properties
  //-------------------------------------------------------------

  /**
   * The model affected by this behavior
   * @var qcl_data_model_db_ActiveRecord
   */
  protected $model;

  /**
   * The default prefix used for data tables
   * @var string
   */
  protected $getDefaultTablePrefix = "data_";

  /**
   * The database driver adapter. Acces with getAdapter()
   */
  private $adapter;

  /**
   * The table object used for manipulating the database tables.
   * Access with getTable()
   * @var string
   */
  private $table;

  /**
   * The name of the table used for storing the data of the managed
   * object.
   * @var string
   */
  private $tableName;

  /**
   * A static persistent cache object to avoid repetetive introspection
   * queries
   * @var qcl_data_model_db_QueryCache
   */
  static private $cache;


  /**
   * The indexes of the model table
   * @var array
   */
  private $indexes = array();

  /**
   * @var string[] Properties for the primary index
   */
  private $primaryIndexProperties = array();

  /**
   * Whether the object is initialized
   * @var bool
   */
  private $isInitialized = false;

  //-------------------------------------------------------------
  // Constructor
  //-------------------------------------------------------------

  /**
   * Constructor
   * @param qcl_data_model_IActiveRecord $model Model affected by this behavior
   */
  function __construct( qcl_data_model_IActiveRecord $model )
  {
    /*
     * the model affected by this behavior
     */
    $this->model = $model;
  }

  /**
   * Initialization
   * @return bool true if initialization is necessary, false if already initialized
   */
  function init()
  {
    if ( ! $this->isInitialized )
    {
      $this->setupIndexes();
      $this->isInitialized  = true;
      return true;
    }
    return false;
  }

  //-------------------------------------------------------------
  // Getters and setters
  //-------------------------------------------------------------

  /**
   * Getter for model affected by this behavior
   * @return qcl_data_model_db_ActiveRecord
   */
  public function getModel()
  {
    return $this->model;
  }

  /**
   * Retrieves the datasource object
   * @return qcl_data_datasource_DbModel
   */
  public function getDatasourceModel()
  {
    return $this->getModel()->datasourceModel();
  }

  /**
   * Stores the name or object reference of the datasource that
   * provides information on access to the data
   * @param qcl_data_datasource_DbModel $datasourceModel
   * @internal param mixed $datasource Either the name of the datasource or an
   * object reference to the datasource object
   * return void
   */
  protected function setDatasourceModel( qcl_data_datasource_DbModel $datasourceModel )
  {
    $this->datasourceModel = $datasourceModel;
  }

  /**
   * Getter for table name. If the table name has not been set
   * by the model, use "data_" plus class name of the model
   * @return string
   */
  public function getTableName()
  {
    if ( ! $this->tableName )
    {
      $this->tableName = $this->getModel()->tableName();
      if ( ! $this->tableName )
      {
        $this->tableName =
          $this->getDefaultTablePrefix() . $this->getModel()->className();
      }
    }
    return $this->getTablePrefix() . $this->tableName;
  }

  /**
   * Getter for the default prefix for data tables
   * @return string
   */
  public function getDefaultTablePrefix()
  {
    return $this->getDefaultTablePrefix;
  }

  /**
   * Setter for the default prefix for data tables
   * @param $prefix
   * @return string
   */
  public function setDefaultTablePrefix( $prefix )
  {
    qcl_assert_valid_string( $prefix );
    $this->getDefaultTablePrefix = $prefix;
  }


  /**
   * Getter for persistent cache object
   * @return qcl_data_model_db_QueryCache
   */
  public function cache()
  {
    if ( ! self::$cache )
    {
      qcl_import( "qcl_data_model_db_QueryCache" );
      self::$cache = new qcl_data_model_db_QueryCache();
    }
    return self::$cache;
  }


  //-------------------------------------------------------------
  // Database management
  //-------------------------------------------------------------

  /**
   * Getter for database manager singleton object
   * @return qcl_data_db_Manager
   */
  public function getManager()
  {
    qcl_import( "qcl_data_db_Manager" );
    return qcl_data_db_Manager::getInstance();
  }

  /**
   * Returns the database connection adapter for this model, which is
   * taken from the datasource object or from the framework.
   * @return qcl_data_db_adapter_PdoMysql
   * @throws LogicException
   */
  public function getAdapter()
  {
    if ( $this->adapter === null )
    {
      /*
       * try to get db handler from datasource object
       */
      $dsModel = $this->getDatasourceModel();
      if ( $dsModel )
      {
        $this->adapter = $dsModel->createAdapter();
      }

      /*
       * otherwise, get database object from framework
       */
      else
      {
        $this->adapter = $this->getManager()->createAdapter();
      }

      /*
       * if no database object at this point, fatal error.
       */
      if ( ! $this->adapter )
      {
        throw new LogicException("No database adapter available.");
      }
    }
    return $this->adapter;
  }

  /**
   * Returns application instance
   * @return qcl_application_Application
   * @throws LogicException if no application exists.
   */
  protected function getApplication()
  {
    return $this->getModel()->getApplication();
  }

  //-------------------------------------------------------------
  // Table management
  //-------------------------------------------------------------

  /**
   * Returns the prefix for tables used by this
   * model. defaults to the datasource name plus underscore
   * or an empty string if there is no datasource
   * @return string
   */
  public function getTablePrefix()
  {
    $dsModel = $this->getDatasourceModel();
    if ( $dsModel )
    {
      $prefix = $dsModel->getTablePrefix();
    }
    else
    {
      $prefix = $this->getApplication()->getIniValue("database.tableprefix");
    }
    return $prefix;
  }

  /**
   * Returns the table object used by this behavior
   * @return qcl_data_db_Table
   */
  public function getTable()
  {
    if ( $this->table === null)
    {
      $tableName   = $this->getTableName();
      $adapter     = $this->getAdapter();
      $this->table = new qcl_data_db_Table( $tableName, $adapter );
    }
    return $this->table;
  }

  /**
   * Returns the name of the column that holds the unique (numeric) id of the table.
   * @return string
   */
  public function getIdColumn()
  {
    return "id";
  }

  /**
   * Returns the column name from a property name set by the property
   * behavior.
   *
   * @param string $property Property name
   * @return string
   */
  public function getColumnName( $property )
  {
    return $this->getModel()->getPropertyBehavior()->getColumnName( $property );
  }

  /**
   * Add an index to the model table. This will also update
   * existing indexes.
   * @param array $indexes Map of index data. The keys are the
   *  name of the index, the value is an associative array with
   *  the following keys:
   *  "type"       => a string value, any of (unique|fulltext),
   *  "properties" => an array of property names
   * @return void
   */
  public function addIndexes( $indexes )
  {
    foreach( $indexes as $name => $index)
    {
      $this->indexes[$name] = $index;
    }
  }

  /**
   * Add properties to the primary index of the model
   *
   * Be aware, that you extend the primary key so it needs more space ind the database files
   *
   * @see qcl_data_model_IQueryBehavior::addPrimaryIndexProperties()
   * @param string[] $properties Array of the property names of the model that should be inserted into the primary key
   * @return qcl_data_model_AbstractActiveRecord Current model
   * @since 2010-05-21
   */
   public function addPrimaryIndexProperties(array $properties) {
       foreach($properties as $property) {
           $this->primaryIndexProperties[] = $property;
       }
   }

   /**
    * Gets the properties for the primary index
    *
    * @return string[]
    */
   public function getPrimaryIndexProperties() {
       return $this->primaryIndexProperties;
   }

  /**
   * Sets up the indexes. This must be called from the init()
   * method, after the properties have been set up
   *
   * @throws JsonRpcException
   * @return void.
   */
  public function setupIndexes()
  {
    $indexes   = $this->indexes;
    $model     = $this->getModel();
    $tableName = $this->getTableName();
    $table     = $this->getTable();
    $cache     = $this->cache();

    foreach( $indexes as $name => $index )
    {

      /*
       * check structure
       */
      try
      {
         qcl_assert_array_keys( $index, array( "type", "properties" ) );
      }
      catch ( InvalidArgumentException $e )
      {
        throw new JsonRpcException("Invalid index '$name': " . $e->getMessage() );
      }

      /*
       * initialize cache for this table
       */
      if ( ! isset( $cache->indexes[$tableName] ) )
      {
        $cache->indexes[$tableName] = array();
      }

      /*
       * continue if the cache has the same value
       */
      if ( isset( $cache->indexes[$tableName][$name] ) )
      {
        if ( $cache->indexes[$tableName][$name] == array(
            "type"       => $index['type'],
            "properties" => $index['properties']
        )  )
        {
          $model->log("Index for `$tableName` hasn't changed according to cached data.",QCL_LOG_TABLES);
          $cache->indexes[$tableName][$name] = $index;
          continue;
        }
      }

      /*
       * determine columns
       */
      $columns = array();
      if( ! is_array( $index['properties'] ) )
      {
        throw new JsonRpcException("Invalid index '$name': properties must be an array." );
      }

      foreach( $index['properties'] as $property )
      {
        $columns[] = $this->getColumnName( $property );
      }

      /*
       * if index doesn't exist, create it and continue
       */
      if ( ! $table->indexExists( $name ) )
      {
        $table->addIndex( $index['type'], $name, $columns );
        $cache->indexes[$tableName][$name] = $index;
        continue;
      }

      /*
       * check if the index has changed in the database
       */
      if ( $table->getIndexColumns( $name ) == $columns )
      {
        $model->log("Index for `$tableName` hasn't changed according to the database.",QCL_LOG_TABLES);
        $cache->indexes[$tableName][$name] = $index;
        continue;
      }

      /*
       * Yes, it has changed, drop it and recreate it
       */
      $model->log("Index for `$tableName` has changed, dropping and recreating it.",QCL_LOG_TABLES);
      $table->dropIndex( $name );
      $table->addIndex( $index['type'], $name, $columns );
      $cache->indexes[$tableName][$name] = $index;
    }
  }


  /**
   * Resets  the internal cache
   * @return void
   */
  public function reset()
  {
    $this->cache()->reset();
  }

  //-------------------------------------------------------------
  // Record search and retrieval methods (select/fetch methods)
  //-------------------------------------------------------------

  /**
   * Runs a query on the table managed by this behavior. Stores a
   * reference to the result PDO statement in the query, so that
   * it can be used by the fetch() command.
   *
   * @param qcl_data_db_Query $query
   *    The query object
   * @return int
   *    The number of rows selected
   */
  public function select( qcl_data_db_Query $query)
  {
    $sql = $query->toSql( $this->getModel() );
    $query->pdoStatement = $this->getAdapter()->query(
      $sql, $query->getParameters(), $query->getParameterTypes()
    );
    $query->rowCount = $this->getAdapter()->rowCount();
    return $query->rowCount;
  }

  /**
   * Selects all database records or those that match a where condition.
   * Takes an array as argument, from which new qcl_data_db_Query object
   * is created and returned.
   *
   * @param array $where
   *    Array containing the where data
   * @param mixed|null $orderBy
   * @return qcl_data_db_Query
   */
  public function selectWhere( $where, $orderBy=null )
  {
    if( ! is_array( $where ) )
    {
      new InvalidArgumentException("Invalid query data. Must be array.");
    }

    /*
     * Create query object
     */
    $query = new qcl_data_db_Query( array(
      'where'   => $where,
      'orderBy' => $orderBy
    ) );

    /*
     * Do query and return object
     */
    $this->select( $query );
    return $query;
  }

  /**
   * Select an array of ids for fetching.
   * @param array $ids
   * @param string|array|null $orderBy
   * @return qcl_data_db_Query
   * @throws InvalidArgumentException
   */
  public function selectIds( $ids, $orderBy=null )
  {
    if ( ! is_array( $ids) or ! count( $ids ) )
    {
      throw new InvalidArgumentException( __METHOD__ . " expects array with one or more elements.");
    }

    /*
     * sanity-check the ids
     */
    foreach( $ids as $id )
    {
      if( ! is_numeric($id) )
      {
        throw new InvalidArgumentException("Invalid id '$id'");
      }
    }
    /*
     * select
     */
    $query = new qcl_data_db_Query( array(
      'select'    => "*",
      'where'     => "id IN (" . implode(",", $ids ) .")",
      'orderBy'   => $orderBy
    ) );
    $this->select( $query );
    return $query;
  }

  /**
   * Returns a records by property value
   * @param string $propName Name of property
   * @param string|array $values Value or array of values to find. If an array, retrieve all records
   * that match any of the values.
   * @param qcl_data_db_Query|null $query
   * @return qcl_data_db_Query
   * @throws InvalidArgumentException
   */
  public function selectBy( $propName, $values, $query=null )
  {
    if( $query === null )
    {
      $query = new qcl_data_db_Query();
    }
    elseif ( ! $query instanceof qcl_data_db_Query )
    {
      throw new InvalidArgumentException("Invalid query data.");
    }

    $column     = $this->getColumnName( $propName );
    $colStr     = $this->getAdapter()->formatColumnName( $column );
    $names      = array();
    $parameters = array();

    foreach ( (array) $values as $i => $value )
    {
      $name    = ":value{$i}";
      $names[] = $name;
      $parameters[$name] = $value;
    }

    $query->where      = "$colStr IN (" . implode(",", $names ) . ")";
    $query->parameters = $parameters;

    $this->select( $query );
    return $query;
  }

  /**
   * If no argument, return the first or next row of the result of the previous
   * query. If a query object is passed, return the first or next row of the
   * result of this query.
   * The returned value is converted into the correct type
   * according to the property definition and the property behavior.
   * @see qcl_data_model_db_PropertyBehavior::typecast()
   * @param qcl_data_db_Query|null $query
   * @throws InvalidArgumentException
   * @return array
   */
  public function fetch( $query = null )
  {
    /*
     * use passed PDOStatement or simply the last one created
     */
    if ( $query instanceof qcl_data_db_Query
    and $query->getPdoStatement() instanceof PDOStatement )
    {
      $result = $query->getPdoStatement()->fetch();
    }
    elseif( $query === null )
    {
      $result = $this->getAdapter()->fetch();
    }
    else
    {
      throw new InvalidArgumentException("Argument must be instanceof qcl_data_db_Query or null");
    }

    /*
     * set the result
     */
    if ( ! is_array( $result ) )
    {
      return null;
    }
    else
    {
    	// FIXME test performance
    	foreach( $result as $key => $value )
    	{
    		$result[$key] = $this->getModel()->getPropertyBehavior()->typecast($key, $value);
    	}
      //if ( isset( $result["id"]) ) settype( $result["id"] , "integer" ); //FIXME
      return $result;
    }
  }


  /**
   * If no argument, return all rows of the result of the previous
   * query. If a query object is used as argument, run this query beforehand and
   * return the result. Don't use this for large amounts of data.
   * @param qcl_data_db_Query $query
   * @return array
   */
  public function fetchAll( $query = null )
  {
    if ( $query )
    {
      $this->select( $query );
    }
    $result = array();
    while ( $row = $this->fetch() )
    {
      $result[] = $row;
    }
    return $result;
  }


  /**
   * Returns all values of a model property that match a query
   * @param string $property Name of property
   * @param qcl_data_db_Query|array|string|null $query "Where" query information
   * as string or qcl_data_db_Query object. If null, select all property values
   * in the model data
   * @param bool $distinct If true, return only distinct values. Defaults to true
   * @throws InvalidArgumentException
   * @return array Array of values
   */
  public function fetchValues( $property, $query=null, $distinct=true )
  {

    /*
     * create query object from arguments
     */
    if ( is_array ( $query ) or is_null ( $query ) or is_string( $query ) )
    {
      $query = new qcl_data_db_Query( array(
        'properties' => $property,
        'where'      => $query,
        'distinct'   => $distinct,
        'orderBy'    => $property
      ) );
    }

    /*
     * if query argument is a query object, set its 'properties'
     * value unless it is already set
     */
    elseif ( $query instanceof qcl_data_db_Query  )
    {
      if ( ! $query->getProperties() )
      {
        $query->setProperties( (array) $property );
      }
      elseif ( $property === null )
      {
        $property = $query->properties[0];
      }
      else
      {
        throw new InvalidArgumentException("Invalid query data.");
      }
      if ( ! isset( $query->distinct ) )
      {
        $query->distinct = $distinct;
      }
      if ( ! isset( $query->orderBy ) )
      {
        $query->orderBy = $property;
      }
    }

    /*
     * invalid argument
     */
    else
    {
      throw new InvalidArgumentException("Invalid query data.");
    }

    /*
     * select and fetch data
     */
    $this->select( $query );
    $result = array();
    while( $row = $this->fetch() )
    {
      $result[] = $row[$property];
    }
    return $result;
  }

  /**
   * Returns the number of records found in the last query.
   * @return int
   */
  public function rowCount()
  {
    return $this->getAdapter()->rowCount();
  }

  /**
   * Counts records in a table matching a query
   * @param qcl_data_db_Query|array $query Query or where condition
   * @throws InvalidArgumentException
   * @return int
   */
  public function countWhere( $query )
  {
    if ( is_array( $query ) )
    {
      $query = new qcl_data_db_Query( array( 'where' => $query ) );
    }
    elseif ( ! $query instanceof qcl_data_db_Query )
    {
      throw new InvalidArgumentException("Argument must be an array or a qcl_data_db_Query object");
    }
    return $this->select( $query );
  }

  /**
   * Returns the number of records in the table
   * @return int
   */
  public function countRecords()
  {
    return $this->getTable()->length();
  }

  //-------------------------------------------------------------
  // Data creation and manipulation
  //-------------------------------------------------------------

  /**
   * Prepares the data for insertion and update
   * @param array $data
   * @return array
   */
  protected function prepareData( $data )
  {
    $preparedData = array();
    $propBeh = $this->getModel()->getPropertyBehavior();
    foreach( $data as $property => $value )
    {
      $column = $this->getColumnName( $property );
      $preparedData[ $column ] = $propBeh->scalarize( $property, $value );
    }

    return $preparedData;
  }

  /**
   * Inserts a data record.
   * @param array $data
   * @return int The id of the created row.
   */
  public function insertRow( $data )
  {
    return $this->getTable()->insertRow( $this->prepareData( $data) );
  }

  /**
   * Updates a record in a table identified by id
   * @param array $data associative array with the column names as keys and
   *  the column data as values.
   * @param int|string $id if the id key is not provided in the $data
   *  paramenter, provide it here (optional)
   * @param bool $keepTimestamp If true, do not overwrite the 'modified'
   *  timestamp
   * @throws InvalidArgumentException
   * @return boolean success
   * @throw InvalidArgumentException
   */
  public function update( $data, $id=null, $keepTimestamp= false )
  {

    /*
     * determine id
     */
    if ( $id === null and $data['id'] )
    {
      $id = $data['id'];
      unset( $data['id'] );
    }

    if ( ! $id  )
    {
      throw new InvalidArgumentException("Missing id.");
    }

    /*
     * set modified timestamp to null to set it to the current database update
     * time unless requested not to (i.e. in sync operations)
     */
    if ( ! $keepTimestamp and $this->getModel()->hasProperty("modified") )
    {
      $data['modified'] = null;
    }

    /*
     * do the update
     */
    $query = new qcl_data_db_Query( array(
      'where' => array ( 'id' => $id ) )
    );
    return $this->getTable()->updateWhere(
      $this->prepareData( $data ),
      $query->createWhereStatement( $this->getModel() ),
      $query->getParameters(),
      $query->getParameterTypes()
    );
  }

  /**
   * Update the records matching the where condition with the key-value pairs
   * @param array $data
   * @param string|array $where
   * @return int Number of affected rows
   */
  public function updateWhere( $data, $where )
  {
    $query = new qcl_data_db_Query( array( 'where' => $where) );
    return $this->getTable()->updateWhere(
      $this->prepareData( $data ),
      $query->createWhereStatement( $this->getModel() ),
      $query->getParameters(),
      $query->getParameterTypes()
    );
  }

  /**
   * Deletes one or more records in a table identified by id. This
   * does not delete dependencies!
   *
   * @param array|int $ids (array of) record id(s)
   * @return bool Success
   */
  public function deleteRow ( $ids )
  {
    return $this->getTable()->deleteRow( $ids );
  }

  /**
   * Deletes one or more records in the data table matching a where condition.
   * This does not delete dependencies!
   *
   * @param array|string $where where condition
   * @throws InvalidArgumentException
   * @return int Number of affected rows
   */
  public function deleteWhere ( $where )
  {
    if( ! is_string( $where ) and ! is_array( $where ) )
    {
      throw new InvalidArgumentException("Invalid argument");
    }

    $query = new qcl_data_db_Query( array( 'where' => $where ) );
    $sql   = $query->createWhereStatement( $this->getModel() );

    return $this->getTable()->deleteWhere(
      $sql,$query->getParameters(), $query->getParameterTypes()
    );
  }

  /**
   * Deletes all records from the database.
   * @return int number of affected rows
   */
  public function deleteAll()
  {
    return $this->getTable()->truncate();
  }

  /**
   * Destroys all data connected to the behavior: Deletes the table
   * that holds the model records.
   */
  public function destroy()
  {
    if( $this->getTable()->exists() )
    {
      $this->getTable()->delete();
    }
  }

  //-------------------------------------------------------------
  // convenience methods
  //-------------------------------------------------------------

  /**
   * Forwards log method request to model
   * @param $msg
   * @param $filters
   * @return void
   */
  protected function log( $msg, $filters )
  {
    $this->getModel()->log( $msg, $filters );
  }
}
?>