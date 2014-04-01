<?php
/*
 * qooxdoo - the new era of web development
 *
 * http://qooxdoo.org
 *
 * Copyright:
 *   2006-2009 Derrell Lipman, Christian Boulanger
 *
 * License:
 *   LGPL: http://www.gnu.org/licenses/lgpl.html
 *   EPL: http://www.eclipse.org/org/documents/epl-v10.php
 *   See the LICENSE file in the project's top-level directory for details.
 *
 * Authors:
 *  * Derrell Lipman (derrell)
 *  * Christian Boulanger (cboulanger) Error-Handling and OO-style rewrite
 */

/**
 * System class containing methods that inform about the server or
 * modify the server features.
 */
class class_System
{
  /**
   * The capabilities of the server
   * @var array
   */
  private $capabilities = array();

  /**
   * Returns singleton instance of this class
   */
  public static function getInstance( $class = __CLASS__ )
  {
    static $instance = null;
    if ( $instance === null )
    {
      $instance = new self;
    }
    return $instance;
  }

  /**
   * Return the list of capabilities-
   * @public
   */
  public function method_getCapabilities()
  {
    $_this = class_System::getInstance();
    return $_this->capabilities;
  }

  /**
   * Add a capability to the server. Must be called on the singleton.
   */
  public function addCapability( $name, $url, $version, $services=array(), $methods=array() )
  {
    $this->capabilities[$name] = array(
      "specUrl"       => $url,
      "specVersion"   => $version,
      "specServices"  => $services,
      "specMethods"   => $methods
    );
  }

  /**
   * Checks if a capablity (of a certain version) exists-
   * @param string $name Name of the capability
   * @param string|null $version Optional version, will be checked if given
   * @return bool
   */
  public function hasCapability( $name, $version=null )
  {
    return isset( $this->capabilities[$name] )
      and ( is_null( $version ) or $this->capabilities[$name]['specVersion'] == $version );
  }

  /**
   * Returns information on a capability.
   * @param string $name Name of the capability
   * @return array Map
   */
  public function getCapability( $name )
  {
    return  $this->capabilities[$name];
  }
}
?>