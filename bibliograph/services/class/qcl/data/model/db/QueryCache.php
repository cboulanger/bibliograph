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
 * Cache for query setup
 */
class qcl_data_model_db_QueryCache
  extends qcl_core_PersistentObject
{
  public $indexes = array();

  public function reset()
  {
    $this->indexes = array();
    $this->savePersistenceData();
  }
}
?>