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

qcl_import("qcl_core_IPersistenceBehavior");

/**
 * Persistence Behavior that saves the object's public properties
 * to the PHP session by an id. If you want to save only one instance
 * of the object, use the class name as id.
 *
 * FIXME Do we need $object AND $id? $object should have its persistence
 * id built in -> change interface and implementations!
 */
class qcl_core_PersistenceBehavior
  implements qcl_core_IPersistenceBehavior
{
  const KEY = QCL_DATA_PERSISTENCE_SESSION;

  /**
   * Return singleton instance of this class
   * @return qcl_core_PersistenceBehavior
   */
  public static function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }

  /**
   * Loads the object's public properties from the session
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   * @return boolean Whether object data has been found and restored (true)
   *  or not (false)
   */
  public function restore( $object, $id )
  {
    if ( isset( $_SESSION[ self::KEY ][ $id ] ) )
    {
      qcl_log_Logger::getInstance()->log( $object->className() . ": loading data with id '$id'",QCL_LOG_PERSISTENCE);
      $object->unserialize( $_SESSION[ self::KEY ][ $id ] );
      return true;
    }
    else
    {
      qcl_log_Logger::getInstance()->log( $object->className() . ": no cached data with id '$id'",QCL_LOG_PERSISTENCE);
      return false;
    }
  }

  /**
   * Saves the managed object's public property to the session
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   */
  public function persist( $object, $id )
  {
//    @todo cannot use logging during shutdown, when called from destructor
//    qcl_log_Logger::getInstance()->log( $object->className() . " saved to cache with id '$id'", QCL_LOG_PERSISTENCE);
    $_SESSION[ self::KEY ][ $id ] = $object->serialize();
  }

  /**
   * Dispose the persistence data for the object with the given id.
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   */
  public function dispose( $object, $id )
  {
    qcl_log_Logger::getInstance()->log( "Deleting persistence data for " . $object->className() . " (id '$id')", QCL_LOG_PERSISTENCE);
    unset( $_SESSION[ self::KEY ][ $id ] );
  }

  /**
   * Resets all persistence data
   * @return void
   */
  public function reset()
  {
    unset( $_SESSION[ self::KEY ] );
  }
}
