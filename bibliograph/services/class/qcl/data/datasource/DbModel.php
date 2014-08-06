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

qcl_import("qcl_data_model_db_NamedActiveRecord");
qcl_import("qcl_data_datasource_Manager");

/**
 * Class modeling datasource information that is stored in a
 * typical sql database. Note that this is not the datasource itself,
 * which can be of any type, but only the information ON the datasource
 * plus some methods to operate with this information. This is the normal
 * case, all other datasource models inherit from this. If you want to
 * use a different storage for your datasource information, you must write
 * custom child classes for the other datasource models.
 *
 * @todo create interface!
 */
class qcl_data_datasource_DbModel
  extends qcl_data_model_db_NamedActiveRecord
{

  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  /**
   * The name of the schema, needed for self-
   * registering
   * @var string
   */
  protected $schemaName = null;

  /**
   * The description of the schema, needed for self-
   * registering
   * @var string
   */
  protected $description = null;

  /**
   * The type of the datasource. Defaults to "mysql"
   * @var string
   */
  protected $type = "mysql";

  /**
   * Table name
   * @var string
   */
  protected $tableName = "data_Datasource";

  /**
   * The foreign key of this model
   */
  protected $foreignKey = "DatasourceId";

  /**
   * The model properties
   * FIME Replace "title" with "name"
   */
  private $properties = array(
    'title' => array(
      'check'   => "string",
      'sqltype' => "varchar(100)"
    ),
    'description' => array(
      'check'   => "string",
      'sqltype' => "varchar(255)"
    ),
    'schema' => array(
      'check'   => "string",
      'sqltype' => "varchar(100)"
    ),
    'type' => array(
      'check'   => "string",
      'sqltype' => "varchar(20)"
    ),
    'host' => array(
      'check'   => "string",
      'sqltype' => "varchar(200)"
    ),
    'port' => array(
      'check'   => "integer",
      'sqltype' => "int(11)"
    ),
    'database' => array(
      'check'   => "string",
      'sqltype' => "varchar(100)"
    ),
    'username' => array(
      'check'   => "string",
      'sqltype' => "varchar(50)"
    ),
    'password' => array(
      'check'   => "string",
      'sqltype' => "varchar(50)"
    ),
    'encoding' => array(
      'check'   => "string",
      'sqltype' => "varchar(20)",
      'nullable'  => false,
      'init'      => "utf-8"
    ),
    'prefix' => array(
      'check'   => "string",
      'sqltype' => "varchar(20)"
    ),
    'resourcepath' => array(
      'check'   => "string",
      'sqltype' => "varchar(255)"
    ),
    'active' => array(
      'check'     => "boolean",
      'sqltype'   => "tinyint(1)",
      'nullable'  => false,
      'init'      => true
    ),
    'readonly' => array(
      'check'     => "boolean",
      'sqltype'   => "tinyint(1)",
      'nullable'  => false,
      'init'      => false
    ),
    'hidden' => array(
      'check'   => "boolean",
      'sqltype' => "tinyint(1)",
      'nullable'  => false,
      'init'      => false
    )
  );

  /**
   * Model relations
   */
  private $relations = array(
    'Datasource_User' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_access_model_User" )
    ),
    'Datasource_Role' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_access_model_Role" )
    ),
    'Datasource_Group' => array(
      'type'        => QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY,
      'target'      => array( 'class' => "qcl_access_model_Group" )
    )
  );

  //-------------------------------------------------------------
  // Class properties
  //-------------------------------------------------------------


  /**
   * Models that are attached to this datasource
   * @var array
   */
  private $modelMap = array();

  //-------------------------------------------------------------
  // Initialization
  //-------------------------------------------------------------

  /**
   * Constructor, adds properties
   */
  public function __construct()
  {
    parent::__construct();
    $this->addProperties( $this->properties );
    $this->addRelations( $this->relations, __CLASS__ );

    $this->formData = array(
      'title'       => array(
        'label'       => $this->tr("Name")
      ),
      'description' => array(
        'type'        => "TextArea",
        'lines'       => 3,
        'label'       => $this->tr("Description")
      ),
      'schema'      => array(
        'type'        => "selectbox",
        'label'       => $this->tr("Schema"),
        'delegate'    => array(
          'options'     => "getSchemaOptions"
        )
      ),
      'type'        => array(
        'label'       => $this->tr("Type")
      ),
      'host'        => array(
        'label'       => $this->tr("Server host"),
        'placeholder' => "The database server host, usually 'localhost'"
      ),
      'port'        => array(
        'label'       => $this->tr("Server port"),
        'marshaler'   => array(
          'marshal'    => array( 'function' => "qcl_toString" ),
          'unmarshal'  => array( 'function' => "qcl_toInteger" )
        ),
        'placeholder' => "The database server port, usually 3306 for MySql"
      ),
      'database'    => array(
        'label'       => $this->tr("Database name"),
        'placeholder' => "The name of the database",
        'validation'  => array(
          'required'    => true
        )
      ),
      'username'    => array(
        'label'       => $this->tr("Database user name")
      ),
      'password'    => array(
        'label'       => $this->tr("Database user password")
      ),
      'encoding'    => array(
        'label'       => $this->tr("Database encoding"),
        'default'     => 'utf-8'
      ),
      'prefix'      => array(
        'label'       => $this->tr("Datasource prefix")
      ),
      'resourcepath' => array(
        'label'       =>  $this->tr("Resource path")
      ),
      'active'        => array(
        'type'    => "SelectBox",
        'label'   =>  $this->tr("Status"),
        'options' => array(
          array( 'label' => "Disabled", 'value' => false ),
          array( 'label' => "Active",   'value' => true )
        )
      )
    );
  }

  /**
   * Returns singleton instance of this class.
   * @return qcl_data_datasource_DbModel
   */
  public static function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  //-------------------------------------------------------------
  // Getters and setters for model properties
  //-------------------------------------------------------------

  /**
   * Getter for 'title' property
   * @return string
   */
  public function getTitle()
  {
    return $this->_get("title");
  }

  /**
   * Alias of getTitle()
   * @return string
   */
  public function getName()
  {
    return $this->getTitle();
  }

  /**
   * Getter for 'schema' property
   * @return string
   */
  public function getSchema()
  {
    return $this->_get("schema");
  }

  /**
   * Getter for 'description' property
   * @return string
   */
  public function getDescription()
  {
    return $this->_get("description");
  }

  /**
   * Getter for 'type' property
   * @return string
   */
  public function getType()
  {
    return $this->_get("type");
  }

  /**
   * Returns the default type set by the class
   * @return string
   */
  public function getDefaultType()
  {
    return $this->type;
  }

  /**
   * Getter for 'host' property
   * @return string
   */
  public function getHost()
  {
    return $this->_get("host");
  }

  /**
   * Getter for 'port' property
   * @return int
   */
  public function getPort()
  {
    return $this->_get("port");
  }

  /**
   * Getter for 'database' property
   * @return string
   */
  public function getDatabase()
  {
    return $this->_get("database");
  }

  /**
   * Getter for 'username' property
   * @return string
   */
  public function getUsername()
  {
    return $this->_get("username");
  }

  /**
   * Getter for 'password' property
   * @return string
   */
  public function getPassword()
  {
    return $this->_get("password");
  }

  /**
   * Getter for 'encoding' property
   * @return string
   */
  public function getEncoding()
  {
    return $this->_get("encoding");
  }

  /**
   * Getter for 'prefix' property
   * @return string
   */
  public function getPrefix()
  {
    return $this->_get("prefix");
  }

  /**
   * Getter for 'resourcepath' property
   * @return string
   */
  public function getResourcepath()
  {
    return $this->_get("resourcepath");
  }

  /**
   * Boolean getter for 'active' property
   * @return bool
   */
  public function isActive()
  {
    return $this->_get("active");
  }

