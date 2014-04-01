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
 * Interface for a database-based model
 */
interface qcl_data_model_db_IModel
{

  /**
   * Getter for table name.
   * @return string Name of the table this model stores its data in.
   */
  public function tableName();

  /**
   * Getter for datasource model
   * @return qcl_data_datasource_DbModel
   */
  public function datasourceModel();

  /**
   * Returns the behavior object responsible for maintaining the object
   * properties and providing access to them.
   * @return qcl_data_model_db_PropertyBehavior
   */
  public function getPropertyBehavior();

  /**
   * Returns the query behavior.
   * @return qcl_data_model_db_QueryBehavior
   */
  public function getQueryBehavior();

}
?>