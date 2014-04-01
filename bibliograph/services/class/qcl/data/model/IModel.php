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

qcl_import( "qcl_core_IPropertyAccessors" );

/**
 * Interface for data models
 */
interface qcl_data_model_IModel
  extends qcl_core_IPropertyAccessors
{

  /**
 * Add a property definition to the model
 * @param array $properties
 * @return void
 */
  public function addProperties( $properties );

}
?>