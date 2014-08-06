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
qcl_import( "qcl_core_PersistentObject" );
qcl_import( "qcl_data_file_PersistenceBehavior" );

/**
 * Object that can be persisted in the filesystem (using the temp folder of the OS).
 */
class qcl_data_file_PersistentObject
  extends     qcl_core_PersistentObject
  implements  qcl_core_IPersistable
{

  /**
   * Getter for persistence behavior.
   * @return qcl_data_model_file_PersistenceBehavior
   */
  function getPersistenceBehavior()
  {
    return qcl_data_file_PersistenceBehavior::getInstance();
  }
}
