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

qcl_import( "qcl_data_model_db_PersistentObject" );

class qcl_application_ApplicationCache
  extends qcl_data_model_db_PersistentObject
{
  public $datasourceRegistered = false;
  public $dataImported = false;

  /**
   * @return qcl_application_ApplicationCache
   */
  static public function getInstance()
  {
    return qcl_getInstance( __CLASS__ );
  }
}
?>