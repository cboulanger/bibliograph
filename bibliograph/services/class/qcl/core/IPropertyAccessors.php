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

interface qcl_core_IPropertyAccessors
{

  /**
   * Returns the behavior object responsible for maintaining the object
   * properties and providing access to them.
   * @return qcl_core_IPropertyBehavior
   */
  public function getPropertyBehavior();

  /**
   * Return the names of all properties of this object.
   * @param boolean $ownPropertiesOnly
   * 		If true, return only the properties defined in the class. If false 
   * 		(default), return these properties plus all the inherited properties
   * 		of the parent classes.
   * @return array
   */
  public function properties( $ownPropertiesOnly = false );

  /**
   * Checks if class has this property.
   * Alias for $this->getPropertyBehavior()->has()
   * @param $property
   * @return bool
   */
  public function hasProperty( $property );

  /**
   * Checks if property exists and throws an error if not,
   * @param $property
   * @return bool
   */
  public function checkProperty( $property );

  /**
   * Generic getter for properties
   * @param $property
   * @return mixed
   */
  public function get( $property );

  /**
   * Generic setter for properties.
   * @param string|array $property If string, set the corresponding property to $value.
   *   If array, assume it is a map and set each key-value pair. Returns the object.
   * @param mixed $value
   * @return qcl_core_BaseClass
   */
  public function set( $property, $value=null );

  /**
   * Gets the values of all properties as an associative
   * array, keys being the property names.
   * @param null $options
   * @return array
   */
  public function data($options=null);

  /**
   * Returns a array of property values according to the
   * array of property names that were passes as arguments.
   * @internal param $prop1
   * @internal param $prop2
   * @internal param $prop3 ...
   * @return array
   */
  public function listProperties();

  /**
   * Compare current record with array. This will only compare
   * the keys existing in the array or the fields that are
   * provided as second argument.
   *
   * @param object|array $compare Model object or array data
   * @param array $fields
   * @return bool whether the values are equal or not
   */
  public function compareWith( $compare, $fields=null );

  /**
   * Returns all property values that exists in both models.
   * @param qcl_core_object $object
   * @return array
   */
  public function getSharedPropertyValues ( $object );


  /**
   * Copies all properties that exists in both models except the 'id' property.
   * @param qcl_core_Object $object
   * @param array $exclude
   * @return void
   */
  public function copySharedProperties ( $object, $exclude=array() );

  /**
   * Compares all properties that exists in both models.
   * @param qcl_core_Object $object
   * @param array[optional] $diff Array that needs to be passed by
   *  reference that will contain a list of parameters that differ
   * @return bool True if all property values are identical, false if not
   */
  public function compareSharedProperties ( $object, $diff=array() );

  //-------------------------------------------------------------
  // 'magic' methods providing virtual accessor methods
  //-------------------------------------------------------------

//  /**
//   * Property write access. Allows to intercept direct access to the properties.
//   * @param $name
//   * @param $value
//   * @return void
//   */
//  public function __set( $name, $value );

//
//  /**
//   * Property read access. Allows to intercept direct access to the properties.
//   * @param $name
//   * @return mixed
//   */
//  public function __get($name);

  /**
   * Method called when called method does not exist.
   * This will check whether method name is
   *
   * - getXxx or setXxx and then call get("xxx")
   *    or setProperty("xxx", $arguments[0] ).
   *
   * Otherwise, raise an error.
   * @param string $method  Method name
   * @param array  $arguments Array or parameters passed to the method
   * @return mixed return value.
   */
  function __call( $method, $arguments );

  /**
   * Serializes an array of public properties of this object into a string
   * that can be used by the unserialize() method to populate the object
   * properties.
   * @return string
   */
  public function serialize();

  /**
   * Serializes an array of public properties of this object into a string
   * that can be used by the unserialize() method to populate the object
   * properties.
   * @param $data
   * @return string
   */
  public function unserialize( $data );

}
