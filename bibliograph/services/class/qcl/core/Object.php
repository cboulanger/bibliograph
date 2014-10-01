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

qcl_import( "qcl_core_IPropertyAccessors");
qcl_import( "qcl_core_PropertyBehavior");

/**
 * Base class of all qcl classes.
 * @todo remove all methods into global functions that have no
 * direct relationship with the object
 */
class qcl_core_Object
  implements qcl_core_IPropertyAccessors
{
  /**
   * Whether the model properties can be modified
   * @var bool
   */
  protected $readonly = false;

  /**
   * An array of names of the properties that are
   * allowed to be exported to a client copy of this
   * object. This can include "virtual properties" which
   * have a getter Method but no stored value.
   * Defaults to null;
   *
   * @var array
   */
  protected $clientProperties;

  /**
   * The globally unique id of this object.
   * Accessed with ::objectId()
   * to access it.
   * @var string
   */
  private $objectId = null;


  /**
   * The property behavior object
   * @var qcl_core_PropertyBehavior
   */
  private $propertyBehavior;

  /**
   * If the object has been initialized
   * @var bool
   */
  private $isInitialized = false;

  //-------------------------------------------------------------
  // Initialization & singleton
  //-------------------------------------------------------------

  /**
   * Class constructor. If the mixin class property contains
   * array entries, these classes will be mixed in.
   */
  function __construct()
  {
    /*
     * initialize object id
     */
    $this->objectId();

    //$this->log( "* Constructing $this", QCL_LOG_OBJECT );
  }

  /**
   * Initialize object
   * @return boolean True if initialization has to be done in the subclass,
   * false if object was already initialized earlier.
   */
  public function init()
  {
    if ( ! $this->isInitialized )
    {
      $this->getPropertyBehavior()->init();
      $this->isInitialized = true;
      return true;
    }
    return false;
  }

  /**
   * Returns a singleton instance of this class
   * @return qcl_core_Object
   */
  public static function getInstance()
  {
    return qcl_getInstance( get_called_class() );
  }

  /**
   * Returns true if object has been initialized
   * @return bool
   */
  public function isInitialized()
  {
    return $this->isInitialized;
  }

  /**
   * Resets the initialization state
   */
  public function resetInitialized()
  {
    $this->isInitialized = false;
  }

  /**
   * Return an array with  the names of the properties that are
   * allowed to be exported to a client copy of this
   * object. This can include "virtual properties" which
   * have a getter Method but no stored value.
   *
   * @return array
   */
  public function clientProperties()
  {
    return $this->clientProperties;
  }

  //-------------------------------------------------------------
  // Getters
  //-------------------------------------------------------------

  /**
   * Getter for application singleton instance.
   * @throws LogicException if no application exists.
   * @return qcl_application_Application
   */
  public function getApplication()
  {
    if( class_exists("qcl_application_Application") )
    {
      $app = qcl_application_Application::getInstance();
      if ( $app )
      {
        return $app;
      }
    }
    throw new LogicException("No application instance is available. Please check your setup.");
  }

  //-------------------------------------------------------------
  // Object id and class management
  //-------------------------------------------------------------

  /**
   * Returns the (hopefully) globally unique object id and generates
   * it if necessary. Registers object id in a global database.
   * This only works during one request, i.e. at runtime.
   * @return string
   */
  public function objectId()
  {
    if ( ! $this->objectId )
    {
      /*
       * generate object id
       */
      $this->objectId = uuid();

      /*
       * register it in global database
       * @todo move into static member
       */
      global $object_db;
      if ( ! $object_db )
      {
        $object_db = array();
      }
      $object_db[$this->objectId] = $this;

    }
    return $this->objectId;
  }

  /**
   * Returns an object identified by its id.
   * @param $objectId
   * @return qcl_core_Object
   * @todo rewrite without using a global variable
   */
  public function getObjectById($objectId)
  {
    global $object_db;
    return $object_db[$objectId];
  }

  //-------------------------------------------------------------
  // Main property API
  //-------------------------------------------------------------

  /**
   * Returns the behavior object responsible for maintaining the object
   * properties and providing access to them.
   * @return qcl_core_PropertyBehavior
   */
  public function getPropertyBehavior()
  {
    if ( $this->propertyBehavior === null )
    {
      $this->propertyBehavior = new qcl_core_PropertyBehavior( $this );
    }
    return $this->propertyBehavior;
  }

  /**
   * Returns an array of the names of the properties of this object.
   * @param boolean $ownPropertiesOnly
   * 		If true, return only the properties defined in the class. If false 
   * 		(default), return these properties plus all the inherited properties
   * 		of the parent classes.
   * @return array
   */
  public function properties( $ownPropertiesOnly = false )
  {
    return $this->getPropertyBehavior()->names( $ownPropertiesOnly );
  }

  /**
   * Returns an array of names of the properties that are defined in the
   * class, not excluding those defined in parent classes. This method
   * must be overridden and implemented in each class for which this behavior
   * is needed, since there is no generalizable way to determine these
   * property names.
   * @throws qcl_core_NotImplementedException
   * @return array
   */
  public function ownProperties()
  {
  	throw new qcl_core_NotImplementedException(__METHOD__);
  }  

  /**
   * Checks if class has this property.
   * Alias for $this->getPropertyBehavior()->has()
   * @param $property
   * @return bool
   */
  public function hasProperty( $property )
  {
    return $this->getPropertyBehavior()->has( $property );
  }

  /**
   * Checks if property exists and throws an error if not,
   * @param $property
   * @return bool
   */
  public function checkProperty( $property )
  {
    return $this->getPropertyBehavior()->check( $property );
  }

  /**
   * Generic getter for properties
   * @param $property
   * @return mixed
   */
  public function get( $property )
  {
    /*
     * initialize object and behaviors
     */
    $this->init();

    /*
     * call the behvior method to do the work
     */
    return $this->getPropertyBehavior()->get( $property );
  }

  /**
   * Gets the property directly. Internal method used to skip overridden
   * property getters.
   */
  public function _get( $property )
  {
    return $this->getPropertyBehavior()->_get( $property );
  }

  /**
   * Generic setter for properties.
   * @param string|array $property
   *    If string, set the corresponding property to $value.
   *    If array, assume it is a map and set each key-value pair.
   * @param mixed $value
   * @return qcl_core_Object
   */
  public function set( $property, $value=null )
  {
    /*
     * initialize object and behaviors
     */
    $this->init();

    /*
     * call the behvior method to do the work
     */
    return $this->getPropertyBehavior()->set( $property, $value );
  }

  /**
   * Sets the property directly. Internal method used to skip overridden
   * property setters. Returns the object itself for chaining
   * @param string|array $property
   *    If string, set the corresponding property to $value.
   *    If array, assume it is a map and set each key-value pair. 
   * @param mixed $value
   * @return qcl_core_Object
   */
  public function _set( $property,  $value=null )
  {
    return $this->getPropertyBehavior()->_set( $property, $value );
  }

  /**
   * Gets the values of all properties as an associative
   * array, keys being the property names.
   * @param array $options 
   * 		An associative array containing one or more of the following keys:
   * 			include => Array of property names to include
   * 			exclude	=> Array of property names to exclude
   * @return array
   */
  public function data( $options=null )
  {
    /*
     * initialize object and behaviors
     */
    $this->init();

    /*
     * call the behavior method to do the work
     */
    $data = $this->getPropertyBehavior()->data();
    
    /*
     * options
     */
    if ( is_array( $options ) )
    {
    	$delete = array();
    	
    	/*
    	 * include
    	 */
    	if ( isset( $options['include'] ) )
    	{
    		$delete = array_diff( 
    			$this->properties(),
    			(array) $options['include']
    		);
    	}

    	/*
    	 * exclude
    	 */
    	if ( isset( $options['exclude'] ) )
    	{
    		$delete = array_unique( array_merge(
    			$delete,
    			(array) $options['exclude']
    		) );
    	}     	
    	
    	/*
    	 * unset the given property values
    	 */
    	foreach( $delete as $prop )
    	{
    		unset( $data[$prop] );
    	}
    }
    
    return $data;
  }


  /**
   * Returns a map of properties that are allowed to be
   * exported to the client. This includes "virtual" properties
   * which have a getter method but no stored value.
   *
   * @see qcl_core_Object::clientProperties()
   * @return array
   *    Associative array of property names and property
   *    values.
   */
  public function clientData()
  {
    /*
     * initialize object and behaviors
     */
    $this->init();

    /*
     * get client properties
     */
    $data = array();
    foreach( $this->clientProperties() as $property )
    {
      $data[$property] = $this->get( $property );
    }
    return $data;
  }

  /**
   * Returns a human-readable string representation of the data
   * that can alsoe be parsed with eval(). Internally, this is
   * a simple var_export() of the resutl of the data() method.
   * @return string
   */
  public function exportData()
  {
    return var_export( $this->data(), true );
  }

  /**
   * Returns a array of property values according to the
   * array of property names that were passes as arguments.
   * @internal param $prop1
   * @internal param $prop2
   * @internal param $prop3 ...
   * @return array
   */
  public function listProperties()
  {
    /*
     * initialize object and behaviors
     */
    $this->init();

    /*
     * return properties as passed to the method
     */
    $result = array();
    foreach ( func_get_args() as $property )
    {
      $result[] = $this->get($property);
    }
    return $result;
  }

  /**
   * Whether the model is readonly
   * @return bool
   */
  public function isReadonly()
  {
    return $this->readonly;
  }

  /**
   * Allows easy traversal through the properties
   * of this object: $this->query("foo/bar/baz");
   *
   * @param string $path
   * @param mixed|null $node Optional property node to query
   * @throws InvalidArgumentException
   * @return mixed value
   */
  public function query( $path, $node=null )
  {
    if ( $node === null )
    {
      $node = $this;
    }
    $parts = explode( "/", $path );
    foreach( $parts as $part )
    {
      if ( is_object( $node ) and isset( $node->$part ) )
      {
        $node = $node->$part;
      }
      elseif ( is_array( $node ) and isset( $node[$part] ) )
      {
        $node = $node[$part];
      }
      else
      {
        throw new InvalidArgumentException("Cannot complete traversal of '$path', no match at '$part'");
      }
    }
    return $node;
  }

  //-------------------------------------------------------------
  // helper methods to compare and copy properties
  //-------------------------------------------------------------

  /**
   * Gets the data as an associated array from the data provided
   * @param array|stdClass|qcl_data_model_xmlSchema_DbModel $data
   * @throws InvalidArgumentException
   * @return array
   */
  protected function getArrayData( $data )
  {
    $array = null;
    if ( $data instanceof qcl_core_BaseClass )
    {
      $array = $data->data();
    }
    elseif ( is_object( $data ) )
    {
      $array = (array) $data;
    }
    elseif ( ! is_array( $data ) )
    {
      throw new InvalidArgumentException("Invalid parameter");
    }
    return $array;
  }

  /**
   * Compare current record with array. This will only compare
   * the keys existing in the array or the fields that are
   * provided as second argument.
   *
   * @param object|array $compare Model object or array data
   * @param array $fields
   * @return bool whether the values are equal or not
   */
  public function compareWith( $compare, $fields=null )
  {
    /*
     * initialize object and behaviors
     */
    $this->init();

    /*
     * check arguments to get what we should compare with
     */
    $array = $this->getArrayData( $compare );

    /*
     * assume data is equal as default and change this to false
     * as difference is found
     */
    $isEqual = true;

    /*
     * do the comparison
     */
    if ( is_array($fields) )
    {
      foreach ( $fields as $key  )
      {
        if ( $this->get($key) !== $array[$key] )
        {
          $isEqual = false;
        }
      }
    }
    else
    {
      foreach ( $array as $key => $value )
      {
        if ( $this->get($key) !== $value )
        {
          $isEqual = false;
        }
      }
    }

    /*
     * return the result
     */
    //$this->debug("The data is " . ($isEqual ? "equal" : "not equal") );
    return $isEqual;
  }

  /**
   * Returns all property values that exists in both models.
   * @param qcl_data_model_xmlSchema_DbModel $model
   * @return array
   */
  public function getSharedPropertyValues ( $model )
  {
    /*
     * initialize object and behaviors
     */
    $this->init();

    $myProperties    = $this->properties();
    $data            = $model->data();

    foreach( $data as $key => $value )
    {
      if ( ! in_array($key, $myProperties) )
      {
        unset($data[$value]);
      }
    }
    return $data;
  }

  /**
   * Copies all properties that exists in both models except the 'id' property.
   * @param qcl_data_model_AbstractActiveRecord $model
   * @param array $exclude
   * @return void
   */
  public function copySharedProperties ( $model, $exclude=array() )
  {
    $myProperties    = $this->properties();
    $data            = $model->data();

    foreach( $data as $key => $value )
    {
      if ( $key != "id" and in_array( $key, $myProperties ) and ! in_array( $key, $exclude ) )
      {
        $this->set($key,$value);
      }
    }
  }


  /**
   * Compares all properties that exists in both models.
   * @param qcl_data_model_xmlSchema_DbModel $that Other model
   * @param array[optional] $diff Array that needs to be passed by reference that will contain a list of parameters that differ
   * @return bool True if all property values are identical, false if not
   */
  public function compareSharedProperties ( $that, $diff=array() )
  {
    /*
     * initialize object and behaviors
     */
    $this->init();

    $properties = array_intersect(
      $this->properties(),
      $that->properties()
    );

    $isEqual = true;
    foreach( $properties as $name )
    {
      $prop1 = trim($this->get( $name ));
      $prop2 = trim($that->get( $name ));
      //$this->debug("$prop1 => $prop2");

      if ( $prop1 !== $prop2  )
      {
        $isEqual = false;
        $diff[$name] = array($prop1,$prop2);
      }
    }
    return $isEqual;
  }

  //-------------------------------------------------------------
  // 'magic' methods providing virtual accessor methods
  //-------------------------------------------------------------

//  /**
//   * Property write access. Allows to intercept direct access to the properties.
//   * @param $name
//   * @param $value
//   * @return void
//   */
//  public function __set( $name, $value )
//  {
//    /*
//     * if the object has a property of this name, set this first
//     */
//    if ( isset( $this->$name ) or
//         in_array( $name, array_keys( get_object_vars( $this ) ) ) )
//    {
//      $this->$name = $value;
//    }
//    elseif ( $this->getPropertyBehavior()->has( $name ) )
//    {
//      $this->getPropertyBehavior()->set( $name, $value );
//    }
//    else
//    {
//      $this->raiseError("Object has no property '$name" );
//      return null;
//    }
//  }
//
//  /**
//   * Property read access. Allows to intercept direct access to the properties.
//   * @param $name
//   * @return mixed
//   */
//  public function __get($name)
//  {
//    /*
//     * if the object has a property of this name, get this first
//     */
//    if ( isset( $this->$name ) or
//         in_array( $name, array_keys( get_object_vars( $this ) ) ) )
//    {
//      return $this->$name;
//    }
//    elseif ( $this->getPropertyBehavior()->has( $name ) )
//    {
//      return $this->getPropertyBehavior()->get( $name, $value );
//    }
//    else
//    {
//      $this->raiseError("Object has no property '$name" );
//      return null;
//    }
//  }

  /**
   * Method called when called method does not exist.
   * This will check whether method name is
   *
   * - getXxx or setXxx and then call get("xxx")
   *    or setProperty("xxx", $arguments[0] ).
   *
   * Otherwise, raise an error.
   * @param string $method Method name
   * @param array $arguments Array or parameters passed to the method
   * @throws LogicException
   * @return mixed return value.
   */
  function __call( $method, $arguments )
  {
    /*
     * if the method exists, it is has precedence
     */
    if ( method_exists( $this, $method ) )
    {
      return call_user_func_array( array($this, $method ), $arguments);
    }

    /*
     * accessor methods
     */
    $accessorMethodExists = false;
    $result = null;

    $accessor = strtolower( substr( $method, 0, 3 ) );
    $property = strtolower( substr( $method, 3 ) );

    if ( $accessor == "set" )
    {
      $this->checkProperty( $property );
      array_unshift( $arguments, $property);
      $result = call_user_func_array( array( $this, "set" ), $arguments);
      $accessorMethodExists = true;
    }
    elseif ( $accessor == "get" )
    {
      $this->checkProperty( $property );
      array_unshift( $arguments, $property);
      $result = call_user_func_array( array( $this, "get" ), $arguments);
      $accessorMethodExists = true;
    }

    /*
     * raise error if method does not exist
     */
    if ( ! $accessorMethodExists )
    {
      throw new LogicException( "Overload error: Unknown method " . get_class($this) . "::$method().");
    }
    return $result;
  }

  /**
   * Serializes the object to an array. This
   * @return array
   * @todo this is the same as data(), consolidate API!
   */
  function toArray()
  {
    return $this->getPropertyBehavior()->data();
  }

  /**
   * Serializes an array of public properties of this object into a string
   * that can be used by the unserialize() method to populate the object
   * properties.
   * @return string
   */
  public function serialize()
  {
    return serialize( $this->getPropertyBehavior()->data() );
  }

  /**
   * Serializes an array of public properties of this object into a string
   * that can be used by the unserialize() method to populate the object
   * properties.
   * @param $data
   * @return string
   */
  public function unserialize( $data )
  {
    $map = unserialize( $data );
    if ( ! is_array( $map ) )
    {
      $this->warn("Data cannot be unserialized!");
    }
    else
    {
      $this->set( $map );
    }
  }

  //-------------------------------------------------------------
  // Object initialization
  //-------------------------------------------------------------


  /**
   * Make a copy of this object
   */
  public function cloneObject()
  {
    return clone($this);
  }


  //-------------------------------------------------------------
  // Object and class introspection
  //-------------------------------------------------------------

  /**
   * OO alias for get_class($this)
   * @return string
   */
  public function className()
  {
    return get_class($this);
  }

  /**
   * The currently executed function.
   * @deprecated, use __METHOD__ instead
   * @return string
   */
  public function functionName()
  {
    $backtrace = debug_backtrace();
    return $backtrace[1]['function'];
  }

  /**
   * OO alias for method_exists($this)
   * @param string $method
   * return bool
   * @return bool
   */
  public function hasMethod( $method )
  {
    return method_exists($this,$method);
  }

  /**
   * OO alias for get_class_methods(get_class($this))
   * @return array
   */
  public function methods()
  {
    return get_class_methods( $this->className() );
  }

  //-------------------------------------------------------------
  // logging & debugging
  //-------------------------------------------------------------

  /**
   * Get logger object
   * @return qcl_log_Logger
   */
  public function getLogger()
  {
    return qcl_log_Logger::getInstance();
  }

  /**
   * Logs a message if the filters are enabled
   * @return void
   * @param mixed $msg
   * @param string|array $filters
   */
  public function log( $msg, $filters="debug" )
  {
    $this->getLogger()->log( $msg, $filters );
  }

  /**
   * Log a debug message. This method should be used only for
   * temporary debugging. Such method calls should be able to
   * be expunged completely by a global search/replace.
   * @return void
   * @param mixed $msg
   * @param string $class Optional class name
   * @param int $line Optional line number
   */
  public function debug($msg,$class=null,$line=null)
  {
    if ( is_bool( $msg ) )
    {
      $msg = boolString( $msg );
    }
    elseif ( ! is_scalar($msg) )
    {
      $msg = print_r($msg,true);
    }
    $m = ">>> DEBUG <<< ";
    if ( !($class and $line))
    {
      $bt = debug_backtrace();
      $caller = array_shift($bt);
      $class = $caller['file'];
      $line  = $caller['line'];
    }
    $m .= $class ."[" .$line . "]: ";
    $m .= "\n" . $msg;
    $this->info ( $m, "info" );
  }

  /**
   * Logs a message with of level "info"
   * @return void
   * @param mixed $msg
   */
  public function info ( $msg )
  {
    $this->log ( $msg, "info" );
  }


  /**
   * Logs a message with of level "warn"
   * @return void
   * @param $msg string
   */
  public function warn ( $msg )
  {
    $this->log ( $msg, "warn" );
  }

  /**
   * Returns the backtrace of invoked function calls
   * @return string list
   */
  public function backtrace()
  {
    $backtrace =  debug_get_backtrace(3);
    return $backtrace;
  }

  //-------------------------------------------------------------
  // Messages and events
  //-------------------------------------------------------------

  /**
   * Getter for message bus
   * @return qcl_event_message_Bus
   */
  public function getMessageBus()
  {
    qcl_import("qcl_event_message_Bus");
    return qcl_event_message_Bus::getInstance();
  }

  /**
   * Adds a message subscriber. This works only for objects which have been
   * initialized during runtime. Filtering not yet supported, i.e. message name must
   * match the one that has been used when subscribing the message, i.e. no wildcards!
   * @todo move to external class
   * @param string $filter
   * @param string $method Callback method of the current object
   */
  public function addSubscriber( $filter, $method )
  {
    $this->getMessageBus()->addSubscriber( $filter, $this, $method );
  }

  /**
   * Dispatches a message.
   * @param string $name Message name
   * @param mixed $data Data dispatched with message
   */
  public function dispatchMessage ( $name, $data=true )
  {
    $this->getMessageBus()->dispatchMessage( $this, $name, $data );
  }

  /**
   * Dispatches a server message.
   * @param string $name Message name
   * @param mixed $data Data dispatched with message
   */
  public function dispatchClientMessage ( $name, $data=true )
  {
    $this->getMessageBus()->dispatchClientMessage( $this, $name, $data );
  }

  /**
   * Broadcasts a message to all connected clients
   * @param string $name 
   * 		Message name
   * @param mixed $data 
   * 		Data dispatched with message
   * @param bool $excludeOwnSession 
   * 		Whether the current session should be excluded from the broadcast
   * 		(Default: false).
   */
  public function broadcastClientMessage ( $name, $data=true, $excludeOwnSession=false  )
  {
    $this->getMessageBus()->broadcastClientMessage( $this, $name, $data, $excludeOwnSession );
  }

  /**
   * Getter for event dispatcher
   * @return qcl_event_Dispatcher
   */
  public function getEventDispatcher()
  {
    qcl_import( "qcl_event_Dispatcher" );
    return qcl_event_Dispatcher::getInstance();
  }

  /**
   * Adds an event listener. Works only during runtime, i.e. event bindings are not
   * persisted.
   * @todo rewrite using event objects and support persisted event bindings
   * @param string $type The name of the event
   * @param string|qcl_core_Object $object The object or the object id retrieved by '$this->objectId()'
   * @param string $method callback method of the object
   */
  public function addListener( $type, $object, $method)
  {
    $this->getEventDispatcher()->addListener( $this, $type, $object, $method );
  }

  /**
   * Dispatches a server event.
   * @param qcl_event_type_Event $event
   * @internal param \qcl_core_Object $target
   * @return bool Whether the event was dispatched or not.
   */
  public function dispatchEvent ( $event )
  {
    $this->getEventDispatcher()->dispatchEvent( $this, $event );
  }

  /**
   * Fires an event
   * @param string $type Message Event type
   */
  public function fireEvent ( $type )
  {
    $this->getEventDispatcher()->fireEvent( $this, $type );
  }

  /**
   * Fires a data event
   * @param $type
   * @param mixed $data Data dispatched with event
   * @internal param mixed $event Message Event type
   */
  public function fireDataEvent ( $type, $data )
  {
    $this->getEventDispatcher()->fireDataEvent( $this, $type, $data );
  }

  //-------------------------------------------------------------
  // Translation
  //-------------------------------------------------------------

  /**
   * Translates a message. If the standard qcl_locale_Manager class
   * is used, the gettext domain is taken from the first
   * segment of the class name. Class foo_bar_Baz will use translations of
   * domain "foo", stored in "foo/class/locale/xx/LC_MESSAGES/foo.po".
   *
   * @param  string  $msgId
   *    Message id of the string to be translated
   * @param   array|string  $varargs
   *    (optional) Variable number of arguments for the sprintf formatting either as an array
   *    or as parameters
   * @return  String
   */
  public function tr( $msgId, $varargs=null )
  {
    if ( ! is_array($varargs) )
    {
      $varargs = func_get_args();
      array_shift($varargs);
    }
    $manager = $this->getApplication()->getLocaleManager();
    return $manager->tr($msgId, $varargs, $this->className() );
  }

  /**
   * Translate a plural message.Depending on the third argument the plural
   * or the singular form is chosen.
   *
   * @see qcl_core_Object::tr()
   * @param string $singularMessageId
   *    Message id of the singular form (may contain format strings)
   * @param string $pluralMessageId
   *    Message id of the plural form (may contain format strings)
   * @param int $count
   *    If greater than 1 the plural form otherwhise the singular form
   *    is returned.
   * @param array|string $varargs
   *    (optional) Variable number of arguments for the sprintf formatting
   * @return string
   */
  public function trn ( $singularMessageId, $pluralMessageId, $count, $varargs=null )
  {
    if ( ! is_array($varargs) )
    {
      $varargs = func_get_args();
      array_shift($varargs);
      array_shift($varargs);
      array_shift($varargs);
    }
    $manager = $this->getApplication()->getLocaleManager();
    return $manager->trn( $singularMessageId, $pluralMessageId, $count, $varargs, $this->className() );
  }

  /**
   * Marks a message string for translation without actually translating it.
   * This might make sense where a translation is needed at a different
   * place in the code using the content of a variable.
   *
   * @param $msgId
   * @return string Returns the input string
   */
  public function marktr( $msgId )
  {
    return $msgId;
  }

  //-------------------------------------------------------------
  // Converting object into other formats
  //-------------------------------------------------------------

  /**
   * Returns a string representation of the object that has purely informational
   * value
   * @return string
   */
  public function __toString()
  {
    return "[" . $this->className() . " #" . $this->objectId() . "]";
  }

  /**
   * Dumps a variable to a string representation
   * @return string
   */
  public function dump()
  {
    return var_export( $this, true );
  }
}
