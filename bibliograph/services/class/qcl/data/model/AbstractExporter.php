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

qcl_import( "qcl_core_Object" );


/**
 * Abstract class serving as marker interface for classes
 * that export data from active record models
 */
abstract class qcl_data_model_AbstractExporter
  extends qcl_core_Object
{

  /**
   * Exports the data from the model. Takes the model as argument. Returns
   * data in the format that the exporter provides
   * @param qcl_data_model_AbstractActiveRecord $model
   * @return mixed
   */
  abstract public function export( qcl_data_model_AbstractActiveRecord $model );

}
?>