//  /**
//   * Boolean getter for 'readonly' property
//   * @return boolean
//   * FIXME results in an infinite loop when uncommented
//   */
//  public function isReadonly()
//  {
//    return $this->_get("readonly");
//  }

  /**
   * Boolean getter for 'hidden' property
   * @return bool
   */
  public function isHidden()
  {
    return $this->_get("hidden");
  }

  //-------------------------------------------------------------
  // internal methods
  //-------------------------------------------------------------

  /**
   * Checks whether the model has a valid schema name
   */
  protected function checkSchemaName()
  {
    if ( ! $this->schemaName  )
    {
      throw new LogicException("You must define the 'schemaName' property to be able to self-register the datasource.");
    }
  }

  /**
   * Getter for manager
   * @return qcl_data_datasource_Manager
   */
  protected function getManager()
  {
    qcl_import( "qcl_data_datasource_Manager" );
    return qcl_data_datasource_Manager::getInstance();
  }

  /**
   * Returns an 'options' array for a select box with the
   * schema names
   *
   * @return array
   */
  public function getSchemaOptions()
  {
    $schemata = qcl_data_datasource_Manager::getInstance()->schemas();
    $options = array();
    foreach( $schemata as $schema )
    {
      $options[] = array(
        'label' => $schema,
        'value' => $schema
      );
    }
    return $options;
  }

  //-------------------------------------------------------------
  // API methods
  //-------------------------------------------------------------

  /*
   * Return the name of the schema of this datasource model
   */
  public function getSchemaName()
  {
    return $this->schemaName;
  }

  /**
   * Self-registers the datasource model with the manager.
   * Returns the registry model instance.
   *
   * @return qcl_data_datasource_RegistryModel
   * @throws qcl_data_model_RecordExistsException
   *    Thrown if schema name already exists
   */
  public function registerSchema()
  {
    $this->checkSchemaName();
    $dsManager = qcl_data_datasource_Manager::getInstance();
    return $dsManager->registerSchema( $this->schemaName, array(
      'class'       => $this->className(),
      'description' => $this->description
    ) );
  }

  /**
   * Self-unregisters schema
   * @return void
   */
  public function unregisterSchema()
  {
    $this->checkSchemaName();
    $dsManager = qcl_data_datasource_Manager::getInstance();
    $dsManager->unregisterSchema( $this->schemaName );
  }


  /**
   * Registers the models that are part of the datasource
   * @param array $modelMap Associative array that maps the
   * type of model to the model classes
   * @throws InvalidArgumentException
   * @return void
   * @todo fix and document data schema
   */
  public function registerModels( $modelMap )
  {
    if ( ! is_map( $modelMap ) )
    {
      throw new InvalidArgumentException( "Invalid argument" );
    }

    /*
     * iterate through the map an register each model
     */
    foreach( $modelMap as $type => $data )
    {
      $class = null;
      if ( isset( $data['class'] ) ) // legacy
      {
        $class = $data['class'];
        $data['model']['class'] = $class;
      }
      elseif ( isset( $data['model']['class'] ) )
      {
        $class = $data['model']['class'];
      }
      else
      {
        throw new InvalidArgumentException( sprintf(
          "No class given for datasource %s, model type %s",
          $this, $type
        ) );
      }

      // legacy
      if ( isset( $data['replace'] ) )
      {
        $data['model']['replace'] = $data['replace'];
      }

      /*
       * import class
       */
      qcl_import( $class );
      if ( ! class_exists( $class )  ) // @todo check interface
      {
        throw new InvalidArgumentException("Invalid model class '$class'");
      }

      /*
       * store data
       */
      $this->modelMap[$type] = $data;

      $this->log( sprintf(
        "Datasource '%s': registered model of type '%s' with class '%s'",
        $this, $type, $class
      ), QCL_LOG_DATASOURCE );
    }
  }

  /**
   * Returns the types all the models registered
   * @return array
   */
  public function modelTypes()
  {
    return array_keys( $this->modelMap );
  }

  

  /**
   * Return the shared model instance for the given type.
   * @param string $type
   * @throws InvalidArgumentException
   * @return qcl_data_model_AbstractActiveRecord
   */
  public function getInstanceOfType( $type )
  {
    $this->checkLoaded();

    if ( ! isset( $this->modelMap[$type] ) )
    {
      throw new InvalidArgumentException("Model of type '$type' is not registered");
    }

    /*
     * get and return the model
     */
    $class = $this->modelMap[$type]['model']['class'];
    //$namedId = $this->namedId();

    if ( ! isset( $this->modelMap[$type]['model']['instance'] ) )
    {
      $model = new $class( $this );
      $this->modelMap[$type]['model']['instance'] = $model;
    }

    return $this->modelMap[$type]['model']['instance'];
  }
  
