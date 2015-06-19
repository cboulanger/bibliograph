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
 *  * Oliver Friedrich (jesus77)
 */

qcl_import( "qcl_data_model_Model" );
qcl_import( "qcl_data_model_IActiveRecord" );
qcl_import( "qcl_data_model_IRelationalModel" );

/**
 * Abstract class for all classes that implement a data model based on
 * the "Active Record" pattern. The object holds data for one record at
 * the time, but provides ways of cycling through a record set if more
 * than one record is retrieved by a query to the data container. The
 * implementing class must implement the getQueryBehaviorMethod() returning
 * an instance of the behavior class that implements qcl_model_IQueryBehavior.
 * @todo convert into abstract class
 */

abstract class qcl_data_model_AbstractActiveRecord
  extends qcl_data_model_Model
  implements qcl_data_model_IActiveRecord, qcl_data_model_IRelationalModel
{

  /**
   * The name of the property that is used to reference the
   * model record in a different model
   * @var string
   */
  protected $foreignKey;

  /**
   * The object instance of the datasource that this model belongs to.
   * The datasource provides shared resources for models.
   * @var qcl_data_datasource_DbModel
   */
  private $datasourceModel;


  /**
   * The last query executed
   * @var qcl_data_db_Query
   */
  protected $lastQuery;

  /**
   * The original data loaded from the database
   * @var array
   */
  protected $_data = array();

  /**
   * Whether model record data is loaded
   * @var bool
   */
  protected $_loaded = false;
  
	/**
	 * Flag to indicate that a record has been deleted
	 * @var bool
	 */
	protected $_isDeleted = false;  
  
  /**
   * The number of seconds after which the record is automatically
   * deleted if not modified. Defaults to null (= no expiration).
   * @var int
   */
	protected $expiresAfter = null;

  /**
   * Whether to change the transaction id after a change to the model
   * @var bool
   */
  protected $incrementTransactionIdAfterUpdate = false;


  //-------------------------------------------------------------
  // Model properties
  //-------------------------------------------------------------

  /**
   * @see qcl_data_model_PropertyBehavior
   * @var array
   * @todo not used, remove?
   */
//  private $properties = array(
//    "id" => array(
//      "check"    => "integer",
//      "nullable" => false,
//    ),
//    "created" => array(
//      "check"    => "qcl_data_db_Timestamp",
//      "nullable" => true
//    ),
//    "modified" => array(
//      "check"    => "qcl_data_db_Timestamp",
//      "nullable" => true
//    )
//  );

  /**
   * dialog.Form - compatible form data for the editable properties
   * of this model.
   * @todo add documentation
   * @var array
   */
  protected $formData = array();

  //-------------------------------------------------------------
  // Initialization
  //-------------------------------------------------------------

  /**
   * Constructor
   * @param qcl_data_datasource_DbModel|null $datasourceModel Optional datasource
   *  model which provides shared resources for several models that belong
   *  to it.
   * @throws LogicException
   */
  function __construct( $datasourceModel=null )
  {

    if ( get_class( $this ) == __CLASS__ )
    {
      throw new LogicException("Class is abstract and must be extended.");
    }

    parent::__construct();

    /*
     * set datasource model
     */
    $this->setDatasourceModel( $datasourceModel );
  }

  /**
   * Initializes the model and the behaviors.
   * @return boolean True if initialization has to be done in the subclass,
   * false if object was already initialized earlier.
   */
  public function init()
  {
    if ( parent::init() )
    {
      $this->getQueryBehavior()->init();
      $this->getRelationBehavior()->init();
      return true;
    }
    return false;
  }

  //-----------------------------------------------------------------------
  // Internal methods
  //-----------------------------------------------------------------------

  /**
   * Converts an argument to an array. If the argument is null, return
   * an empty array. If the argument is not an array, return an arra with
   * the argument as single element. If the argument is an array already,
   * return this array.
   *
   * @param mixed $arg
   * @return array
   */
  protected function argToArray( $arg )
  {
    if( is_array( $arg ) )
    {
      return $arg;
    }
    elseif ( is_null( $arg ) )
    {
      return array();
    }
    else
    {
      return array( $arg );
    }
  }

  //-------------------------------------------------------------
  // Getters & setters
  //-------------------------------------------------------------

  /**
   * Generic setter for model properties.
   * @see qcl_core_Object#set()
   * @param array|string $first
   * @param null $second
   * @param bool $checkLoaded
   * @return qcl_data_model_db_ActiveRecord
   */
  public function set( $first, $second= null, $checkLoaded=true )
  {
    if( $checkLoaded) $this->checkLoaded();
    parent::set( $first, $second );
    return $this;
  }

  /**
   * Getter for modification date
   * @return qcl_data_db_Timestamp
   */
  public function getModified()
  {
    $this->checkLoaded();
    return $this->_get("modified");
  }

  /**
   * Getter for creation date
   * @return qcl_data_db_Timestamp
   */
  public function getCreated()
  {
    $this->checkLoaded();
    return $this->_get("created");
  }

  /**
   * Getter for datasource model
   * @return qcl_data_datasource_DbModel
   */
  public function datasourceModel()
  {
    return $this->datasourceModel;
  }

  /**
   * Setter for datasource model
   * @param qcl_data_datasource_DbModel $datasourceModel
   * FIXME use interface!
   * @throws InvalidArgumentException
   */
  protected function setDatasourceModel( $datasourceModel )
  {
    if ( is_null( $datasourceModel ) )
    {
      $this->datasourceModel = $datasourceModel;
    }
    elseif ( $datasourceModel instanceof qcl_data_datasource_DbModel )
    {
      $datasourceModel->id(); // makes sure active record is loaded
      $this->datasourceModel = $datasourceModel;
    }
    else
    {
      throw new InvalidArgumentException("Datasource model must be null or instance of qcl_data_datasource_DbModel");
    }
  }

  /**
   * Gets the values of all properties as an associative
   * array, keys being the property names. Overridden to check if
   * record is loaded.
   * 
   * @param array $options 
   * 		An associative array containing one or more of the following keys:
   * 			include => Array of property names to include
   * 			exclude	=> Array of property names to exclude
   * @return array
   */
  public function data( $options=null )
  {
    $this->checkLoaded();
    return parent::data( $options );
  }

  /**
   * Returns the data that was originally loaded from the database. Can be used
   * to see whether data has changed in the meantime.
   * @return array
   */
  public function originalData()
  {
    return $this->_data;
  }

  /**
   * Gets the data of the currently loaded record as a stdClass object
   * so you can use $record->foo instead of $record['foo']
   * @return stdClass
   */
  public function dataObject()
  {
    $this->checkLoaded();
    return (object) $this->data();
  }


  /**
   * Return dialog.Form - compatible  form data for editable properties of
   * this model.
   * @todo add documentation
   * @return array
   */
  public function formData()
  {
    return $this->formData;
  }
  
  //-------------------------------------------------------------
  // UI-related getters
  //-------------------------------------------------------------

  /**
   * Returns the text for a 'label' that is used in a visual widget
   * to represent the model record. Must be implemented if used.
   * @throws qcl_core_NotImplementedException
   * @todo make abstract?
   * @return string
   */
  public function label()
  {
  	throw new qcl_core_NotImplementedException(__METHOD__);
  }

  /**
   * Returns the model value that is used in a visual widget
   * to represent the model record. Defaults to returning the
   * id property.
   * @return mixed
   */
	public function model()
  {
  	return $this->id(); 
  }
  
  /**
   * Returns the icon path that is used in a visual widget
   * to represent the model record. Defaults to returning
   * a NULL value
   * @return string
   */  
	public function icon()
  {
  	return null; 
  }

  /**
   * Returns the data model of an UI element such as a ListItem or tree node
   * @param string $modelKey
   *    The name of the key that should have the model value. Defaults to "model".
   * @param string $iconKey
   *    The name of the key that should have the icon path. Defaults to "icon".
   * @param string $labelKey
   * @internal param int|null $iconSize The pixel size of the icon, if any. Defaults to null (= no icon)*    The pixel size of the icon, if any. Defaults to null (= no icon)
   * @return array
   */
  public function uiElementModel($modelKey="model", $iconKey="icon", $labelKey="label")
  {
  	return array(
  		$modelKey	=> $this->model(),
  		$labelKey	=> $this->label(),
  		$iconKey	=> $this->icon()
  	);
  }

  //-------------------------------------------------------------
  // Numeric and Named Id
  //-------------------------------------------------------------

  /**
   * Gets the id of the current record. Raises error if no record
   * is loaded.
   * @return int
   */
  public function getId()
  {
    $this->checkLoaded();
    $id = (int) $this->_get("id");
    return $id;
  }

  /**
   * Alias of getId()
   * return int
   */
  public function id()
  {
    return $this->getId();
  }

  //-------------------------------------------------------------
  // Query behavior
  //-------------------------------------------------------------

  /**
   * Returns the query behavior. Must be implemented by the subclass.
   * @return qcl_data_model_db_QueryBehavior
   */
  abstract public function getQueryBehavior();

  /**
   * Add indexes to the backend database.
   * @see qcl_data_model_IQueryBehavior::addIndexes()
   * @param array $indexes
   * @return void
   */
  public function addIndexes( $indexes )
  {
    $this->getQueryBehavior()->addIndexes( $indexes );
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
  public function addPrimaryIndexProperties($properties) {
      $this->getQueryBehavior()->addPrimaryIndexProperties($properties);
      return $this;
  }

  //-------------------------------------------------------------
  // Record Retrieval (load methods)
  //-------------------------------------------------------------

  /**
   * Sets the properties from a query result
   * @param array $result
   * @return void
   */
  protected function setFromQuery( $result )
  {
    qcl_assert_array( $result );
    $propBehavior = $this->getPropertyBehavior();
    foreach( $result as $property => $value )
    {
      if ( $this->hasProperty( $property ) )
      {
        $this->set( $property, $propBehavior->typecast( $property, $value ), false );
      }
      else
      {
        if( $this->getLogger()->isFilterEnabled( QCL_LOG_PROPERTIES) ) $this->log( sprintf(
          "Model class '%s' does not have a property '%s'. Please check the database and remove column '%s' if necessary.",
          $this->className(), $property, $property
        ), QCL_LOG_PROPERTIES );
      }
    }
  }

  /**
   * Loads a model record identified by id. Does not return anything.
   * Throws an exception if no model data could be found. Returns
   * itself in order to allow changed method calling ($model->load(1)->delete();
   *
   * @param int $id
   * @return qcl_data_model_db_ActiveRecord
   * @throws qcl_data_model_RecordNotFoundException
   */
  public function load( $id )
  {
  	
  	qcl_assert_integer( $id, "Invalid parameter. Must be integer.");
  	
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * select and fetch row with corresponding id
     */
    $this->getQueryBehavior()->select( new qcl_data_db_Query( array(
      'select' => $this->properties(),
      'where'  => array( "id" => $id )
    ) ) );
    $result = $this->getQueryBehavior()->fetch();

    /*
     * typecast and set result
     */
    if ( $result )
    {
      /*
       * Set all the properties
       */
      $this->setFromQuery( $result );

      /*
       * Mark that we're loaded
       */
      $this->_loaded = true;

      /*
       * save a copy so we can check whether properties have changed
       */
      $this->_data = $this->data();

      /*
       * return myself
       */
      return $this;
    }
    throw new qcl_data_model_RecordNotFoundException( sprintf(
      "Model record [%s #%s] does not exist",
      $this->className(), $id
    ) );
  }

  /**
   * Return true if record has been loaded, false if not.
   * @return bool
   */
  public function isLoaded()
  {
    return $this->_loaded;
  }


  /**
   * Checks if active record is loaded and throws a qcl_data_model_NoRecordLoadedException if not.
   * @return void
   * @throws qcl_data_model_NoRecordLoadedException
   */
  public function checkLoaded()
  {
    if ( ! $this->_loaded )
    {
      throw new qcl_data_model_NoRecordLoadedException(sprintf(
      	"Model %s is not loaded yet.", $this->className()
      ));
    }
  }

  /**
   * If query is successful, load the first row of the result set into the
   * model. If not, throw an exception. Returns
   * itself in order to allow changed method calling, such as:
   * $model->loadWhere( array( 'foo' => "bar" )
   *  ->set( array( 'foo' => "baz" )
   *  ->save();
   *
   * @throws qcl_data_model_RecordNotFoundException
   * @param qcl_data_db_Query|array $query
   * @return qcl_data_model_db_ActiveRecord
   */
  public function loadWhere( $query )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * select and fetch first row that matches the query
     */
    $query = $this->getQueryBehavior()->selectWhere( $query );

    if ( $query->getRowCount() > 0 )
    {
      $result = $this->getQueryBehavior()->fetch();

      /*
       * Set all the properties
       */
      $this->setFromQuery( $result );

      /*
       * mark that a record is loaded
       */
      $this->_loaded = true;

      /*
       * save a copy so we can check whether properties have changed
       */
      $this->_data = $this->data();
        

      /*
       * return the number of rows found
       */
      return $query->getRowCount();
    }
    else
    {
      throw new qcl_data_model_RecordNotFoundException( sprintf(
        "No model instance [%s] could be found for the given query",
        $this->className()
      ) );
    }
  }

  //-----------------------------------------------------------------------
  // Select model records for iteration
  //-----------------------------------------------------------------------

  /**
   * find model records that match the given query object
   * for iteration
   * @param qcl_data_db_Query $query
   * @return int Number of found records
   */
  public function find( qcl_data_db_Query $query )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * execute query
     */
    return $this->getQueryBehavior()->select( $query );
  }

  /**
   * find model records that match the given where array data
   * for iteration.
   * @param array $where
   *    Array containing the where data
   * @param mixed|null $orderBy
   *    Optional data for the ordering of the retrieved records
   * @return qcl_data_db_Query Result query object
   */
  public function findWhere( $where, $orderBy=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * execute query
     */
    $this->lastQuery = $this->getQueryBehavior()->selectWhere( $where, $orderBy );
    return $this->lastQuery;
  }

  /**
   * Find all model records for iteration. The order of the records
   * is not specified.
   * @return qcl_data_db_Query The query object to use for iteration
   */
  public function findAll()
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * run query
     */
    $this->lastQuery = new qcl_data_db_Query( array(
      'select'  => "*"
    ) );
    $this->getQueryBehavior()->select( $this->lastQuery );
    return $this->lastQuery;
  }

  /**
   * Find all model records for iteration, ordered by a property
   * @param null $property
   * @return qcl_data_db_Query The query object to use for iteration
   */
  public function findAllOrderBy( $property=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * run query
     */
    $this->lastQuery = new qcl_data_db_Query( array(
      'select'  => "*",
      'orderBy' => $property
    ) );
    $this->getQueryBehavior()->select( $this->lastQuery );
    return $this->lastQuery;
  }

  /**
   * Find the models instances that are linked with the target model
   * for iteration.
   *
   * @param qcl_data_model_AbstractActiveRecord $targetModel
   *    Target model
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_AbstractActiveRecord[] $dependencies
   *    Optional model instance or array of model instances on which the link
   *    between this model and the target model depends.
   * @param string|array $orderBy
   *    Optional order by argument
   * @return qcl_data_db_Query
   * @throws qcl_data_model_RecordNotFoundException
   */
  public function findLinked( qcl_data_model_AbstractActiveRecord $targetModel, $dependencies=null, $orderBy=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * dependencies?
     */
    $dependencies = $this->argToArray( $dependencies );

    /*
     * find linked ids
     */
    $ids = $this->getRelationBehavior()->linkedModelIds( $targetModel, $dependencies );
    if ( count( $ids ) )
    {
      $this->lastQuery = $this->getQueryBehavior()->selectIds( $ids, $orderBy );
      if( $this->foundNothing() ){
        throw new qcl_data_model_RecordNotFoundException( sprintf(
          "Problem with link data between [%s] and %s",
          $this->className(), $targetModel
        ) );
      }
      return $this->lastQuery;
    }
    else
    {
      throw new qcl_data_model_RecordNotFoundException( sprintf(
        "No record of [%s] is linked to %s",
        $this->className(), $targetModel
      ) );
    }
  }
  
  /**
   * Find the models instances that are NOT linked with the target model
   * for iteration. Current implementation is ineffective and very expensive 
   * if you have a large number of records, because it simply creates a diff
   * between ALL ids and the linked ones. 
   *
   * @param qcl_data_model_AbstractActiveRecord $targetModel
   *    Target model
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_AbstractActiveRecord[] $dependencies
   *    Optional model instance or array of model instances on which the link
   *    between this model and the target model depends.
   * @param string|array $orderBy
   *    Optional order by argument
   * @return qcl_data_db_Query
   * @throws qcl_data_model_RecordNotFoundException
   * @todo rewrite more efficiently
   */
  public function findNotLinked( qcl_data_model_AbstractActiveRecord $targetModel, $dependencies=null, $orderBy=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * dependencies?
     */
    $dependencies = $this->argToArray( $dependencies );

    /*
     * all ids 
     */
    $allIds = $this->getQueryBehavior()->fetchValues("id");
    
    /*
     * find linked ids
     */
    $ids = array_diff( $allIds, $this->getRelationBehavior()->linkedModelIds( $targetModel, $dependencies ) );
    if ( count( $ids ) )
    {
      $this->lastQuery = $this->getQueryBehavior()->selectIds( $ids, $orderBy );
      if( $this->foundNothing() ){
        throw new qcl_data_model_RecordNotFoundException( sprintf(
          "Problem with link data between [%s] and %s",
          $this->className(), $targetModel
        ) );
      }
      return $this->lastQuery;
    }
    else
    {
      throw new qcl_data_model_RecordNotFoundException( sprintf(
        "No record of [%s] exists that is not linked to %s",
        $this->className(), $targetModel
      ) );
    }
  }

  /**
   * Find the models instances that are linked with the target model,
   * having no dependency to the model instances that are given as
   * second argument and after. This typically means that the join
   * table contains a NULL value in the foreign key columns of these
   * models in the join table.
   *
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_db_ActiveRecord $targetModel
   *    Target model
   * @param qcl_data_model_db_ActiveRecord[]|qcl_data_model_AbstractActiveRecord $notDependencies
   * @param string|array|null $orderBy
   *    Optional order by argument
   * @throws qcl_data_model_RecordNotFoundException
   * @throws InvalidArgumentException
   * @return qcl_data_db_Query
   */
  public function findLinkedNotDepends( qcl_data_model_AbstractActiveRecord $targetModel, $notDependencies, $orderBy=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * models which shouldn't be dependencies
     */
    $notDependencies = $this->argToArray( $notDependencies );

    if( count( $notDependencies ) == 0 )
    {
      throw new InvalidArgumentException("You must provide at least one model as second argument.");
    }

    $relBehavior   = $this->getRelationBehavior();
    $relation      = $relBehavior->getRelationNameForModel( $targetModel );
    $foreignKey    = $relBehavior->getForeignKey( $relation );

    if ( $relBehavior->getRelationType( $relation ) != QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY )
    {
      throw new InvalidArgumentException("findLinkedNotDepends() supports only n:m relations.");
    }

    /*
     * find linked ids which are not dependent on the
     * given models
     */
    $where = array(
      $targetModel->foreignKey() => $targetModel->id()
    );

    foreach( $notDependencies as $model )
    {
      $where[ $model->foreignKey() ] = null;
    }

    $ids = $relBehavior
      ->getJoinModel( $relation )
      ->getQueryBehavior()
      ->fetchValues( $foreignKey, $where );

    if ( ! count( $ids ) )
    {
      throw new qcl_data_model_RecordNotFoundException("No linked records found.");
    }

    $query = $this
      ->getQueryBehavior()
      ->selectIds( $ids, $orderBy );

    return $query;
  }

  /**
   * If the last query has found more then one record, get the first or next one.
   * If not, or the end of the found records has been reached, return null.
   * @param qcl_data_db_Query|null $query If given, fetch the records
   *   that have been selected using the given query. Otherwise retrieve
   *   the result of the last query.
   * @throws InvalidArgumentException
   * @return array|null The raw data from the record model. To get typecasted
   *  data with translated key names, use ::data()
   */
  public function loadNext( $query= null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * check argument
     */
    if ( $query === null )
    {
      $query = $this->lastQuery;
    }
    else if ( ! $query instanceof qcl_data_db_Query )
    {
      throw new InvalidArgumentException("Argument must be an instance of qcl_data_db_Query");
    }

    /*
     * fetch the next record, set the corrensponding properties if successful
     * and return the result
     */
    $result = $this->getQueryBehavior()->fetch( $query );
    if( $result )
    {
      /*
       * Set all the properties
       */
      $this->setFromQuery( $result );
    }

    /*
     * mark that a record is loaded
     */
    $this->_loaded = true;

    /*
     * save a copy so we can check whether properties have changed
     */
    $this->_data = $this->data();     

    /*
     * return the record
     */
    return $result;
  }

  /**
   * Returns the result of the last query without modifying the
   * active record.
   * @return array Array of arrays
   */
  public function fetchAll()
  {
    try
    {
      $id = $this->id();
    }
    catch ( qcl_data_model_NoRecordLoadedException $e )
    {
      // ignore error, this should also work without a loaded record
      $id = null;
    }

    $result = array();

    /*
     * fetch the complete data
     */
    while( $this->loadNext() )
    {
      $result[] = $this->data();
    }

    /*
     * reload model
     */
    if ( $id )
    {
      $this->load( $id );
    }

    return $result;
  }


  //-------------------------------------------------------------
  // Data creation and manipulation
  //-------------------------------------------------------------

  /**
   * Creates a new model record, optionally, with preconfigured data.
   * @param array|null Optional map of properties to set
   * @return int Id of the record
   */
  public function create( $data=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * mark that record is loaded
     */
    $this->_loaded = true;

    /*
     * setting initial values
     */
    $this->getPropertyBehavior()->initPropertyValues();
    $this->set("created", $this->getCurrentTimestamp() );

    if( is_array( $data ) )
    {
      $this->set( $data );
    }

    /*
     * inserting values
     */
    $id = $this->getQueryBehavior()->insertRow( $this->data() );

    /*
     * reload values, in case the database has changed something
     */
    $this->load( $id );

    /*
     * log message
     */
    $this->log( sprintf( "Created new model record '%s'.", $this ), QCL_LOG_MODEL );

    /*
     * increment transaction id since data has changed
     */
    $this->incrementTransactionId();

    /*
     * fire event
     */
    $this->fireDataEvent("change", array(
      'start' => $id,
      'end'   => $id,
      'type'  => "add",
      'items' => array( $this->data() )
    ));
    $this->fireEvent("changeLength");

    /*
     * return the id
     */
    return $id;
  }

  /**
   * Save the model properties to the database.
   * @return boolean
   */
  public function save()
  {
    $this->checkLoaded();

    $oldData = $this->_data;
    $newData = $this->data();

    $data = array(
      'id'  => $newData['id']
    );

    /*
     * increment transaction id since data has changed
     */
    $this->incrementTransactionId();

    foreach( $newData as $property => $value )
    {
      if( $oldData[$property] !== $value )
      {
        /*
         * store changed data for commit into the database
         */
        $data[$property] = $value;
      }
    }

    $success = $this->getQueryBehavior()->update( $data );

    /*
     * fire event for changed properties
     */
    foreach( $data as $property => $value )
    {
      $this->fireDataEvent("changeBubble", array(
        'value' => $value,
        'name'  => $property,
        'old'  => $oldData[$property]
      ) );
    }

    /*
     * reload data in case there were side effects
     */
    $this->load($newData['id']);

    return $success;
  }

  /**
   * Returns the current timestamp. Override if database has a different time
   * @return qcl_data_db_Timestamp
   */
  protected function getCurrentTimestamp()
  {
    return new qcl_data_db_Timestamp("now");
  }

  /**
   * Deletes the model instance data from the database. Does not delete the
   * active record object. Also deletes references to this model that exist
   * through the previous linking of models.
   *
   * @return boolean
   */
  public function delete()
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * check if we have a loaded record
     */
    $this->checkLoaded();    
    $id = $this->getId();

    $this->log( "Unlinking all linked records for $this ...", QCL_LOG_MODEL );

    /*
     * unlink all model records and delete dependent ones
     */
    $relationBehavior = $this->getRelationBehavior();
    foreach( $relationBehavior->relations() as $relation )
    {
      $targetModel = $relationBehavior->getTargetModel( $relation );
      $isDependent = $relationBehavior->isDependentModel( $targetModel );

      $this->log( sprintf(
        "    ... for relation '%s':%s target model '%s'",
        $relation,
        $isDependent ? " dependent":"",
        $targetModel->className()
      ), QCL_LOG_MODEL );

      $relationBehavior->unlinkAll( $targetModel, false, $isDependent );
    }

    /*
     * increment transaciton id since data has changed
     */
    $this->incrementTransactionId();

    /*
     * fire event
     */
    $this->fireDataEvent("change", array(
      'start' => $id,
      'end'   => $id,
      'type'  => "remove",
      'items' => array( $id )
    ));
    $this->fireEvent("changeLength");

    /*
     * delete the model data
     */
    $this->log( sprintf( "Deleting record data for %s ...", $this ), QCL_LOG_MODEL );
    $succes = $this->getQueryBehavior()->deleteRow( $id );
    
    /*
     * set flags
     */
    $this->_isDeleted = true;

    return $succes;
  }
  
  /**
   * Returns true if the current record has just been deleted
   * @return bool
   */
  public  function isDeleted()
  {
  	return $this->_isDeleted;
  }

  /**
   * Deletes the model records that match the 'where' data. This does not
   * delete linked model data. Load() each record to be deleted and then
   * execute delete() in order to delete relational dat.
   *
   * @param array $where
   * @return int number of affected rows
   */
  public function deleteWhere( $where )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * execute query
     */
    $rowCount =  $this->getQueryBehavior()->deleteWhere( $where );

    /*
     * increment transaciton id since data has changed
     */
    $this->incrementTransactionId();

    // FIXME implement change event

    return $rowCount;
  }

  /**
   * Deletes all records from the database. Also deletes references
   * to other model instances. Don't use this method on really large
   * datasets.
   *
   * @return number of affected rows
   */
  public function deleteAll()
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    $this->log( "Unlinking all records for model $this", QCL_LOG_MODEL );

    /*
     * initialize all dependent models so that he dependencies are set up
     * before deleting them
     */
    $relationBehavior = $this->getRelationBehavior();
    foreach ( $relationBehavior->relations() as $relation )
    {
      $relationBehavior->getTargetModel( $relation )->init();
    }

    /*
     * now unlink them
     */
    foreach ( $relationBehavior->relations() as $relation )
    {
      $targetModel = $relationBehavior->getTargetModel( $relation );
      $isDependent = $relationBehavior->isDependentModel( $targetModel );

      /*
       * unlink all model records and delete dependend ones
       */
      $this->log( sprintf(
        "    ... for relation '%s':%s target model %s",
        $relation,
        $isDependent ? " dependent":"",
        $targetModel
      ), QCL_LOG_MODEL );
      $relationBehavior->unlinkAll( $targetModel, true,  $isDependent );
    }

    /*
     * delete model data
     */
    $this->log( sprintf(
      "Deleting all records for model %s", $this
    ), QCL_LOG_MODEL );

    $this->getQueryBehavior()->deleteAll();
    qcl_data_model_db_ActiveRecord::resetBehaviors();

    /*
     * increment transaciton id since data has changed
     */
    $this->incrementTransactionId();

    // FIXME change event
  }

  /**
   * Updates the given properties with new values of those model records
   * that match the 'where' data.
   * @param array $data Map of key - value pairs of the properties to be updated.
   * @param array $where
   * @return int number of affected rows
   */
  public function updateWhere( $data, $where )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * execute query
     */
    $rowCount =  $this->getQueryBehavior()->updateWhere( $data, $where );

    /*
     * increment transaciton id since data has changed
     */
    $this->incrementTransactionId();

    // FIXME need to fire changeBubble event!

    return $rowCount;
  }

  //-----------------------------------------------------------------------
  // Transaction id
  //-----------------------------------------------------------------------

  /**
   * Returns the model that keeps transactions ids for other models
   * @return qcl_data_model_db_TransactionModel
   * @todo should be abstact method
   */
  abstract public function getTransactionModel();

  /**
   * Use transaction model to get transaction id of this model.
   * @return int The current transaction id or 0 if transaction
   * ids are disabled
   */
  public function getTransactionId()
  {
    if ( $this->incrementTransactionIdAfterUpdate )
    {
      return $this->getTransactionModel()->getTransactionIdFor( $this );
    }
    return 0;
  }

  /**
   * Use transaction model to increment transaction id of this model
   * @return int The current transaction id or 0 if transaction ids
   * are disabled.
   */
  public function incrementTransactionId()
  {
    if ( $this->incrementTransactionIdAfterUpdate )
    {
      return $this->getTransactionModel()->incrementTransactionIdFor( $this );
    }
    return 0;
  }

  /**
   * Reset the transaction id
   * @return void
   */
  public function resetTransactionId()
  {
    $this->getTransactionModel()->resetTransactionIdFor( $this );
  }

  //-----------------------------------------------------------------------
  // Information on records/queries
  //-----------------------------------------------------------------------

  /**
   * Number of rows affected/selected by the last statement
   * @return int
   */
  public function rowCount()
  {
    if ( $this->lastQuery )
    {
      return $this->lastQuery->getRowCount();
    }
    throw new LogicException("No query exists for which to count rows.");
  }

  /**
   * Returns true if the last query didn't find any records
   * @return boolean
   */
  public function foundNothing()
  {
    return $this->rowCount() == 0;
  }

  /**
   * Whether the last query was successful
   * @return boolean
   */
  public function foundSomething()
  {
    return $this->rowCount() > 0;
  }

  /**
   * Returns number of records in the database
   * @return int
   */
  public function countRecords()
  {
    return $this->getQueryBehavior()->countRecords();
  }

  /**
   * Returns the number of records matching the where
   * @param array|qcl_data_db_Query $query Query or where condition
   * @return int
   */
  public function countWhere( $query )
  {
    $this->init();
    return $this->getQueryBehavior()->countWhere( $query );
  }

  //-----------------------------------------------------------------------
  // Model relations (associations )
  //-----------------------------------------------------------------------

  /**
   * Returns the key with which the ids of the model data is
   * referenced in other (foreign) relational tables
   *
   * @return string
   */
  public function foreignKey()
  {
    return $this->foreignKey;
  }

  /**
   * Returns the relation behavior
   * @return qcl_data_model_db_RelationBehavior
   * @todo create interface
   */
  abstract function getRelationBehavior();

  /**
   * Add the definition of relations of this model for use in
   * queries.
   *
   * @param array  $relations
   * @param string|null $definingClass
   *   The class that defines the relations. Usually, the caller passes the __CLASS__ constant.
   *   This is needed to correctly determine the model class names when child classes are involved.
   *   Not optional although the signature suggests this - this is only because of the  moreg
   *   generalized interface.
   * @return void
   */
  public function addRelations( $relations, $definingClass=null )
  {
    qcl_assert_valid_string($definingClass,"The name of the defining class must be passed as second argument.");
    $this->getRelationBehavior()->addRelations( $relations, $definingClass );
  }

  /**
   * Returns true if the managed model has a relation with the given
   * model.
   *
   * @param qcl_data_model_AbstractActiveRecord $model
   * @return bool
   */
  public function hasRelationWithModel( $model )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * call behavior method do do the actual work
     */
    try
    {
      return $this->getRelationBehavior()->hasRelationWithModel( $model );
    }
    catch( qcl_data_model_Exception $e )
    {
      return false;
    }
  }

  /**
   * Returns the number of links of this model record with the given
   * model.
   *
   * @param qcl_data_model_AbstractActiveRecord $model
   *  Target model to check
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_AbstractActiveRecord[] $dependencies
   *    Optional model instance or array of model instances on which the link
   *    between this model and the target model depends.
   * @return bool
   */
  public function countLinksWithModel( $model, $dependencies=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * dependencies
     */
    $dependencies = $this->argToArray( $dependencies );

    /*
     * call behavior method do do the actual work
     */
    try
    {
      return $this->getRelationBehavior()->countLinksWithModel( $model, $dependencies );
    }
    catch( qcl_data_model_Exception $e )
    {
      return false;
    }
  }

  /**
   * Returns true if the managed model has a link with the given
   * model.
   *
   * @param qcl_data_model_AbstractActiveRecord $model
   *  Target model to check
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_AbstractActiveRecord[] $dependencies
   *    Optional model instance or array of model instances on which the link
   *    between this model and the target model depends.
   * @return bool
   */
  public function hasLinkWithModel( $model, $dependencies=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * dependencies
     */
    $dependencies = $this->argToArray( $dependencies );

    /*
     * call behavior method do do the actual work
     */
    try
    {
      return $this->getRelationBehavior()->hasLinkWithModel( $model, $dependencies );
    }
    catch( qcl_data_model_Exception $e )
    {
      return false;
    }
  }

  /**
   * Creates a link between two associated models.
   * @param qcl_data_model_AbstractActiveRecord $targetModel
   *    Target model instance
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_AbstractActiveRecord[] $dependencies
   *    Optional model instance or array of model instances on which the link
   *    between this model and the target model depends.
   * @return qcl_data_model_AbstractActiveRecord
   * @throws qcl_data_model_RecordExistsException If link already exists
   */
  public function linkModel( $targetModel, $dependencies=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * dependencies, if any
     */
    $dependencies = $this->argToArray( $dependencies );

    /*
     * call behavior method do do the actual work
     */
    $this->getRelationBehavior()->linkModel( $targetModel, $dependencies );
    return $this;
  }

  /**
   * Checks if this model and the given target model are linked.
   *
   * @param qcl_data_model_AbstractActiveRecord $targetModel
   *    Target model instance
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_AbstractActiveRecord[] $dependencies
   *    Optional model instance or array of model instances on which the link
   *    between this model and the target model depends.
   * @return bool
   */
  public function islinkedModel( $targetModel, $dependencies=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * dependencies, if any
     */
    $dependencies = $this->argToArray( $dependencies );

    /*
     * call behavior method do do the actual work
     */
    return $this->getRelationBehavior()->isLinkedModel( $targetModel, $dependencies );
  }

  /**
   * Returns the ids of all model records linked to the target model.
   *
   * @param qcl_data_model_AbstractActiveRecord $targetModel
   *    Target model instance
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_AbstractActiveRecord[] $dependencies
   *    Optional model instance or array of model instances on which the link
   *    between this model and the target model depends.
   * @return array
   */
  public function linkedModelIds( $targetModel, $dependencies=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * dependencies, if any
     */
    $dependencies = $this->argToArray( $dependencies );

    /*
     * call behavior method do do the actual work
     */
    return $this->getRelationBehavior()->linkedModelIds( $targetModel, $dependencies );
  }

  /**
   * Unlinks the given target model from this model.
   *
   * @param qcl_data_model_AbstractActiveRecord $targetModel
   *    Target model instance
   * @param qcl_data_model_AbstractActiveRecord|qcl_data_model_AbstractActiveRecord[] $dependencies
   *    Optional model instance or array of model instances on which the link
   *    between this model and the target model depends.
   * @return void
   * @throws qcl_data_model_Exception if models are not linked
   */
  public function unlinkModel( $targetModel, $dependencies=null )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * dependencies, if any
     */
    $dependencies = $this->argToArray( $dependencies );

    /*
     * call behavior method do do the actual work
     */
    $this->getRelationBehavior()->unlinkModel( $targetModel, $dependencies );
  }

  /**
   * Unlinks all linked records of the given target model from
   * the currently loaded model record.
   *
   * @param qcl_data_model_AbstractActiveRecord $targetModel
   *    Target model instance. Does not need to be loaded.
   * @param bool $allLinks 
   * 		If true, remove all links, i.e. not only of the currently loaded
   * 	  model instance, but all other instances as well.
   * @param bool $delete 
   * 		If true, delete all linked records in addition
   *    to unlinking them. This is needed for dependend models.
   * @return bool
   */
  public function unlinkAll( $targetModel, $allLinks = false, $delete = false )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * call behavior method do do the actual work
     */
    return $this->getRelationBehavior()->unlinkAll( $targetModel, $allLinks, $delete );
  }

  //-----------------------------------------------------------------------
  // Import / export
  //-----------------------------------------------------------------------

  /**
   * Imports data, using an importer class that needs to subclass
   * qcl_data_model_AbstractImporter
   *
   * @param qcl_data_model_AbstractImporter $importer
   * @return void
   */
  public function import( qcl_data_model_AbstractImporter $importer )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * call importer method do do the actual work
     */
    $importer->import( $this );
  }

  /**
   * Exports data, using an exporter class that needs to subclass
   * qcl_data_model_AbstractExporter. Returns data in the format
   * that the exporter provides
   *
   * @param qcl_data_model_AbstractExporter $exporter
   * @return mixed The exported data
   */
  public function export( qcl_data_model_AbstractExporter $exporter )
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * call importer method do do the actual work
     */
    return $exporter->export( $this );
  }

  //-------------------------------------------------------------
  // Cleanup
  //-------------------------------------------------------------
  
  /**
   * This method loads each model record and triggers the expiration
   * feature on each record, if enabled. Since this is potentially 
   * computation-expensive, call it only in a startup or shutdown method 
   * that is called only once per user and session. 
   */
  public function cleanup()
  {
  	$this->findAll();
  	while ( $this->loadNext() )
  	{
  		if ( $this->checkExpiration() )
  		{
  			$this->delete();
  		}
  	}
  } 
  
  
  /**
   * Method called in the load() method to check whether the record auto-expires.
   * By default, checks the expiresAfter property and removes the record if
   * the record hasn't been touched for the number of seconds stored in the
   * property (if not NULL). Returns true if record is expired and should be deleted,
   * otherwise false.
   * @return boolean
   */
  protected function checkExpiration()
  {
  	if ( $this->expiresAfter !== null )
  	{
  		$m  = $this->getModified();
  		if ( $m instanceof  DateTime )
  		{
	  		$d 	= $m->diff( new DateTime() );
	  		$s 	= $d->s + 
				  		$d->i * 60 + 
				  		$d->h * 3600 +
				  		$d->d * 86400 +
				  		$d->m * 2678400;
				
	  		return ( $s > $this->expiresAfter );
  		}
  	}
  	return false;
  }

  /**
   * Resets the internal cache used by the behaviors to avoid unneccessary
   * database lookups. Call this method statically at the beginning of your
   * code as long as your model definitions change.
   *
   * @return void
   */
  public static function resetBehaviors()
  {
    $class = get_called_class();
    $_this = new $class;
    $_this->getPropertyBehavior()->reset();
    $_this->getQueryBehavior()->reset();
    $_this->getRelationBehavior()->reset();
  }

  /**
   * Destroys all data connected to the model, such as tables etc.
   */
  public function destroy()
  {
    /*
     * initialize model and behaviors
     */
    $this->init();

    /*
     * destroy all jointables models, too. This can leave the database in a
     * broken state, make sure to destroy the model joined by the join table,
     * too.
     */
    $relationBehavior = $this->getRelationBehavior();
    foreach( $relationBehavior->relations() as $relation )
    {
      if ( $relationBehavior->getRelationType( $relation ) == QCL_RELATIONS_HAS_AND_BELONGS_TO_MANY )
      {
        $joinTableName = $relationBehavior->getJoinTableName( $relation );
        $joinTable = new qcl_data_db_Table( $joinTableName, $this->getQueryBehavior()->getAdapter() );
        if ( $joinTable->exists() )
        {
          $joinModel = $relationBehavior->getJoinModel( $relation );
          $joinModel->destroy();
        }
      }
    }
    $this->getQueryBehavior()->destroy();
    qcl_data_model_db_ActiveRecord::resetBehaviors();
  }

  //-------------------------------------------------------------
  // Conversions
  //-------------------------------------------------------------

  /**
   * Return a string representation of the model
   */
  public function __toString()
  {
    $id = $this->_loaded ? $this->id() : "--";
    $ds = $this->datasourceModel();
    return sprintf(
      "[%s%s/%s]",
      ( $ds ? $ds->namedId() . "/" : "" ) ,
      $this->className(), $id
    );
  }
}
