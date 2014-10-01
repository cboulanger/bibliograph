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

qcl_import( "qcl_core_PropertyBehavior" );

/**
 * Property behavior modelled on the qooxdoo property definition syntax.
 *
 * Define the properties the private $properties class member variable:
 *
 * <pre>
 * private $properties = array(
 *   "foo" => array(
 *     "check"    => "array",
 *     "init"     => "foo",
 *     "apply"    => "_applyFoo",
 *     "nullable" => true,
 *     "event"    => "changeFoo"
 *    ),
 *    "bar"  => array(
 *      "check"     => "integer",
 *      "init"      => 1,
 *      "nullable"  => false
 *    )
 * );
 * </pre>
 *
 * and add them to the property definition by using the "addProperties()"
 * method in the constructor:
 *
 * <pre>
 * function __construct()
 * {
 *   // properties must be declared BEFORE calling the parent constructor!
 *   $this->addProperties( $this->properties );
 *
 *   parent::__construct();
 * }
 * </pre>
 *
 * Since you use a private member, this works event when a "$properties"
 * property exists in the parent class.
 *
 * You can also *refine* properties by overriding them if you call the parent
 * constructor first and then add the properties. However, since the parent
 * constructor calls the init() method, make sure the code called in init()
 * does not rely on the presence of all the properties.
 */
