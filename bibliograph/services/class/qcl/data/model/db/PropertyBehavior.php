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

qcl_import( "qcl_data_model_PropertyBehavior" );

/**
 * Extending the property behavior of qcl_data_model_PropertyBehavior
 * with sql database support. This property behavior is to be used
 * by subclasses of qcl_data_model_AbstractActiveRecord, which already
 * defines the properties "id", "created", "modified".
 *
 * Since the properties will be persisted in the database, non-scalar
 * value types can be serialized to a string representation on demand.
 *
 * This behavior adds the following keys to the property definition:
 *
 * 'sqltype'    The full definition of a column in a database table
 *              as it would be used in a CREATE TABLE statement.
 *              Support for a more fine-grained type definition will
 *              be added later, which will make this feature more
 *              portable across database drivers.
 *
 * 'column'     The name of the column that the property is stored in.
 *              Defaults to the property name.
 *
 * 'unique'     Easy way to add a unique index on the column
 *
 * 'serialize'  If <true>, non-scalar values will be serialized before being
 *              stored in the database, and unserialized before when the
 *              record is loaded. Since serialized objects have a variable
 *              and potentially very large size, make sure to use
 *              data type (such as LONGTEXT or LONGBLOB) that will be able to
 *              store adequately long strings.
 *
 * 'export'     When the model data is exported, whether to include
 *              this property in the export data. Defaults to <true> if
 *              not defined.
 *
 *
 * <pre>
 * private $properties = array(
 *   "foo" => array(
 *     "check"    => "string",
 *     "init"     => "foo",
 *     "nullable" => true,
 *     "sqltype"  => "varchar(50)"
 *    ),
 *    "bar"  => array(
 *      "check"     => "integer",
 *      "init"      => 1,
 *      "nullable"  => true,
 *      "sqltype"   => "int(11)",
 *      "column"    => "real_bar_column"
 *    ),
 *    "baz"  => array(
 *      "check"     => "boolean",
 *      "init"      => true,
 *      "nullable"  => false,
 *      "sqltype"   => "int(1)",
 *      "export"    => false
 *    ),
 * );
 *
 * function __construct()
 * {
 *   $this->addProperties( $this->properties );
 *   parent::__construct();
 * }
 * </pre>
 *
 *
 * @see qcl_data_model_db_PropertyBehavior
 */
