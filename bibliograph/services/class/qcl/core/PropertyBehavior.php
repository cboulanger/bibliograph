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

qcl_import("qcl_core_IPropertyBehavior") ;

/**
 * Exceptions thrown in this class
 */
class qcl_core_PropertyBehaviorException extends LogicException {}

/**
 * Default property behvior. Provides access to the named
 * public member properties of the object affected by this behavior.
 */
class qcl_core_PropertyBehavior
  implements qcl_core_IPropertyBehavior
{
 /**
   * The the object affected by this behavior
   * @var unknown_type
   */
  protected $object = null;

  /**
   * The logger instance
   * @var qcl_log_Logger
   */
  protected $logger;
  
  
  /**
   * Wheter the property has already been initialized
   */
  protected $isInitialized;
  

  //-------------------------------------------------------------
  // initialization
  //-------------------------------------------------------------

  /**
   * Constructor
   * @param qcl_core_Object $object Object affected by this behavior
   * @return qcl_core_PropertyBehavior
   */
  function __construct( qcl_core_Object $object )
  {
    $this->object = $object;
    $this->logger = qcl_log_Logger::getInstance();
  }


  /**
   * Initializes behavior. Does nothing currently.
   * @return bool True if calling subclass should also do initialization
   * stuff, false if already initialized
   */
  public function init()
  {
    if ( ! $this->isInitialized )
    {
//      $this->object->log( sprintf(
//        "* Initializing object properties for '%s' using '%s'",
//        $this->object->className(), get_class( $this )
//      ), QCL_LOG_PROPERTIES );

      // do nothing at this point

      $this->isInitialized = true;
      return true;
    }
    return false;
  }

  //-------------------------------------------------------------
  // logging
  //-------------------------------------------------------------

  /**
   * Checks if the QCL_LOG_PROPERTIES is enabled
   * @return unknown_type
   */
  protected function hasLog()
  {
    return $this->logger->isFilterEnabled( QCL_LOG_PROPERTIES );
  }

  /**
   * Logs a message with the QCL_LOG_PROPERTIES filter
   * @param $msg
   * @return void
   */
  protected function log( $msg )
  {
    $this->logger->log( $msg, QCL_LOG_PROPERTIES );
  }

  //-------------------------------------------------------------
  // API
  //-------------------------------------------------------------


  /**
   * Getter for object
   * @return qcl_core_Object
   */
  protected function getObject()
  {
    return $this->object;
  }

  /**
   * Checks if the object has a public property of this name or if the object
   * has a getter and setter method for this property.
   * @param $property
   * @return bool
   */
  public function has( $property )
  {
    return
      in_array( $property, $this->names() )
      or ( $this->hasGetter( $property ) and $this->hasSetter( $property ) );
  }

  /**
   * Checks if property exists and throws an error if not.
   * @param $property
   * @return bool
   * @throws InvalidArgumentException
   * @throws qcl_core_PropertyBehaviorException
   */
  public function check( $property )
  {
   	if ( ! $property or ! is_string($property) )
  	{
  		throw new InvalidArgumentException( "Invalid property argument" );
  	}
    if ( ! $this->has( $property) )
    {
      //$this->object->warn( $this->object->backtrace() ); // @todo necessary?
      throw new qcl_core_PropertyBehaviorException( sprintf(
        "Class '%s': object property '%s' does not exist or is not accessible",#
        get_class( $this->object ), $property
      ) );
    }
  }

  /**
   * Generic getter for properties
   * @param $property
   * @return mixed
   * @throws InvalidArgumentException
   */
  public function get( $property )
  {
  	if ( ! $property or ! is_string($property) )
  	{
  		throw new InvalidArgumentException( "Invalid property argument" );
  	}
  	
  	if ( ! $this->has( $property ) )
  	{
  	  throw new InvalidArgumentException( sprintf(
  	   "Class %s has no managed property %s", 
  	   $this->getObject()->className(),
  	   $property
  	  ) );
  	}
  	
    if ( $this->hasGetter( $property ) )
    {
      $getterMethod = $this->getterMethod( $property );
      return $this->object->$getterMethod();
    }
    return $this->_get( $property );
  }

  /**
   * Implementation for getter for properties
   * @param $property
   * @return unknown_type
   */
  public function _get( $property )
  {
    $this->check( $property );
    if ( $this->hasGetter( $property ) )
    {
      $getterMethod = $this->getterMethod( $property );
      return $this->object->$getterMethod();
    }
    return $this->object->$property;
  }

  /**
   * Setter for properties.
   * @param string|array $property If string, set the corresponding property
   *  to $value. If array, assume it is a map and set each key-value pair.
   *  Returns the object to allow chained setting.
   * @param mixed $value
   * @throws LogicException
   * @throws InvalidArgumentException
   * @return qcl_core_Object
   */
  public function set( $property, $value=null )
  {
    //if( is_string($property ) ) $this->object->info("set '$property'" );
    /*
     * readonly?
     */
    if ( $this->object->isReadonly() )
    {
      throw new LogicException("Object is readonly.");
    }

    /*
     * if first argument is array, iterate through array
     */
    if ( is_array( $property ) and $value === null )
    {
      foreach ( $property as $key => $value )
      {
        $this->set( $key, $value );
      }
      return $this->object;
    }

    /*
     * if first argument is object, iterate through object properties
     */
    elseif ( is_object( $property ) and $value === null )
    {
      foreach( get_object_vars( $property )  as $key => $value )
      {
        $this->set( $key, $value );
      }
      return $this->object;
    }

    /*
     * normal case, arguments are property and value
     */
    elseif ( is_string( $property ) )
    {
      if ( $this->hasSetter( $property ) )
      {
        $setterMethod = $this->setterMethod( $property );
        return $this->object->$setterMethod( $value );
      }
      else
      {
        return $this->_set( $property, $value );
      }
    }

    /*
     * else, error
     */
    else
    {
      throw new InvalidArgumentException("Invalid arguments");
    }
  }

  /**
   * Implementation for set(). Returns the object itself for chaining.
   * @param string $property
   * @param mixed $value
   * @return qcl_core_Object
   */
  public function _set( $property, $value )
  {
    $this->check( $property );
    $this->object->$property = $value;
    return $this->object;
  }

  /**
   * Returns the name of the getter method for a property
   * @param string $property
   * @return string
   */
  public function getterMethod( $property )
  {
    return "get" . ucfirst( $property );
  }

  /**
   * Returns the name of the setter method for a property
   * @param string $property
   * @return string
   */
  public function setterMethod( $property )
  {
    return "set" . ucfirst( $property );
  }

  /**
   * Checks if the object has a getter method for this property
   * @param string $property
   * @return bool
   */
  public function hasGetter( $property )
  {
    return method_exists( $this->object, $this->getterMethod( $property ) );
  }

  /**
   * Checks if the object has a setter method for this property
   * @param string $property
   * @return bool
   */
  public function hasSetter( $property )
  {
    return method_exists( $this->object, $this->setterMethod( $property ) );
  }

  /**
   * Checks whether the property has a local or internal name (such as a
   * column name that is different from the property name).
   * @param $property
   * @return bool
   */
  public function hasLocalAlias( $property )
  {
    return false;
  }

  /**
   * Returns the php type of the  property.
   * @see http://www.php.net/manual/de/function.gettype.php
   * This only works if the property has been set.
   * @param $property
   * @return string
   */
  public function type( $property )
  {
    $this->check( $property );
    return gettype( $this->object->$property );
  }

  /**
   * The names of all the managed properties of this class.
   * @param boolean $ownPropertiesOnly
   * 		If true, return only the properties defined in the class. If false 
   * 		(default), return these properties plus all the inherited properties
   * 		of the parent classes.
   * FIXME $ownPropertiesOnly not impemented
   * @return array
   */
  public function names( $ownPropertiesOnly=false )
  {
  	if ( $ownPropertiesOnly )
  	{
  		return $this->object->ownProperties(); 	
  	}
  	else 
  	{
  		return array_keys( get_class_vars( get_class( $this->object ) ) );	
  	}
  }
  
  /**
   * Returns all the managed properties as a map
   * @return array Associative array of key-value pairs
   */
  public function data()
  {
    $map = array();
    foreach( $this->names() as $name )
    {
      $map[$name] = $this->get( $name );
    }
    return $map;
  }

  /**
   * Resets any internal data the behavior might keep
   * @return void
   */
  public function reset()
  {
    // do nothing
  }
}