class qcl_data_model_PropertyBehavior
  extends qcl_core_PropertyBehavior
{

  /**
   * The native variable types
   * @var array
   */
  protected static $native_types = array("boolean", "integer", "double", "string", "array", "object","resource","NULL");

  /**
   * The names of the core properties that ActiveRecord and NamedActiveRecord models
   * define
   * @var array
   */
  protected static $core_properties = array( "id", "namedId", "created", "modified" );

  /**
   * Property definitions of this class and all parent classes
   */
  protected $properties = array();
  
  /**
   * The stored values for the managed properties
   * @var array
   */
  protected $data = array();

  /**
   * If the behavior has been initialized
   * @var bool
   */
  private $isInitialized = false;


  //-------------------------------------------------------------
  // Initialization
  //-------------------------------------------------------------

  /**
   * Constructor
   * @param qcl_data_model_IModel $model Model affected by this behavior
   * @return qcl_data_model_PropertyBehavior
   */
  function __construct( qcl_data_model_IModel $model )
  {
    parent::__construct( $model );
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



  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------


  /**
   * Set initial values unless the model has been restored from persistent
   * data.
   * @throws qcl_core_PropertyBehaviorException
   * @return void
   */
  public function initPropertyValues()
  {

    foreach( $this->properties as $property => $prop )
    {
      /*
       * skip id column
       */
      if ( $property == "id" or $property == "namedId" )
      {
        $this->properties[$property ][ 'nullable' ] = true; // to prevent an exception
        $this->_set( $property, null );
        continue;
      }

      /*
       * initial value is set
       */
      if ( isset( $prop['init'] )  )
      {
        if( $this->hasLog() ) $this->log( sprintf(
          "Initializing property '%s' with '%s'",
          $property, $prop['init']
        ) );
        $this->set( $property, $prop['init'] );
      }

      /*
       * no initial value, but nullability is set
       */
      elseif ( isset( $prop['nullable'] ) )
      {
        if ( $prop['nullable'] == true )
        {
          if ( $this->hasLog() ) $this->log(  "Initializing property '$property' with NULL" );
          $this->set( $property, null );
        }
        else
        {
          throw new qcl_core_PropertyBehaviorException(
            "Property " . get_class( $this->getModel() ) . "::\${$property} must be nullable or have an init value:"
          );
        }
      }
      /*
       * if no initial value, make property implicitly nullable
       * and defaulting to null
       */
      else
      {
        $this->properties[ $property ][ 'nullable' ] = true;
        $this->properties[ $property ][ 'init' ] = null;
        $this->set( $property, null );
      }
    }
  }


  //-------------------------------------------------------------
  // Getters
  //-------------------------------------------------------------

  /**
   * Getter for managed model
   * @return qcl_data_model_AbstractActiveRecord
   */
  protected function getModel()
  {
    return parent::getObject();
  }

  /**
   * Getter for definition of properties in the managed model
   * @return array
   */
  public function propertyMap()
  {
    $this->getModel()->warn(sprintf("Use %s() only for debugging", __METHOD__ ) );
    return $this->properties;
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------

  /**
   * Checks if property exists and throws an error if not.
   * @param $property
   * @throws qcl_core_PropertyBehaviorException
   * @throws InvalidArgumentException
   * @return bool
   */
  public function check( $property )
  {
    if ( ! $property or ! is_string( $property ) )
    {
      throw new InvalidArgumentException("Invalid property '$property'");
    }
    if ( ! $this->has( $property) )
    {
      if ( in_array( $property, self::$core_properties ) )
      {
         throw new qcl_core_PropertyBehaviorException( sprintf(
          "Class '%s': core model property '%s' is not defined. ".
          "Did you call the parent constructor in the constructor of class '%s'?",
          $this->getModel()->className(), $property, $this->getModel()->className()
        ) );
      }
      //$this->getModel()->debug( $this->_propertyMap() );
      $this->getObject()->warn( $this->getObject()->backtrace() );
      throw new qcl_core_PropertyBehaviorException( sprintf(
        "Class '%s': model property '%s' is not defined.",#
        get_class( $this->getModel() ), $property
      ) );
    }
  }

  /**
   * Implementation of property getter
   * @param $property
   * @return mixed
   */
  public function _get( $property )
  {
    return $this->data[$property];
  }

  /**
   * Implementation for model property setter
   * @param string $property Property name
   * @param mixed $value Property value
   * @throws qcl_core_PropertyBehaviorException
   * @return qcl_data_model_IModel
   */
  public function _set( $property, $value )
  {
    $props    = $this->properties;
    $def      = $props[$property];
    $type     = isset( $def['check'] )    ? $def['check']    : null;
    $nullable = isset( $def['nullable'] ) ? $def['nullable'] : true; // nullable by default
    $apply    = isset( $def['apply'] )    ? $def['apply']    : null;
    $event    = isset( $def['event'] )    ? $def['event']    : null;

    /*
     * check type/nullable
     */
    if ( $nullable and $value === null )
    {
      $fail = false;
    }
    elseif ( in_array( $type, self::$native_types ) )
    {
      if ( $type != gettype( $value ) )
      {
        $fail = true;
        $msg = "Expected type '$type', got '$value' (" . gettype( $value ) . ").";
      }
      else
      {
        $fail = false;
      }
    }
    elseif ( class_exists( $type ) and ! is_a( $value, $type ) )
    {
      $fail = true;
      $msg = "Expected class '$type', got '" . typeof( $value, true ) . "'";
    }

    if ( $fail )
    {
      throw new qcl_core_PropertyBehaviorException( sprintf(
        "Invalid value type for property '%s' of class '%s': %s",
        $property, $this->getModel()->className(), $msg
       ) );
    }

    /*
     * set value
     */
    $old = $this->data[$property];
    $this->data[$property] = $value;

    /*
     * apply method?
     */
    if ( $apply and $value !== $old )
    {
      if ( method_exists( $this->getModel(), $apply ) )
      {
        call_user_func( array( $this->getModel(), $apply ), $value, $old );
      }
      else
      {
        throw new qcl_core_PropertyBehaviorException(
          "Invalid property definition: apply method " .
          get_class( $this->getModel() ) .
          "::$apply() does not exist."
        );
      }
    }

    /*
     * dispatch event
     */
    if ( $event and $value !== $old )
    {
      $this->getModel()->fireDataEvent( $event, $value );
    }

    return $this->object;
  }

  /**
   * Returns the type of the property as provided in the property
   * definition
   * @param $property
   * @return string
   */
  public function type( $property )
  {
    $this->check( $property );
    return $this->properties[$property]['check'];
  }

  /**
   * The names of all the managed properties of this class.
   * @param boolean $ownPropertiesOnly
   * 		If true, return only the properties defined in the class. If false 
   * 		(default), return these properties plus all the inherited properties
   * 		of the parent classes.
   * @return array
   */
  public function names( $ownPropertiesOnly=false )
  {
  	if ( $ownPropertiesOnly )
  	{
  		return $this->getModel()->ownProperties(); 
  	}
  	else 
  	{
  		return array_keys( $this->properties );	
  	}
  }


  /**
   * Adds a property definition. You can refine values in parent classes.
   * @param array $properties
   * @throws LogicException
   * @return array The new property definition
   */
  public function add( $properties )
  {
    //$this->getModel()->debug("Adding " .print_r( $properties,true),__CLASS__,__LINE__);

    /*
     * check validity
     */
    if( ! is_array( $properties ) or ! is_map( $properties ) )
    {
      throw new LogicException( sprintf(
        "Invalid property data in model class '%s'.", $this->getModel()->className()
      ) );
    }

    /*
     * add to property definition array
     */
    foreach ( $properties as $name => $map )
    {
      if( $this->hasLog() ) $this->log( sprintf(
        "Adding property '%s' for model '%s'.", $name, $this->getModel()->className()
      ), QCL_LOG_PROPERTIES );

      if ( ! is_array( $this->properties[ $name ] ) )
      {
        $this->properties[ $name ] = $map;
      }
      else
      {
        foreach( $map as $key => $value )
        {
           $this->properties[ $name ][ $key ] = $value;
        }
      }

      /*
       * optional export property is true by default
       */
      if ( ! isset( $this->properties[ $name ]['export'] ) )
      {
        $this->properties[ $name ]['export'] = true;
      }
    }

    return $this->properties;
  }

  /**
   * Stub to be overridden, the memory-based model doesn't need a primary index.
   * @return void
   */
  public function setupPrimaryIndex(){}

  /**
   * Stub to be overridden, the memory-based model needs no special property 
   * setup.
   * @return void
   */
  public function setupProperties(){}

  /**
   * Cast the given value to the correct php type according to its
   * property type. If the type is a class name, instantiate a new
   * object with the value as the constructor argument. If the
   * 'serialize' flag has been set, unserialize the value into
   * a string before saving it.
   * @param string $propertyName
   * @param mixed $value
   * @throws LogicException
   * @throws qcl_core_PropertyBehaviorException
   * @return mixed
   * @todo This is a mess. Typecasting and unserializing should be
   * dealt with separately.
   */
  public function typecast( $propertyName, $value )
  {
    if( $propertyName == "id" ) return (int) $value;

    $type = $this->type( $propertyName );
    
    //$this->getModel()->debug( "$propertyName=$value($type)");

    /*
     * serialized array values
     */
    if ( $type == "array"
          and isset( $this->properties[$propertyName]['serialize'] )
          and $this->properties[$propertyName]['serialize'] === true)
    {
      /*
       * If the value is NULL in the backend and we have an init
       * value, use the init value instead.
       */
      if( $value === null and isset( $this->properties[$propertyName]['init'] ) )
      {
        $value = $this->properties[$propertyName]['init'];
      }

      /*
       * else, unserialize. This must fail without a default value
       */
      elseif ( is_string( $value ) )
      {
        $value = unserialize( $value );
        if ( ! is_array( $value ) )
        {
          throw new qcl_core_PropertyBehaviorException("Serialized value is not an array!");
        }
      }
      
      /*
       * else, typcasting failed
       */
      elseif ( ! is_array( $value ) )
      {
      	throw new qcl_core_PropertyBehaviorException("Cannot convert saved value into an array!");
      }
    }

    /*
     * FIXME: now we check for a very similar setup!
     */
    elseif ( is_null( $value ) )
    {
      if ( ! isset( $this->properties[$propertyName]['nullable' ] )
          or $this->properties[$propertyName]['nullable' ] === true )
      {
        return null;
      }
      else
      {
        throw new qcl_core_PropertyBehaviorException(
          "Non-nullable property '$propertyName' cannot take a null value"
        );
      }
    }

    /*
     * native types
     */
    elseif ( in_array( $type, self::$native_types ) )
    {
      settype( $value, $type );
    }

    /*
     * objects
     */
    elseif ( class_exists( $type ) )
    {
      if ( is_string( $value) )
      {
        if ( isset( $this->properties[$propertyName]['serialize'] )
            and  $this->properties[$propertyName]['serialize'] === true )
        {
          $value = unserialize( $value );
          if ( ! $value instanceof $type )
          {
            throw new LogicException(
              "Invalid value class. Expected '$type', got '" .
              typeof( $value, true ) . "'."
            );
          }
        }
        else
        {
          $value =  new $type( $value );
        }
      }
    }
    return $value;
  }

  /**
   * Converts into a scalar value (a string, integer, boolean)
   * that can be saved into the database. NULL values are treated
   * as scalars for the purpose of this method.
   *
   * Objects and arrays are cast to a string value, depending on the
   * property definition. If the 'serialize' flag is set to true,
   * serialize the value. Otherwise, cast it to a string. This will
   * work only with objects that have a __toString() method.
   *
   *
   * @param string $propertyName Name of the property to scalarize
   * @param mixed $value Value to scalarize
   * @throws qcl_core_PropertyBehaviorException
   * @return string
   */
  public function scalarize( $propertyName, $value )
  {
    /*
     * scalar values and null need no conversion
     */
    if ( is_scalar( $value ) or is_null( $value ) )
    {
      return $value;
    }
    /*
     * serialize the property if so defined
     */
    if ( isset( $this->properties[$propertyName]['serializer'] ) )
    {    
    	$method = $this->properties[$propertyName]['serializer'];
    	if ( ! method_exists($this->getModel(), $method ) )
    	{
	      throw new qcl_core_PropertyBehaviorException( sprintf(
	        "Unable to stringify '%s' type value. " .
	        "The serializer method '%s' for property '%s' doesn't exist in class '%s' .",
	      	typeof( $value, true ), $method, $propertyName, $this->getModel()->className()
	      ) );
    	}
    	return $this->getModel()->$method($value);
    }
    elseif ( isset( $this->properties[$propertyName]['serialize'] ) )
    {
    	$serialize = $this->properties[$propertyName]['serialize'];
    	qcl_assert_boolean( $serialize, sprintf(
	        "The 'serialize key for property '%s' in class '%s' must be a boolean value .",
	        $propertyName, $this->getModel()->className()
	     ) );
      return serialize( $value );
    }
    elseif( ! is_object( $value) )
    {
      throw new qcl_core_PropertyBehaviorException(
        "Unable to stringify '" . typeof( $value, true ) . "' type value. " .
        "Use the 'serialize' or the 'serializer' keys in the definition of property '$propertyName'."
      );
    }
    elseif ( method_exists( $value, "__toString" ) )
    {
      return (string) $value;
    }
    else
    {
      throw new qcl_core_PropertyBehaviorException(
        "Unable to stringify a " . get_class( $value ) . " class object. " .
        "Use the 'serialize' or the 'serializer' keys in the definition of property '$propertyName'."
      );
    }
  }

  /**
   * Returns true if the php type passed as argument is a native type
   * @param string $type
   * @return bool
   */
  protected function isNativeType( $type )
  {
    return in_array( $type, self::$native_types );
  }

  /**
   * Returns true if the property is to be included in an export.
   * @param $property
   * @return bool
   */
  public function isExportableProperty( $property )
  {
    return $this->properties[$property]['export'];
  }

  /**
   * Returns an array of properties that have been defined as
   * exportable.
   * @return array
   */
  public function exportableProperties()
  {
    $propList = array();
    foreach( $this->names() as $property )
    {
      if ( $this->isExportableProperty( $property ) )
      {
        $propList[] = $property;
      }
    }
    return $propList;
  }

  /**
   * Returns the "init" value of the property
   * @param $property
   * @return unknown_type
   */
  public function getInitValue( $property )
  {
    return $this->properties[$property]['init'];
  }
}
