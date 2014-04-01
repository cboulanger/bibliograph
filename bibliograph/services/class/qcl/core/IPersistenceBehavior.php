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
 * Interface for persistence behaviors. Always use the singleton
 * instance.
 */
interface qcl_core_IPersistenceBehavior
{

  /**
   * Returns a singleton instance of this class
   * @return qcl_core_Object
   */
  public static function getInstance();

  /**
   * Restores the persistent object from the cache, i.e populate the public
   * properties from the saved data.
   *
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   * @return boolean Whether object data has been found and restored (true)
   *  or not (false)
   */
  public function restore( $object, $id );

  /**
   * Saves the public properties of the object to the
   * behaviors's datasource
   *
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   * @return void
   */
  public function persist( $object, $id );

  /**
   * Disposes the persistence data for the object with the given id.
   * @param qcl_core_Object $object Persisted object
   * @param string $id The id of the saved object
   */
  public function dispose( $object, $id );

}
?>