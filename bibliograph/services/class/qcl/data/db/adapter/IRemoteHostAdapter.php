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

interface qcl_data_db_adapter_IRemoteHostAdapter
{

  /**
   * getter for database host
   * @return string
   */
  public function getHost();


  /**
   * getter for database port
   * @return string
   */
  public function getPort();


  /**
   * Returns current database name
   * @return string
   */
  public function getDatabase();


}


?>