class qcl_data_model_db_PropertyBehavior
  extends qcl_data_model_PropertyBehavior
{
  /**
   * A persistent object which holds cached data on
   * table, property and column initialization
   * @var qcl_data_model_db_PropertyCache
   */
  private static $cache = null;

  /**
   * Whether the behavior has been initialized
   * @var bool
   */
  private $isInitialized = false;

  /**
   * Getter for managed model
   * @return qcl_data_model_db_ActiveRecord
   */
  protected function getModel()
  {
    return parent::getObject();
  }

  /**
   * Returns the static cache object
   * @return qcl_data_model_db_PropertyCache
   */
  protected function cache()
  {
    if ( ! self::$cache )
    {
      qcl_import( "qcl_data_model_db_PropertyCache" );
      self::$cache = new qcl_data_model_db_PropertyCache();
    }
    return self::$cache;
  }

  /**
   * Initializes the property behavior. Overrides parent class method.
   * @return void
   */
  public function init()
  {
    if ( ! $this->isInitialized )
    {
      if( $this->hasLog() ) $this->log( sprintf(
        "* Initializing model properties for '%s' using '%s'",
        $this->getModel()->className(), get_class( $this )
      ), QCL_LOG_PROPERTIES );

      /*
       * setup the table holding the data
       */
      $this->setupTable();

      /*
       * set up the properties
       */
      $this->setupProperties();

      /*
       * set up the primary index
       */
      $this->setupPrimaryIndex();

      /*
       * initialize the property values
       */
      $this->initPropertyValues();

      /*
       * remember we're intialized
       */
      $this->isInitialized = true;
    }
  }

  /**
   * Creates model table if it doesn't already exist. Fires the
   * "tableCreated" event in case a table gets newly created.
   *
   * @throws InvalidArgumentException
   * @return void
   */
  function setupTable()
  {

    $model = $this->getModel();

    /*
     * check if table exists, otherwise create
     */
    $cache     = $this->cache();
    $tableName = $model->getQueryBehavior()->getTableName();
    if ( ! $tableName )
    {
      throw new InvalidArgumentException("Invalid table name '$tableName'.");
    }

    if ( ! isset( $cache->tables[$tableName] ) or ! $cache->tables[$tableName] )
    {
      $table = $model->getQueryBehavior()->getTable();
      if( ! $table->exists() )
      {
        if( $this->hasLog() ) $this->log( sprintf(
          "Creating table '%s' for model '%s'.",
          $tableName, $model->className()
        ), QCL_LOG_PROPERTIES );

        $table->create();
        $cache->tables[$tableName] = true;

        /*
         * fire event to notify listeners
         */
        $this->getModel()->fireEvent("tableCreated");
      }
      else
      {
        if( $this->hasLog() ) $this->log( sprintf(
          "Table '%s' for model '%s' already exists.",
          $tableName, $model->className()
        ), QCL_LOG_PROPERTIES );
      }
    }
    else
    {
      if( $this->hasLog() ) $this->log( sprintf(
        "Cache: Table '%s' for model '%s' already exists.",
        $tableName, $model->className()
      ), QCL_LOG_PROPERTIES );
    }
  }


  /**
   * Sets up the model properties, creating corresponding
   * tables and columns if they doesn't already exist.
   *
   * @param null $filter
   * @throws LogicException
   * @throws qcl_core_PropertyBehaviorException
   * @throws qcl_data_model_Exception
   * @return void
   */
  public function setupProperties( $filter=null )
  {
    $properties = $this->properties;
    $model      = $this->getModel();
    $qBehavior  = $model->getQueryBehavior();
    $adpt       = $qBehavior->getAdapter();
    $table      = $qBehavior->getTable();
    $tableName  = $qBehavior->getTableName();
    $cache      = $this->cache();
    
    $createTrigger = false; 
    
    /*
     * check table name
     */
    if ( ! $tableName )
    {
      throw new qcl_core_PropertyBehaviorException("Cannot setup properties. No table name!");
    }

    /*
     * setup cache
     */
    if ( ! isset( $cache->properties[$tableName] ) )
    {
      $cache->properties[$tableName] = array();
    }
    $cachedProps = $cache->properties[$tableName];

    $clazz = $model->className();
    if( $this->hasLog() ) $this->log( "Setting up properties for class '$clazz'.");

    /*
     * setup table columns
     * @todo separate by task into individual methods
     */
    foreach( $properties as $property => $prop )
    {
      
      if( $this->hasLog() ) $this->log( "Setting up property '$property' ($clazz).");
      
      /*
       * skip elements that are not in the filter if filter has been set
       */
      if ( is_array( $filter ) and ! in_array( $property, $filter ) )
      {
        continue;
      }

      /*
       * save the property definition in serialized form
       */
      $serializedProps = serialize( $prop );
      if ( isset( $cachedProps[$property] )
        and $cachedProps[$property] == $serializedProps )
      {
        if( $this->hasLog() ) $this->log( sprintf(
          "Property '%s' of class '%s', table '%s' has not changed.",
          $property, $model->className(), $tableName
        ), QCL_LOG_PROPERTIES );
        continue;
      }

      /*
       * real column name of the property, defaults
       * to the property name
       */
      if ( ! isset( $prop['column'] ) or ! $prop['column'] )
      {
        $this->properties[$property]['column']  = $property;
      }
      else
      {
        $this->properties[$property]['column'] = $prop['column'];
      }
      $column = $this->properties[$property]['column'];

      /*
       * skip "id" column since it is created by default
       */
      if ( $property == "id" ) continue;

      /*
       * determine sql type
       */
      if ( isset( $prop['sqltype'] ) )
      {
        $sqltype = $prop['sqltype'];
      }
      else
      {
        if ( isset( $prop['serialize'] ) and $prop['serialize'] == true )
        {
          $sqltype = $adpt->getColumnTypeDefinition( "serialized-data" );
        }
        else
        {
          // @todo implement automatic sqltype determination
          throw new LogicException("No 'sqltype' provided for '$property'");
        }
      }

      /*
       * 'CURRENT_TIMESTAMP'
       */
      if( strtolower($sqltype) == "current_timestamp" )
      {
        $sqltype = $qBehavior->getAdapter()->currentTimestampSql();
        // if this type is not available, try to create a trigger
        if ( ! $sqltype )
        {
          $sqltype = "DATETIME";
          $createTrigger = true;
        }
      }

      /*
       * Allow NULL? sqltype definition has preference
       */
      if ( ! (strstr( $sqltype, 'NULL') || strstr( $sqltype, 'null') ) )
      {
        /*
         * if 'nullable' property is provided
         */
         if( isset( $prop['nullable'] ) )
         {
           if ( $prop['nullable'] === true)
           {
              $sqltype .= ' NULL';
           }
           else
           {
              if ( ! isset( $prop['init'] ) )
              {
                throw new qcl_data_model_Exception( sprintf(
                  "Property '%s.%s' must provide an init value in order to be 'not nullable'.",
                  get_class( $this->model), $property
                ) );
              }
              switch ( $prop['check'] )
              {
                case "boolean":
                  $default = $prop['init'] ? 1 : 0;
                  break;
                case "integer":
                  $default = $prop['init'];
                  break;
                default:  
                  $default = "'" . $prop['init'] . "'";
              }
              $sqltype .= ' NOT NULL DEFAULT ' . $default;
           }
         }
         
         /*
          * otherwise, by default add "NULL" to sql type
          */
         else
         {
            $sqltype .= ' NULL';
         }
      }

      /*
       * check type
       */
      if ( ! $sqltype )
      {
        throw new qcl_data_model_Exception( sprintf(
          "Property '%s.%s' does not have a valid 'sqltype' definition.",
          get_class( $this->model), $property
        ) );
      }

      /*
       * if column does not exist, create it
       */
      if ( ! $table->columnExists( $column ) )
      {
        $table->addColumn( $column, $sqltype );

        /*
         * unique index on column?
         */
        if ( isset( $prop['unique'] ) and $prop['unique'] === true )
        {
          $indexName = "unique_{$column}";
          if( ! $table->indexExists( $indexName ) )
          {
            $table->addIndex( "unique", $indexName, array( $column ) );  
          }
          else
          {
            if( $this->hasLog() ) $this->log( "Unique index for property '$prop' already exists.");
          }
        }
        
        // trigger
        if( $createTrigger )
        {
          $qBehavior->getAdapter()->createTimestampTrigger( $tableName, $column );
        }
      }

      /*
       * if not, check if it has changed
       */
      else
      {
        $curr_sqltype = $table->getColumnDefinition( $column );
        if (strtolower( $curr_sqltype )  != strtolower( $sqltype ) )
        {
          if( $this->hasLog() ) $this->log( sprintf(
            "Column '%s' has changed from '%s' to '%s'",
            $column, $curr_sqltype, $sqltype
          ) );
        }
        else
        {
          if( $this->hasLog() ) $this->log( "Column '$column' has not changed.");
        }
      }
    

      /*
       * save in cache
       */
      $cache->properties[$tableName][$property] = $serializedProps;

    } // end foreach
  }

  /**
   * Given the name of the property, return the name of the column
   * that the property data is stored in.
   * @param $property
   * @return string
   */
  public function getColumnName( $property )
  {
    $this->check( $property );
    $column = $this->properties[$property]['column'];
    if ( ! $column )
    {
      // @todo Doesn't work if foreign keys are manually set
      return $property;
      /*throw new qcl_core_PropertyBehaviorException( sprintf(
        "Cannot convert property '%s' into column name", $property
      ) );*/
    }
    return $column;
  }

  /**
   * Resets the property behavior and the internal cache
   * @return void
   */
  public function reset()
  {
    $this->isInitialized = false;
    $this->cache()->reset();
  }
  
 /**
   * Setups the primary index of the table
   * @return void
   */
  public function setupPrimaryIndex()
  {

    $table = $this->getModel()->getQueryBehavior()->getTable();
    //$tableName = $this->getModel()->getQueryBehavior()->getTableName();

    $primaryIndexCols = $this->getModel()->getQueryBehavior()->getPrimaryIndexProperties();
    $existingPrimaryKey = $table->getPrimaryKey();

    if((count($existingPrimaryKey) == 0) && (count($primaryIndexCols) != 0)) {
        // set new primary index
        $table->addPrimaryKey($primaryIndexCols);
    } elseif(count(array_diff($primaryIndexCols, $existingPrimaryKey)) != 0) {
        // change existing primary index
        $table->modifyPrimaryKey($primaryIndexCols);
    }

  }  
}
?>