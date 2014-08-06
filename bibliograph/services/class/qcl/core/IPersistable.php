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

interface qcl_core_IPersistable
{
  /**
   * Whether the object has been newly created (true) or has been restored
   * from a cache
   * @return bool
   */
  public function isNew();

  /**
   * Whether the data is disposed, i.e. not persisted any longer
   * @return bool
   */
  public function isDisposed();

  /**
   * Getter for persistence behavior. Defaults to persistence in
   * the session.
   * @return qcl_core_IPersistenceBehavior
   */
  function getPersistenceBehavior();

  /**
   * Returns the id that is used to persist the object between
   * requests.
   * @return string
   */
  function getPersistenceId();

  /**
   * Persist the properties of the object so that they will be
   * restored upon next instantiation of the object.
   * @return void
   */
  public function savePersistenceData();

  /**
   * Disposes the persisted data
   * @return unknown_type
   */
  public function disposePersistenceData();

  /**
   * Destructor. Calls savePersistenceData() if the data hasn't been
   * disposed.
   */
  function __destruct();
}
