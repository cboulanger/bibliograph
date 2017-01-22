<?php
/*
 * qcl - the qooxdoo component library
 *
 * http://qooxdoo.org/contrib/project/qcl/
 *
 * Copyright:
 *   2007-2015 Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Christian Boulanger (cboulanger)
 */

qcl_import( "qcl_data_datasource_RegistryModel" );

/**
 * Datasource manager singleton class
 */
class qcl_data_datasource_Manager
  extends qcl_core_Object
{

  //-------------------------------------------------------------
  // Class properties
  //-------------------------------------------------------------

  /**
   * cache for datasource model objects
   * @var array
   */
  private $datasourceModels = array();

  //-------------------------------------------------------------
  // Initialization
  //-------------------------------------------------------------

  /**
   * Returns singleton instance of this class.
   * @return qcl_data_datasource_Manager
   */
  static function getInstance()
  {
    return qcl_getInstance(__CLASS__);
  }

  //-------------------------------------------------------------
  // Schema registration
  //-------------------------------------------------------------

  /**
   * Getter for  storage object
   * @return qcl_data_datasource_RegistryModel
   */
  public function getRegistryModel()
  {
    return qcl_data_datasource_RegistryModel::getInstance();
  }

  /**
   * Register datasource.
   * @param $schemaName
   * @param array $options Map of additional information. At least the
   * key 'class' must be provided
   * @return int
   * @throws InvalidArgumentException
   * @internal param string $name Name of datasource schema
   * @internal param string $description Long description
   */
  public function registerSchema( $schemaName, $options )
  {
    if ( ! isset( $options['class'] ) or ! $options['class'] )
    {
      throw new InvalidArgumentException("Missing 'class' argument");
    }

    $class = $options['class'];

    // todo: decide if class must exists at time of registering so that we can do dependency checks or not
    if ( ! is_string( $class) ) //or ! $class instanceof qcl_data_datasource_DbModel )
    {
      throw new InvalidArgumentException("Invalid class '$class'. Must implement qcl_data_datasource_IModel ");
    }

    $this->log("Registering class '$class' for schema '$schemaName'", QCL_LOG_DATASOURCE);

    /*
     * create schema
     */
    return $this->getRegistryModel()->createIfNotExists( $schemaName, $options );
  }

  /**
   * Unregister datasource.
   * @param $schemaName
   * @param bool $deleteAll If true, also delete all datasources
   *   and their models
   * @throws InvalidArgumentException
   * @internal param string $name Name of datasource schema
   * @todo de-activate all datasources
   */
  public function unregisterSchema( $schemaName, $deleteAll=false )
  {
    if ( $deleteAll )
    {
      $schemaDatasources = $this->getDatasourceNamesBySchema( $schemaName );
      foreach( $schemaDatasources as $dsName )
      {
        $this->deleteDatasource( $dsName, true );
      }
    }
    $this->log("Unregistering schema '$schemaName'", QCL_LOG_DATASOURCE);
    $registry = $this->getRegistryModel();
    try
    {
      $registry->load( $schemaName );
      $registry->delete();
    }
    catch( qcl_data_model_RecordNotFoundException $e )
    {
      throw new InvalidArgumentException("Schema '$schemaName' does not exist.");
    }

  }

  /**
   * Returns an array of datasource names that are registered for the model schema
   * @param string $schemaName
   * @return array
   */
  public function getDatasourceNamesBySchema( $schemaName )
  {
    $dsModel = $this->getDatasourceModel();
    return $dsModel->getQueryBehavior()->fetchValues(NAMED_ID,array(
      'schema'  => $schemaName
    ) );
  }


  /**
   * Return the class name for a datasource schema name
   * @param string $schemaName
   * @throws InvalidArgumentException
   * @return string
   */
  public function schemaClass( $schemaName )
  {
    qcl_assert_valid_string( $schemaName );

    $registry = $this->getRegistryModel();
    try
    {
      $registry->load( $schemaName );
      $class = $registry->getClass();
      if ( ! $class )
      {
        throw new InvalidArgumentException( "No class registered for schema '$schemaName'" );
      }
      return $class;
    }
    catch ( qcl_data_model_RecordNotFoundException $e )
    {
      throw new InvalidArgumentException( "Schema '$schemaName' does not exist." );
    }

  }

  /**
   * Returns a list of registered schema names
   * @return array
   */
  public function schemas()
  {
    $registry = $this->getRegistryModel();
    return $registry->getQueryBehavior()->fetchValues( "namedId" );
  }

  //-------------------------------------------------------------
  // Datasource models
  //-------------------------------------------------------------

  /**
   * Returns the model which stores the datasource information and
   * on which all other datasource models are based.
   * @return qcl_data_datasource_DbModel
   */
  public function getDatasourceModel()
  {
    static $dsModel = null;
    if ( $dsModel === null )
    {
      qcl_import( "qcl_data_datasource_DbModel" );
      $dsModel =  qcl_data_datasource_DbModel::getInstance();
      $dsModel->init();
    }
    return $dsModel;
  }

  /**
   * Returns a list of registered schema names
   * @return array
   */
  function datasources()
  {
    return $this->getDatasourceModel()->getQueryBehavior()->fetchValues( "namedId" );
  }

  /**
   * Creates a datasource with the given name, of the given schema.
   * @param string $name
   * @param string $schema
   * @param array $properties
   *    An optional map of properties of the datasource model instance
   *    to create. You can, for example, set the "dsn" property this way.
   * @throws LogicException
   * @return qcl_data_datasource_DbModel
   */
  public function createDatasource( $name, $schema, $properties=array() )
  {
    qcl_assert_valid_string( $name, "Invalid name argument" );
    qcl_assert_valid_string( $schema, "Invalid schema argument" );

    /*
     * create a new generic model with the given properties
     */
    $dsModel = $this->getDatasourceModel();
    $dsModel->create( $name, array(
      "schema" => $schema,
      "type" => "placeholder"
    ) );
    $dsModel->set( $properties );
    $dsModel->save();

    /*
     * get the new specialized model
     */
    $dsModel = $this->getDatasourceModelByName( $name );
    if ( $dsModel->getType() == "placeholder" )
    {
      $type = $dsModel->getDefaultType();
      if( ! $type ){
        $class = $dsModel->className();
        throw new LogicException("No default type for datasource $name [$class]");
      }
      $dsModel->setType( $type );
      $dsModel->save();
    }

    return $dsModel;
  }

  /**
   * Retrieves and initializes the datasource model object for a
   * datasource with the given name. Caches the result during a
   * request.
   *
   * @param string $name Name of datasource
   * @return qcl_data_datasource_DbModel
   * @throws InvalidArgumentException
   * @todo change thrown error!
   */
  public function getDatasourceModelByName( $name )
  {
    qcl_assert_valid_string("Invalid datasource name '$name'.");

    /*
     * create model object if it hasn't been created already
     */
    if ( ! isset( $this->datasourceModels[$name] ) )
    {

      /*
       * get datasource model
       */
      $dsModel = $this->getDatasourceModel();

      try
      {
        $dsModel->load( $name );
      }
      catch( qcl_data_model_RecordNotFoundException $e)
      {
        throw new InvalidArgumentException("A datasource '$name' does not exist.");
      }

      /*
       * get schema name and class
       */
      $schemaName = $dsModel->getSchema();
      if( ! $schemaName )
      {
        throw new InvalidArgumentException("Datasource '$name' does not have a schema.");
      }
      $schemaClass = $this->schemaClass( $schemaName );

      $this->log( "Datasource '$name' has schema name '$schemaName' and class '$schemaClass'.", QCL_LOG_DATASOURCE );

      /*
       * instantiate, load data and initialize the datasource and the attached models
       */
      qcl_import( $schemaClass );
      $dsModel = new $schemaClass;
      $dsModel->load( $name );

      /*
       * save reference to the object
       */
      $dsModel->manager = $this;
      $this->datasourceModels[$name] = $dsModel;
    }

    /*
     * return cached model object
     */
    return $this->datasourceModels[$name];
  }

  /**
   * Deletes a datasource from the database and from the manager cache.
   * @param $name
   * @param bool|\Whether $deleteModels Whether to delete the datasource's models, too
   *   Defaults to true
   * @return unknown_type
   */
  public function deleteDatasource( $name, $deleteModels=true )
  {
    $dsModel = $this->getDatasourceModelByName( $name );
    if ( $deleteModels )
    {
      foreach( $dsModel->modelTypes() as $type )
      {
        $this->log( "Destroying model type '$type' of datasource '$name' ", QCL_LOG_DATASOURCE );
        $dsModel->getInstanceOfType( $type )->destroy();
      }
    }
    $this->log( "Deleting datasource '$name' ", QCL_LOG_DATASOURCE );
    $dsModel->getQueryBehavior()->deleteWhere( array( NAMED_ID => $name ) );
    unset( $this->datasourceModels[$name] );
  }


  /**
   * Destroys all data connected to the model, such as tables etc.
   * Use with caution, as this may have desastrous effects.
   */
  public function destroyAll()
  {
    /*
     * destroy each datasource in the table
     */
    foreach( $this->datasources() as $name )
    {
      $this->getDatasourceModelByName( $name )->destroy();
    }

    /*
     * destroy the table itself
     */
    $dsModel= $this->getDatasourceModel();
    $this->log( "Destroying the datasource table for " . get_class( $dsModel ), QCL_LOG_DATASOURCE);
    $dsModel->getQueryBehavior()->getTable()->delete();
    qcl_data_model_db_ActiveRecord::resetBehaviors();
  }


  /**
   * Deletes all datasources with their model data.
   * Use with caution, as this may have desastrous effects.
   * @return boolean
   */
  public function emptyAll()
  {
    /*
     * delete each datasource in the table
     */
    foreach( $this->datasources() as $name )
    {
      $this->getDatasourceModelByName( $name )->delete();
    }
    qcl_data_model_db_ActiveRecord::resetBehaviors();
  }

}
