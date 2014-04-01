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
 * Cache for property setup.
 *
 * @todo Currently, this cannot be converted into a
 * qcl_data_model_db_PersistentObject because this creates an infinite
 * recursion ( qcl_data_model_db_PersistentObject depends on
 * qcl_data_model_db_ActiveRecord which depends on
 * qcl_data_model_db_PropertyCache which depends on qcl_core_PersistentObject
 * which depends on ... ). Need to find a way to break this circular dependency,
 * because the caches should not be tied to the session but instead persisted
 * in the database.
 */
class qcl_data_model_db_PropertyCache
  extends qcl_core_PersistentObject
{
  public $tables     = array();
  public $properties = array();

  public function reset()
  {
    $this->tables = array();
    $this->properties = array();
    $this->savePersistenceData();
  }
}
?>