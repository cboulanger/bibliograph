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
qcl_import( "qcl_core_PersistentObject" );
qcl_import( "qcl_data_model_db_PersistenceBehavior" );

/**
 * Object that can be persisted in a database. Unlike
 * qcl_data_model_db_PersistentModel, the object persists simply
 * the public properties of the model.
 */
class qcl_data_model_db_PersistentObject
  extends     qcl_core_PersistentObject
  implements  qcl_core_IPersistable
{

  //-------------------------------------------------------------
  // Class properties
  //-------------------------------------------------------------

  /**
   * Whether this model is tied to a user, i.e., if it is
   * to be deleted when the user no longer exists
   */
  protected $isBoundToUser = false;

  /**
   * Whether this model is tied to a session, i.e., if it is
   * to be deleted when the session expires
   */
  protected $isBoundToSession = false;

  //-------------------------------------------------------------
  // getters and setters
  //-------------------------------------------------------------

  public function isBoundToUser()
  {
    return $this->isBoundToUser;
  }

  public function isBoundToSession()
  {
    return $this->isBoundToSession;
  }
  //-------------------------------------------------------------
  // Persistence
  //-------------------------------------------------------------

  /**
   * Getter for persistence behavior.
   * @return qcl_data_model_db_PersistenceBehavior
   */
  function getPersistenceBehavior()
  {
    return qcl_data_model_db_PersistenceBehavior::getInstance();
  }
}
