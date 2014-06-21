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
 * For performance reasons, and because of the complexity of the property objecs,
 * qcl works heavily with shared class instances. This class manages the singletons.
 * It allows to reset the singleton cache selectively (For example, when models need
 * to be rebuilt because of an environment change).
 */
class qcl_core_SingletonManager extends qcl_core_Object
{
  /**
   * Private array of instances
   * @var array
   */
  private static $instances = array();
  
  /**
   * Returns an instance of the given class
   * @param string $clazz Class name
   */
  public static function createInstance( $clazz )
  {
    if ( ! isset( self::$instances[ $clazz ] ) )
    {
      self::$instances[ $clazz ] = new $clazz;
    }
    return self::$instances[ $clazz ];
  }
  
  /**
   * Resets the instances with the given class name(s). Names can be regular
   * expressions to reset several singleton instances at once.
   * @param array|string $names 
   *    A class name or an array of class names.
   * @param bool $isRegExpr 
   *    Whether the name(s) are regular expressions
   */
  public static function resetInstance( $name, $isRegExpr=false )
  {
    if ( is_array( $name ) )
    {
      foreach( $name as $n )
      {
        self::resetInstance( $n, $isRegExpr );
      }
      return;
    }
    
    $found = array();
    if( $isRegExpr )
    {
      $found = preg_grep( $name, array_keys( self::$instances ) );
    }
    elseif ( isset( self::$instances[ $name ] ) )
    {
      $found = array( $name );
    }
    
    if ( count($found) == 0 )
    {
      throw new LogicException( "No instance of class $name exists." );
    }
    
    foreach( $found as $n)
    {
      unset( self::$instances[ $n ] );
    }
  }
}