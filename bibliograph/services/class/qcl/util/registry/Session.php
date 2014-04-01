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
 * Class which maintains a registry which is valid during one
 * PHP session
 */
class qcl_util_registry_Session
{

  const KEY = "QCL_UTIL_REGISTRY_SESSION_KEY";

  /**
   * resets the page load registry. this needs to be
   * called, for example, when a user logs out. Can
   * be called statically.
   */
  public static function reset()
  {
    $_SESSION[ self::KEY ] = array();
  }

  /**
   * Sets the registry value.
   *
   * @param string $key
   * @param mixed $value
   */
  public static function set( $key, $value )
  {
    $_SESSION[ self::KEY ][$key] = $value;
  }

  /**
   * Gets the registry value.
   *
   * @param string $key
   * @return mixed
   */
  public static function get( $key )
  {
    return $_SESSION[ self::KEY ][$key];
  }


  /**
   * Check if registry value is set.
   *
   * @param string $key
   * @return mixed
   */
  public static function has( $key )
  {
    return isset( $_SESSION[ self::KEY ][$key] );
  }
}
?>