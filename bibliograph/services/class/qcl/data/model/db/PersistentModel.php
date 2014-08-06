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
qcl_import( "qcl_data_model_PersistentModel" );
qcl_import( "qcl_data_model_db_PersistenceBehavior" );

/**
 * Model that can be persisted in a database. Properties are defined
 * qooxdoo-style, see qcl_data_model_db_PropertyBehavior. The model
 * automatically retrieves the property state it had when it was
 * last saved. Do not instantiate more than one instance of this model,
 * see qcl_core_PersistentObject.
 *
 * @see qcl_data_model_db_PropertyBehavior
 */
class qcl_data_model_db_PersistentModel
  extends     qcl_data_model_PersistentModel
  implements  qcl_core_IPersistable
{

  //-------------------------------------------------------------
  // Define the model properties in the subclass as
  // described in qcl_data_model_db_PropertyBehavior
  //-------------------------------------------------------------

  //private $properties = array();

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
   * Getter for persistence behavior. Defaults to persistence in
   * the session.
   * @return qcl_data_model_db_PersistenceBehavior
   */
  function getPersistenceBehavior()
  {
    return qcl_data_model_db_PersistenceBehavior::getInstance();
  }

  /**
   * Returns the query behavior.
   * @return qcl_data_model_db_QueryBehavior
   */
  public function getQueryBehavior()
  {
    return $this->getPersistenceBehavior()->getQueryBehavior();
  }
}
