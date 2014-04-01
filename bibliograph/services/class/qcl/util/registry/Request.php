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
 * Class which maintains a registry which is valid during one request
 */
class qcl_util_registry_Request
{

  /**
   * Registry cache
   * @var array
   */
  private $registry = array();

  /**
   * Returns a singleton instance of this class
   * @return qcl_util_registry_Request
   */
  public static function getInstance( )
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * Resets the page load registry. this needs to be
   * called, for example, when a user logs out, Can
   * be called statically
   */
  public static function reset()
  {
    $_this = qcl_util_registry_Request::getInstance();
    $_this->registry = array();
  }

  /**
   * Sets the registry value. Can be called statically
   *
   * @param string $key
   * @param mixed $value
   */
  public static function set( $key, $value )
  {
    $_this = qcl_util_registry_Request::getInstance();
    $_this->registry[$key] = $value;
  }

  /**
   * Gets the registry value. Can be called statically
   *
   * @param string $key
   * @return mixed
   */
  public static function get( $key )
  {
    $_this = qcl_util_registry_Request::getInstance();
    return $_this->registry[$key];
  }


  /**
   * Check if registry value is set. Can be called statically
   *
   * @param string $key
   * @return mixed
   */
  public static function has( $key )
  {
    $_this = qcl_util_registry_Request::getInstance();
    return isset( $_this->registry[$key] );
  }
}
?>