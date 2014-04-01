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

/**
 * Interface for property behaviors
 */
interface qcl_core_IPropertyBehavior
{

  /**
   * Checks if class has a public property of this type.
   * @param $property
   * @return bool
   */
  public function has( $property );

  /**
   * Checks if property exists and throws an error if not.
   * @param $property
   * @return bool
   */
  public function check( $property );

  /**
   * Generic getter for properties
   * @param $property
   * @return mixed
   */
  public function get( $property );

  /**
   * Setter for properties.
   * @param string|array $property If string, set the corresponding property
   *  to $value. If array, assume it is a map and set each key-value pair.
   *  Returns the object to allow chained setting.
   * @param mixed $value
   * @return qcl_core_Object
   */
  public function set( $property, $value=null );

  /**
   * Returns the name of the getter method for a property
   * @param string $property
   * @return string
   */
  public function getterMethod( $property );

  /**
   * Returns the name of the setter method for a property
   * @param string $property
   * @return string
   */
  public function setterMethod( $property );

  /**
   * Checks if the object has a getter method for this property
   * @param string $property
   * @return bool
   */
  public function hasGetter( $property );

  /**
   * Checks if the object has a setter method for this property
   * @param string $property
   * @return bool
   */
  public function hasSetter( $property );

  /**
   * Checks whether the property has a local or internal name (such as a
   * column name that is different from the property name).
   * @param $property
   * @return bool
   */
  public function hasLocalAlias( $property );

  /**
   * Returns the type of the property depending on the behavior implementation
   * @param $property
   * @return string
   */
  public function type( $property );

  /**
   * The names of all the managed properties
   * @return array
   */
  public function names();

  /**
   * Returns all the managed properties as a map
   * @return array Associative array of key-value pairs
   */
  public function data();

  /**
   * Resets any internal data the behavior might keep
   * @return void
   */
  public function reset();


}
?>