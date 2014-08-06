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
 * Class which maintains a registry which is
 * persiste in the application database.
 */
class qcl_util_registry_Persist
{

  /**
   * Returns the persistent registry
   * @return array
   */
  private static function getRegistryObject()
  {
    static $obj = null;
    if ( is_null($obj) )
    {
      require_once "qcl/util/registry/PersistentRegistry.php";
      $obj = new qcl_data_persistence_PersistentRegistry( __CLASS__ );
    }
    return $obj;
  }

  /**
   * Resets the page load registry. this needs to be
   * called, for example, when a user logs out, Can
   * be called statically
   */
  public static function reset()
  {
    $obj = self::getRegistryObject();
    $obj->registry = array();
    $obj->save();
  }

  /**
   * Sets the registry value. Can be called statically
   *
   * @param string $key
   * @param mixed $value
   */
  public static function set( $key, $value )
  {
    $obj = self::getRegistryObject();
    $obj->registry[$key] = $value;
    $obj->save();
  }

  /**
   * Gets the registry value. Can be called statically
   *
   * @param string $key
   * @return mixed
   */
  public static function get( $key )
  {
    $obj = self::getRegistryObject();
    return $obj->registry[$key];
  }


  /**
   * Check if registry value is set. Can be called statically
   *
   * @param string $key
   * @return mixed
   */
  public static function has( $key )
  {
    $obj = self::getRegistryObject();
    return isset( $obj->registry[$key] );
  }
}