/**
   * Resets the shared model instance for the given type.
   * @param string $type
   * @throws InvalidArgumentException
   * @return void
   */
  public function resetInstanceOfType( $type )
  {
    if ( ! isset( $this->modelMap[$type] ) )
    {
      throw new InvalidArgumentException("Model of type '$type' is not registered");
    }
    unset( $this->modelMap[$type]['model']['instance'] );
  }  

  /**
   * Creates a model instance for the given type. Avoid using this
   * method, use the shared instance instead whenever possible for
   * performance reasons.
   *
   * @param string $type
   * @throws InvalidArgumentException
   * @return qcl_data_model_AbstractActiveRecord
   */
  public function createInstanceOfType( $type )
  {
    $this->checkLoaded();

    if ( ! isset( $this->modelMap[$type] ) )
    {
      throw new InvalidArgumentException("Model of type '$type' is not registered");
    }
    
		/*
     * create and return the model
     */
    $class = $this->modelMap[$type]['model']['class'];
    return new $class( $this );    
  }

  /**
   * Returns the class name of the model of the given type.
   * A model instance does not need to exist at this point.
   * @param string $type
   * @throws InvalidArgumentException
   * @return string The class name
   */
  public function getModelClassByType( $type )
  {
    if ( ! isset( $this->modelMap[$type] ) )
    {
      throw new InvalidArgumentException("Model of type '$type' is not registered");
    }
    return $this->modelMap[$type]['model']['class'];
  }

  /**
   * Return the model that corresponds to the given class
   * @param string $class
   * @throws InvalidArgumentException
   * @return qcl_data_model_db_AbstractActiveRecord
   */
  public function getInstanceByClass( $class )
  {
    foreach( $this->modelMap as $type => $data )
    {
      if ( $data['model']['class'] == $class
        or ( isset( $data['model']['replace'] )
        and $data['model']['replace'] == $class  ) )
      {
        return $this->getInstanceOfType($type);
      }
    }
    throw new InvalidArgumentException("Datasource $this does not have a model of class '$class'.");
  }

  /**
   * Checks if the model type exists and throws an exception if not.
   * @param string $type
   * @throws InvalidArgumentException
   * @return void
   */
  public function checkType( $type )
  {
    if ( ! isset( $this->modelMap[$type] ) )
    {
      throw new InvalidArgumentException("Datasource $this has no model type '$type'." );
    }
  }

  /**
   * Returns the controller for the given model type, if defined.
   * @param string $type
   *    The model type
   * @return qcl_data_controller_Controller|null
   *    The singleton instance of the controller or null if none exists
   */
  public function getControllerForType( $type )
  {
    $this->checkType( $type );
    if ( ! isset( $this->modelMap[$type]['controller']['class'] ) )
    {
      return null;
    }
    else
    {
      $class = $this->modelMap[$type]['controller']['class'];
      return qcl_getInstance( $class );
    }
  }

  /**
   * Returns the rpc service name for the given model type, if defined.
   * @param string $type
   *    The model type
   * @return string|null
   *    The service name or null if none exists
   */
  public function getServiceNameForType( $type )
  {
    $this->checkType( $type );
    if ( ! isset( $this->modelMap[$type]['controller']['service'] ) )
    {
      return null;
    }
    return $this->modelMap[$type]['controller']['service'];
  }

  /**
   * Returns the url of the datasource, if any
   * @return string
   */
  public function getUrl()
  {
    $this->id(); // makes sure a record is loaded

    $url = $this->getType() . "://" . $this->getHost();
    if ( $port = $this->getPort() )
    {
      $url .= ":$port";
    }
    return $url;
  }

  /**
   * Returns a PDO-compatible DSN string from the currently loaded record
   * @return string
   */
  public function getDsn()
  {
    $this->id(); // makes sure a record is loaded
    return sprintf(
      "%s:host=%s;port=%s;dbname=%s",
      $this->getType(), $this->getHost(), $this->getPort(), $this->getDatabase()
    );
  }

  /**
   * Populates the model properties from a PDO-compatible DSN string
   * @param $dsn
   * @throws LogicException
   * @return qcl_data_datasource_DbModel returns itself
   */
  public function setDsn( $dsn )
  {
    qcl_assert_valid_string($dsn, "DSN cannot be empty!");
    preg_match( "/(.+):host=(.+);port=(.+);dbname=(.+)/",$dsn,$matches);
    try
    {
      $this->set( array(
        'type'     => $matches[1],
        'host'     => $matches[2],
        'port'     => (int) $matches[3],
        'database' => $matches[4],
      ) );
    }
    catch( PDOException $e)
    {
      throw new LogicException("Invalid DSN: $dsn");
    }

    return $this;
  }

  /**
   * Returns the database connection object of the currently
   * loaded datasource record
   * @return qcl_data_db_IAdapter
   */
  public function createAdapter()
  {
    return qcl_data_db_Manager::getInstance()->createAdapter( $this->getDsn() );
  }

  /**
   * Returns the prefix for tables used by the models connected to this database.
   * Defaults to the datasource name plus underscore if no prefix has been specified
   * in the record data.
   *
   * @return string
   */
  public function getTablePrefix()
  {
    $this->id(); /// make sure a record is loaded
    return
      $this->getQueryBehavior()->getTablePrefix() .
      either( /*$this->getPrefix(),*/ $this->namedId() ) . "_"; // FIXME
  }

  /**
   * If the datasource is a file storage. Defaults to false in normal
   * datasources
   * @return bool
   */
  public function isFileStorage()
  {
    return false;
  }

  //-------------------------------------------------------------
  // Overwritten methods
  //-------------------------------------------------------------

  /**
   * Disabled
   */
  public function destroy()
  {
    $this->init();
    $this->log( "Destroying all models of datasource $this", QCL_LOG_DATASOURCE);
    foreach( $this->modelTypes() as $type )
    {
      $this->getInstanceOfType( $type )->destroy();
    }
    $this->log( "Deleting datasource $this", QCL_LOG_DATASOURCE);
    $this->getQueryBehavior()->deleteWhere( array( "id" => $this->id() ) );
  }


  /**
   * Disabled
   */
  public function deleteAll()
  {
    throw new LogicException( "Not allowed.");
  }

  /**
   * Deletes the datasource model instance data and the data of all
   * the connected models from the database.
   *
   * @return boolean
   */
  public function delete()
  {
    $this->init();

    /*
     * delete all model data
     */
    foreach( $this->modelTypes() as $type )
    {
      $this->log( "Destroying model '$type' of datasource $this", QCL_LOG_DATASOURCE);
      $this->getInstanceOfType( $type )->destroy();
    }

    /*
     * delete transaction entries
     */
    qcl_import("qcl_data_model_db_TransactionModel");
    qcl_data_model_db_TransactionModel::getInstance()->deleteWhere(array(
      'datasource'  => $this->namedId()
    ));

    /*
     * delete datasource
     */
    $this->getManager()->deleteDatasource( $this->namedId(), false );
  }
}

